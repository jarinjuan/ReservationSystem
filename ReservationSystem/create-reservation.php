<?php
// Začátek session
session_start();

// Kontrola, zda je uživatel přihlášen
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Kontrola, zda uživatel není Reader (Reader nemůže vytvářet rezervace)
if($_SESSION["role"] == 'Reader'){
    header("location: dashboard.php");
    exit;
}

// Připojení k databázi
require_once 'assets/database.php';
$conn = connectionDB();

$error = "";
$success = "";

// Debug session informace (odkomentujte pro debugging)
// echo "Debug SESSION: ";
// var_dump($_SESSION);
// echo "<br>";

// Zpracování formuláře
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $classroom_id = isset($_POST["classroom_id"]) ? trim($_POST["classroom_id"]) : "";
    $time_started = isset($_POST["time_started"]) ? trim($_POST["time_started"]) : "";
    $time_ended = isset($_POST["time_ended"]) ? trim($_POST["time_ended"]) : "";
    $status = isset($_POST["status"]) ? trim($_POST["status"]) : "";

    // Debug informace (odkomentujte pro debugging)
    // echo "Debug: classroom_id='$classroom_id', time_started='$time_started', time_ended='$time_ended', status='$status'<br>";
    // echo "Debug: user_id z session: " . (isset($_SESSION["id"]) ? $_SESSION["id"] : "NENÍ NASTAVENO") . "<br>";

    // Základní validace
    if ($classroom_id === "" || $time_started === "" || $time_ended === "" || $status === "") {
        $error = "Prosím vyplňte všechna pole.";
        // Debug: které pole je prázdné
        if ($classroom_id === "") $error .= " (Učebna je prázdná)";
        if ($time_started === "") $error .= " (Začátek je prázdný)";
        if ($time_ended === "") $error .= " (Konec je prázdný)";
        if ($status === "") $error .= " (Stav je prázdný)";
    } elseif (strtotime($time_started) >= strtotime($time_ended)) {
        $error = "Čas konce musí být později než čas začátku.";
    } elseif (strtotime($time_started) < time()) {
        $error = "Nelze vytvořit rezervaci v minulosti.";
    } else {
        // Kontrola, zda učebna existuje
        $check_sql = "SELECT id FROM classrooms WHERE id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "i", $classroom_id);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);
            
            if (mysqli_stmt_num_rows($check_stmt) == 0) {
                $error = "Vybraná učebna neexistuje.";
            } else {
                // Kontrola kolize s existujícími rezervacemi
                $conflict_sql = "SELECT id FROM reservations 
                               WHERE classroom_id = ? 
                               AND status != 'cancelled' 
                               AND (
                                   (time_started <= ? AND time_ended > ?) OR
                                   (time_started < ? AND time_ended >= ?) OR
                                   (time_started >= ? AND time_ended <= ?)
                               )";
                $conflict_stmt = mysqli_prepare($conn, $conflict_sql);
                
                if ($conflict_stmt) {
                    mysqli_stmt_bind_param($conflict_stmt, "issssss", 
                        $classroom_id, $time_started, $time_started, 
                        $time_ended, $time_ended, $time_started, $time_ended);
                    mysqli_stmt_execute($conflict_stmt);
                    mysqli_stmt_store_result($conflict_stmt);
                    
                    if (mysqli_stmt_num_rows($conflict_stmt) > 0) {
                        $error = "Učebna je v tomto čase již rezervována.";
                    } else {
                        // Vložení nové rezervace
                        $insert_sql = "INSERT INTO reservations (classroom_id, user_id, time_started, time_ended, status) 
                                     VALUES (?, ?, ?, ?, ?)";
                        $insert_stmt = mysqli_prepare($conn, $insert_sql);
                        
                        if ($insert_stmt) {
                            mysqli_stmt_bind_param($insert_stmt, "iisss", 
                                $classroom_id, $_SESSION["id"], $time_started, $time_ended, $status);
                            
                            if (mysqli_stmt_execute($insert_stmt)) {
                                $inserted_id = mysqli_insert_id($conn);
                                $success = "Rezervace byla úspěšně vytvořena! ID: $inserted_id pro uživatele: " . $_SESSION["id"];
                                // Debug: kolik rezervací je nyní v databázi
                                $count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM reservations");
                                $count_row = mysqli_fetch_assoc($count_result);
                                $success .= " (Celkem rezervací v DB: " . $count_row['count'] . ")";
                                // Vyčistit formulář
                                $classroom_id = $time_started = $time_ended = $status = "";
                            } else {
                                $error = "Něco se pokazilo při vytváření rezervace. Chyba: " . mysqli_error($conn);
                            }
                        } else {
                            $error = "Chyba při přípravě SQL dotazu.";
                        }
                    }
                } else {
                    $error = "Chyba při kontrole kolizí rezervací.";
                }
            }
        } else {
            $error = "Chyba při kontrole učebny.";
        }
    }
}

// Načtení seznamu učeben
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
    <title>Vytvořit rezervaci - Reservation System</title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background-color: #424549;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-align: center;
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

        input, select {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background-color: #1e2124;
            color: white;
            font-size: 1rem;
            font-family: "Roboto Mono", monospace;
        }

        input:focus, select:focus {
            outline: 2px solid #2980b9;
        }

        .button {
            background-color: #1e2124;
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
            background-color: #2980b9;
        }

        .back-link {
            display: inline-block;
            color: #2980b9;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Vytvořit rezervaci</h1>
        
        <?php if(!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="classroom_id">Učebna</label>
                <select name="classroom_id" required>
                    <option value="">Vyberte učebnu</option>
                    <?php foreach($classrooms as $classroom): ?>
                        <option value="<?php echo $classroom['id']; ?>" 
                                <?php echo (isset($classroom_id) && $classroom_id == $classroom['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($classroom['description']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="time_started">Začátek rezervace</label>
                <input type="datetime-local" name="time_started" required 
                       value="<?php echo isset($time_started) ? htmlspecialchars($time_started) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="time_ended">Konec rezervace</label>
                <input type="datetime-local" name="time_ended" required 
                       value="<?php echo isset($time_ended) ? htmlspecialchars($time_ended) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="status">Stav rezervace</label>
                <select name="status" required>
                    <option value="">Vyberte stav</option>
                    <option value="pending" <?php echo (isset($status) && $status == 'pending') ? 'selected' : ''; ?>>Čekající</option>
                    <option value="confirmed" <?php echo (isset($status) && $status == 'confirmed') ? 'selected' : ''; ?>>Potvrzeno</option>
                    <option value="cancelled" <?php echo (isset($status) && $status == 'cancelled') ? 'selected' : ''; ?>>Zrušeno</option>
                </select>
            </div>
            
            <button type="submit" class="button">Vytvořit rezervaci</button>
        </form>
        
        <a href="dashboard.php" class="back-link">Zpět na dashboard</a>
    </div>
</body>
</html>
