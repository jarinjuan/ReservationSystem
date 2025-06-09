<?php
// Začátek session
session_start();

// Kontrola, zda je uživatel přihlášen
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Kontrola, zda uživatel není Reader (Reader nemůže zobrazovat své rezervace)
if($_SESSION["role"] == 'Reader'){
    header("location: dashboard.php");
    exit;
}

// Připojení k databázi
require_once 'assets/database.php';
$conn = connectionDB();

// Načtení rezervací aktuálního uživatele
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

// Funkce pro formátování data
function formatDateTime($datetime) {
    return date('d.m.Y H:i', strtotime($datetime));
}

// Funkce pro překlad statusu
function translateStatus($status) {
    switch($status) {
        case 'pending': return 'Čekající';
        case 'confirmed': return 'Potvrzeno';
        case 'cancelled': return 'Zrušeno';
        default: return $status;
    }
}

// Funkce pro CSS třídu statusu
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
    <title>Moje rezervace - Reservation System</title>
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

        .reservations-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
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
