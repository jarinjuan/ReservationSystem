<?php
// Začátek session
session_start();

// Kontrola, zda je uživatel přihlášen
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Kontrola, zda má uživatel admin práva
$user_role = strtolower($_SESSION["role"]);
if($user_role != 'admin' && $_SESSION["role"] != 'Admin' && $_SESSION["role"] != 'adminek'){
    header("location: dashboard.php");
    exit;
}

// Připojení k databázi
require_once 'assets/database.php';
$conn = connectionDB();

$error = "";
$success = "";

// Zpracování formuláře
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $description = trim($_POST["description"]);
    
    // Základní validace
    if (empty($description)) {
        $error = "Prosím zadejte popis učebny.";
    } else {
        // Kontrola, zda učebna s tímto popisem již neexistuje
        $check_sql = "SELECT id FROM classrooms WHERE description = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "s", $description);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);
            
            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $error = "Učebna s tímto popisem již existuje.";
            } else {
                // Vložení nové učebny
                $insert_sql = "INSERT INTO classrooms (description) VALUES (?)";
                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                
                if ($insert_stmt) {
                    mysqli_stmt_bind_param($insert_stmt, "s", $description);
                    
                    if (mysqli_stmt_execute($insert_stmt)) {
                        $success = "Učebna byla úspěšně přidána!";
                        // Vyčistit formulář
                        $description = "";
                    } else {
                        $error = "Něco se pokazilo při přidávání učebny.";
                    }
                } else {
                    $error = "Chyba při přípravě SQL dotazu.";
                }
            }
        } else {
            $error = "Chyba při kontrole duplicitní učebny.";
        }
    }
}

// Načtení všech existujících učeben
$classrooms_sql = "SELECT id, description FROM classrooms ORDER BY description";
$classrooms_result = mysqli_query($conn, $classrooms_sql);
$classrooms = [];
if ($classrooms_result) {
    $classrooms = mysqli_fetch_all($classrooms_result, MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="cz">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:ital,wght@0,100..700;1,100..700&display=swap" rel="stylesheet">
    <title>Přidat učebnu - Reservation System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Roboto Mono", monospace;
        }

        body {
            background-color: #1e2124;
            color: white;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #424549;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-role {
            background-color: #424549;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .form-section, .list-section {
            background-color: #424549;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #ffffff;
        }

        input {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background-color: #1e2124;
            color: white;
            font-size: 1rem;
            font-family: "Roboto Mono", monospace;
        }

        input:focus {
            outline: 2px solid #8e44ad;
        }

        .button {
            background-color: #8e44ad;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 24px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
            margin-bottom: 1rem;
        }

        .button:hover {
            background-color: #9b59b6;
        }

        .back-link {
            display: inline-block;
            color: #8e44ad;
            text-decoration: none;
            font-weight: 500;
            text-align: center;
            width: 100%;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .error {
            background-color: #e74c3c;
            color: white;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .success {
            background-color: #27ae60;
            color: white;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .classrooms-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .classroom-item {
            background-color: #1e2124;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .classroom-id {
            color: #bbb;
            font-size: 0.9rem;
        }

        .no-classrooms {
            text-align: center;
            color: #bbb;
            padding: 2rem;
        }

        @media (max-width: 768px) {
            .content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Přidat učebnu</h1>
            <div class="user-info">
                <div>Uživatel: <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></div>
                <div class="user-role"><?php echo htmlspecialchars($_SESSION["role"]); ?></div>
            </div>
        </header>

        <div class="content">
            <!-- Formulář pro přidání učebny -->
            <div class="form-section">
                <h2>Přidat novou učebnu</h2>
                
                <?php if(!empty($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                    <div class="success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="description">Popis učebny</label>
                        <input type="text" name="description" required 
                               placeholder="např. Učebna A1 - Informatika"
                               value="<?php echo isset($description) ? htmlspecialchars($description) : ''; ?>">
                    </div>
                    
                    <button type="submit" class="button">Přidat učebnu</button>
                </form>
                
                <a href="dashboard.php" class="back-link">Zpět na dashboard</a>
            </div>

            <!-- Seznam existujících učeben -->
            <div class="list-section">
                <h2>Existující učebny (<?php echo count($classrooms); ?>)</h2>
                
                <div class="classrooms-list">
                    <?php if (empty($classrooms)): ?>
                        <div class="no-classrooms">
                            <p>Žádné učebny nejsou v databázi.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($classrooms as $classroom): ?>
                            <div class="classroom-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($classroom['description']); ?></strong>
                                </div>
                                <div class="classroom-id">ID: <?php echo $classroom['id']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
