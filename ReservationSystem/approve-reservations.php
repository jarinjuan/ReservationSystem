<?php
session_start();


if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

$user_role = $_SESSION["role"];
if($user_role != 'Admin' && $user_role != 'admin' && $user_role != 'adminek' && $user_role != 'Approver'){
    header("location: dashboard.php");
    exit;
}


require_once 'assets/database.php';
$conn = connectionDB();

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_status"])) {
    $reservation_id = isset($_POST["reservation_id"]) ? intval($_POST["reservation_id"]) : 0;
    $new_status = isset($_POST["new_status"]) ? trim($_POST["new_status"]) : "";

    if ($reservation_id >= 0 && !empty($new_status) && in_array($new_status, ['pending', 'confirmed', 'cancelled'])) {
        $update_sql = "UPDATE reservations SET status = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);

        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, "si", $new_status, $reservation_id);

            if (mysqli_stmt_execute($update_stmt)) {
                $success = "Status rezervace byl úspěšně změněn!";
            } else {
                $error = "Chyba při změně statusu rezervace.";
            }
        } else {
            $error = "Chyba při přípravě SQL dotazu.";
        }
    } else {
        $error = "Neplatné údaje pro změnu statusu.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_reservation"])) {
    $reservation_id = isset($_POST["reservation_id"]) ? intval($_POST["reservation_id"]) : 0;

    if ($reservation_id >= 0) {
        $check_sql = "SELECT status FROM reservations WHERE id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);

        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "i", $reservation_id);
            mysqli_stmt_execute($check_stmt);
            $result = mysqli_stmt_get_result($check_stmt);
            $reservation = mysqli_fetch_assoc($result);

            if ($reservation && $reservation['status'] == 'cancelled') {
                $delete_sql = "DELETE FROM reservations WHERE id = ?";
                $delete_stmt = mysqli_prepare($conn, $delete_sql);

                if ($delete_stmt) {
                    mysqli_stmt_bind_param($delete_stmt, "i", $reservation_id);

                    if (mysqli_stmt_execute($delete_stmt)) {
                        $success = "Zrušená rezervace byla úspěšně smazána z databáze!";
                    } else {
                        $error = "Chyba při mazání rezervace.";
                    }
                } else {
                    $error = "Chyba při přípravě SQL dotazu pro mazání.";
                }
            } else {
                $error = "Lze smazat pouze zrušené rezervace.";
            }
        } else {
            $error = "Chyba při kontrole statusu rezervace.";
        }
    } else {
        $error = "Neplatné ID rezervace.";
    }
}
$sql = "SELECT r.id, r.time_created, r.time_started, r.time_ended, r.status, 
               c.description as classroom_name, u.username
        FROM reservations r 
        JOIN classrooms c ON r.classroom_id = c.id 
        JOIN users u ON r.user_id = u.id
        ORDER BY r.time_started ASC";

$result = mysqli_query($conn, $sql);
$reservations = [];
if ($result) {
    $reservations = mysqli_fetch_all($result, MYSQLI_ASSOC);
}


function formatDateTime($datetime) {
    return date('d.m.Y H:i', strtotime($datetime));
}


function translateStatus($status) {
    switch($status) {
        case 'pending': return 'Čekající';
        case 'confirmed': return 'Potvrzeno';
        case 'cancelled': return 'Zrušeno';
        default: return $status;
    }
}


