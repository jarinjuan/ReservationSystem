<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

if($_SESSION["role"] == 'Reader'){
    header("location: dashboard.php");
    exit;
}
require_once 'assets/database.php';
$conn = connectionDB();

$error = "";
$success = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $classroom_id = isset($_POST["classroom_id"]) ? trim($_POST["classroom_id"]) : "";
    $time_started = isset($_POST["time_started"]) ? trim($_POST["time_started"]) : "";
    $time_ended = isset($_POST["time_ended"]) ? trim($_POST["time_ended"]) : "";
    $status = "pending";

    if ($classroom_id === "" || $time_started === "" || $time_ended === "") {
        $error = "Prosím vyplňte všechna pole.";
    } elseif (strtotime($time_started) >= strtotime($time_ended)) {
        $error = "Čas konce musí být později než čas začátku.";
    } elseif (strtotime($time_started) < time()) {
        $error = "Nelze vytvořit rezervaci v minulosti.";
    } else {
        $check_sql = "SELECT id FROM classrooms WHERE id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "i", $classroom_id);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);
            
            if (mysqli_stmt_num_rows($check_stmt) == 0) {
                $error = "Vybraná učebna neexistuje.";
            } else {
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
                        $insert_sql = "INSERT INTO reservations (classroom_id, user_id, time_started, time_ended, status) 
                                     VALUES (?, ?, ?, ?, ?)";
                        $insert_stmt = mysqli_prepare($conn, $insert_sql);
                        
                        if ($insert_stmt) {
                            mysqli_stmt_bind_param($insert_stmt, "iisss", 
                                $classroom_id, $_SESSION["id"], $time_started, $time_ended, $status);
                            
                            if (mysqli_stmt_execute($insert_stmt)) {
                                $success = "Rezervace byla úspěšně vytvořena a čeká na schválení administrátorem!";
                                $classroom_id = $time_started = $time_ended = "";
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
    <title>Vytvořit rezervaci - Rezervační systém</title>
    <style>
        .form-container {
            max-width: 500px;
            margin: 0 auto;
        }

        .button {
            width: 100%;
            margin-bottom: 1rem;
        }

        .back-link {
            text-align: center;
            width: 100%;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Vytvořit rezervaci</h1>
            <div class="user-info">
                <div>Uživatel: <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></div>
                <div class="user-role"><?php echo htmlspecialchars($_SESSION["role"]); ?></div>
            </div>
        </header>

        <div class="content">
            <div class="form-container">
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

                    <button type="submit" class="button">Vytvořit rezervaci</button>
                </form>

                <a href="dashboard.php" class="back-link">Zpět na dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
