<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

$user_role = strtolower($_SESSION["role"]);
if($user_role != 'admin' && $_SESSION["role"] != 'Admin' && $_SESSION["role"] != 'adminek'){
    header("location: dashboard.php");
    exit;
}
require_once 'assets/database.php';
$conn = connectionDB();

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_classroom"])) {
    $description = trim($_POST["description"]);

    if (empty($description)) {
        $error = "Prosím zadejte popis učebny.";
    } else {
        $check_sql = "SELECT id FROM classrooms WHERE description = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);

        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "s", $description);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);

            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $error = "Učebna s tímto popisem již existuje.";
            } else {
                $insert_sql = "INSERT INTO classrooms (description) VALUES (?)";
                $insert_stmt = mysqli_prepare($conn, $insert_sql);

                if ($insert_stmt) {
                    mysqli_stmt_bind_param($insert_stmt, "s", $description);

                    if (mysqli_stmt_execute($insert_stmt)) {
                        $success = "Učebna byla úspěšně přidána!";
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_classroom"])) {
    $classroom_id = intval($_POST["classroom_id"]);

    if ($classroom_id > 0) {
        $check_reservations_sql = "SELECT COUNT(*) as count FROM reservations WHERE classroom_id = ?";
        $check_reservations_stmt = mysqli_prepare($conn, $check_reservations_sql);

        if ($check_reservations_stmt) {
            mysqli_stmt_bind_param($check_reservations_stmt, "i", $classroom_id);
            mysqli_stmt_execute($check_reservations_stmt);
            $result = mysqli_stmt_get_result($check_reservations_stmt);
            $row = mysqli_fetch_assoc($result);

            if ($row['count'] > 0) {
                $error = "Nelze smazat učebnu, která má aktivní rezervace.";
            } else {
                $delete_sql = "DELETE FROM classrooms WHERE id = ?";
                $delete_stmt = mysqli_prepare($conn, $delete_sql);

                if ($delete_stmt) {
                    mysqli_stmt_bind_param($delete_stmt, "i", $classroom_id);

                    if (mysqli_stmt_execute($delete_stmt)) {
                        $success = "Učebna byla úspěšně smazána!";
                    } else {
                        $error = "Chyba při mazání učebny.";
                    }
                } else {
                    $error = "Chyba při přípravě SQL dotazu pro mazání.";
                }
            }
        }
    } else {
        $error = "Neplatné ID učebny.";
    }
}
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
    <link rel="stylesheet" href="assets/discord-style.css">
    <title>Přidat učebnu - Rezervační systém</title>
    <style>
        .content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .form-section, .list-section {
            background-color: #2f3136;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 8px 16px rgba(0,0,0,0.24);
        }

        .classrooms-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .classroom-item {
            background-color: #40444b;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .classroom-id {
            color: #b9bbbe;
            font-size: 0.9rem;
        }

        .no-classrooms {
            text-align: center;
            color: #b9bbbe;
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
                    
                    <button type="submit" name="add_classroom" class="button">Přidat učebnu</button>
                </form>
                
                <a href="dashboard.php" class="back-link">Zpět na dashboard</a>
            </div>

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
                                    <div class="classroom-id">ID: <?php echo $classroom['id']; ?></div>
                                </div>
                                <div>
                                    <form method="post" style="display: inline;"
                                          onsubmit="return confirm('Opravdu chcete smazat učebnu <?php echo htmlspecialchars($classroom['description']); ?>?');">
                                        <input type="hidden" name="classroom_id" value="<?php echo $classroom['id']; ?>">
                                        <button type="submit" name="delete_classroom" class="button danger">Smazat</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
