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

$sql = "SELECT r.id, r.time_created, r.time_started, r.time_ended, r.status, 
               c.description as classroom_name
        FROM reservations r 
        JOIN classrooms c ON r.classroom_id = c.id 
        WHERE r.user_id = ? 
        ORDER BY r.time_started DESC";

$stmt = mysqli_prepare($conn, $sql);
$reservations = [];

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result) {
        $reservations = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
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
    <title>Moje rezervace - Rezervační systém</title>
    <style>
        .reservations-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
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
            <h1>Moje rezervace</h1>
            <div class="user-info">
                <div>Uživatel: <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></div>
                <div class="user-role"><?php echo htmlspecialchars($_SESSION["role"]); ?></div>
            </div>
        </header>

        <div class="content">
            <div class="actions">
                <a href="create-reservation.php" class="button">Vytvořit novou rezervaci</a>
                <a href="dashboard.php" class="button">Zpět na dashboard</a>
            </div>

            <?php if (empty($reservations)): ?>
                <div class="no-reservations">
                    <h3>Nemáte žádné rezervace</h3>
                    <p>Začněte vytvořením nové rezervace.</p>
                </div>
            <?php else: ?>
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
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
