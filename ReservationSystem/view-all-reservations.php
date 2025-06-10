<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

if($_SESSION["role"] != 'Reader'){
    header("location: dashboard.php");
    exit;
}

require_once 'assets/database.php';
$conn = connectionDB();

$sql = "SELECT r.id, r.time_created, r.time_started, r.time_ended, r.status, 
               c.description as classroom_name, u.username
        FROM reservations r 
        JOIN classrooms c ON r.classroom_id = c.id 
        JOIN users u ON r.user_id = u.id
        ORDER BY r.time_started DESC";

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
    <title>Všechny rezervace - Rezervační systém</title>
    <style>
        .reservations-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        }

        .user-name {
            color: #5865f2;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .no-reservations {
            text-align: center;
            padding: 3rem;
            color: #b9bbbe;
        }

        .no-reservations h3 {
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .reservations-grid {
                grid-template-columns: 1fr;
            }
        }
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
            max-width: 1400px;
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
            background-color: #424549;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .actions {
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
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
            text-decoration: none;
            display: inline-block;
        }

        .button:hover {
            background-color: #2980b9;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: #1e2124;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2980b9;
        }

        .stat-label {
            color: #bbb;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .reservations-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        }

        .reservation-card {
            background-color: #1e2124;
            border-radius: 8px;
            padding: 1.5rem;
            border-left: 4px solid #2980b9;
        }

        .reservation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .reservation-id {
            font-size: 0.9rem;
            color: #bbb;
        }

        .status {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #f39c12;
            color: #1e2124;
        }

        .status-confirmed {
            background-color: #27ae60;
            color: white;
        }

        .status-cancelled {
            background-color: #e74c3c;
            color: white;
        }

        .classroom-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .user-name {
            color: #2980b9;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .time-info {
            margin-bottom: 0.5rem;
        }

        .time-label {
            color: #bbb;
            font-size: 0.9rem;
        }

        .no-reservations {
            text-align: center;
            padding: 3rem;
            color: #bbb;
        }

        .no-reservations h3 {
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .reservations-grid {
                grid-template-columns: 1fr;
            }
            
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Všechny rezervace</h1>
            <div class="user-info">
                <div>Uživatel: <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></div>
                <div class="user-role"><?php echo htmlspecialchars($_SESSION["role"]); ?></div>
            </div>
        </header>

        <div class="content">
            <div class="actions">
                <a href="dashboard.php" class="button">Zpět na dashboard</a>
            </div>

            <?php if (empty($reservations)): ?>
                <div class="no-reservations">
                    <h3>Žádné rezervace v systému</h3>
                    <p>V databázi nejsou žádné rezervace.</p>
                </div>
            <?php else: ?>
            
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($reservations); ?></div>
                        <div class="stat-label">Celkem rezervací</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($reservations, function($r) { return $r['status'] == 'confirmed'; })); ?></div>
                        <div class="stat-label">Potvrzených</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($reservations, function($r) { return $r['status'] == 'pending'; })); ?></div>
                        <div class="stat-label">Čekajících</div>
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
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