function getStatusClass($status) {
    switch($status) {
        case 'pending': return 'status-pending';
        case 'confirmed': return 'status-confirmed';
        case 'cancelled': return 'status-cancelled';
        default: return '';
    }
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
    <title>Schválit rezervace - Rezervační systém</title>
    <style>
        .reservations-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
        }

        .status-form {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #40444b;
        }

        .status-form select {
            background-color: #40444b;
            color: #dcddde;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            margin-right: 10px;
            font-family: "Roboto Mono", monospace;
        }

        .status-form button {
            background-color: #5865f2;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            cursor: pointer;
            font-family: "Roboto Mono", monospace;
            font-size: 0.9rem;
        }

        .status-form button:hover {
            background-color: #4752c4;
        }

        .delete-form {
            margin-top: 0.5rem;
        }

        .delete-form button {
            background-color: #ed4245;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            cursor: pointer;
            font-family: "Roboto Mono", monospace;
            font-size: 0.8rem;
            width: 100%;
        }

        .delete-form button:hover {
            background-color: #c23616;
        }

        .no-reservations {
            text-align: center;
            padding: 3rem;
            color: #b9bbbe;
        }

        .no-reservations h3 {
            margin-bottom: 1rem;
        }
        .reservation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .reservation-id {
            font-size: 0.9rem;
            color: #b9bbbe;
        }

        .classroom-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .user-name {
            color: #5865f2;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .time-info {
            margin-bottom: 0.5rem;
        }

        .time-label {
            color: #b9bbbe;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Schválit rezervace</h1>
            <div class="user-info">
                <div>Uživatel: <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></div>
                <div class="user-role"><?php echo htmlspecialchars($_SESSION["role"]); ?></div>
            </div>
        </header>

        <div class="content">
            <div class="actions">
                <a href="dashboard.php" class="button">Zpět na dashboard</a>
            </div>

            <?php if(!empty($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (empty($reservations)): ?>
                <div class="no-reservations">
                    <h3>Žádné rezervace v systému</h3>
                    <p>V databázi nejsou žádné rezervace ke schválení.</p>
                </div>
            <?php else: ?>

                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($reservations); ?></div>
                        <div class="stat-label">Celkem rezervací</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($reservations, function($r) { return $r['status'] == 'pending'; })); ?></div>
                        <div class="stat-label">Čekajících na schválení</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($reservations, function($r) { return $r['status'] == 'confirmed'; })); ?></div>
                        <div class="stat-label">Schválených</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($reservations, function($r) { return $r['status'] == 'cancelled'; })); ?></div>
                        <div class="stat-label">Zrušených</div>
                    </div>
                </div>


                <div class="reservations-grid">
                    <?php foreach($reservations as $reservation): ?>
                        <div class="reservation-card">
                            <div class="reservation-header">
                                <div class="reservation-id">Rezervace #<?php echo $reservation['id']; ?></div>
                                <div class="status <?php echo getStatusClass($reservation['status']); ?>">
                                    <?php echo translateStatus($reservation['status']); ?>
                                </div>
                            </div>
                            
                            <div class="classroom-name">
                                <?php echo htmlspecialchars($reservation['classroom_name']); ?>
                            </div>
                            
                            <div class="user-name">
                                Uživatel: <?php echo htmlspecialchars($reservation['username']); ?>
                            </div>
                            
                            <div class="time-info">
                                <div class="time-label">Začátek:</div>
                                <div><?php echo formatDateTime($reservation['time_started']); ?></div>
                            </div>
                            
                            <div class="time-info">
                                <div class="time-label">Konec:</div>
                                <div><?php echo formatDateTime($reservation['time_ended']); ?></div>
                            </div>
                            
                            <div class="time-info">
                                <div class="time-label">Vytvořeno:</div>
                                <div><?php echo formatDateTime($reservation['time_created']); ?></div>
                            </div>


                            <div class="status-form">
                                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="display: flex; align-items: center; gap: 10px;">
                                    <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($reservation['id']); ?>">
                                    <select name="new_status" required>
                                        <option value="pending" <?php echo $reservation['status'] == 'pending' ? 'selected' : ''; ?>>Čekající</option>
                                        <option value="confirmed" <?php echo $reservation['status'] == 'confirmed' ? 'selected' : ''; ?>>Schválit</option>
                                        <option value="cancelled" <?php echo $reservation['status'] == 'cancelled' ? 'selected' : ''; ?>>Zrušit</option>
                                    </select>
                                    <button type="submit" name="change_status" value="1">Změnit status</button>
                                </form>

                                <?php if($reservation['status'] == 'cancelled'): ?>
                                    <div class="delete-form">
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>"
                                              onsubmit="return confirm('Opravdu chcete trvale smazat tuto zrušenou rezervaci z databáze?');">
                                            <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($reservation['id']); ?>">
                                            <button type="submit" name="delete_reservation">🗑️ Smazat z databáze</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
