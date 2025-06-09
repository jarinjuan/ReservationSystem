<?php
// Začátek session
session_start();

// Kontrola, zda je uživatel přihlášen
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Kontrola, zda má uživatel oprávnění (Admin nebo Approver)
$user_role = $_SESSION["role"];
if($user_role != 'Admin' && $user_role != 'admin' && $user_role != 'adminek' && $user_role != 'Approver'){
    header("location: dashboard.php");
    exit;
}

// Připojení k databázi
require_once 'assets/database.php';
$conn = connectionDB();

$error = "";
$success = "";

// Zpracování změny statusu
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_status"])) {
    $reservation_id = isset($_POST["reservation_id"]) ? intval($_POST["reservation_id"]) : 0;
    $new_status = isset($_POST["new_status"]) ? trim($_POST["new_status"]) : "";

    // Debug informace (můžete smazat po opravě)
    echo "Debug: reservation_id='$reservation_id', new_status='$new_status'<br>";
    echo "Debug POST data: ";
    var_dump($_POST);
    echo "<br>";

    if ($reservation_id >= 0 && !empty($new_status) && in_array($new_status, ['pending', 'confirmed', 'cancelled'])) {
        // Aktualizace statusu rezervace
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
        $error = "Neplatné údaje pro změnu statusu. ID rezervace: $reservation_id, Status: '$new_status'";
    }
}

// Načtení všech rezervací s detaily
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

// Debug: zobrazit počet rezervací
echo "Debug: Počet rezervací v databázi: " . count($reservations) . "<br>";
if (!empty($reservations)) {
    echo "Debug: První rezervace má ID: " . $reservations[0]['id'] . "<br>";
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
    <title>Schválit rezervace - Reservation System</title>
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
            text-decoration: none;
            display: inline-block;
        }

        .button:hover {
            background-color: #9b59b6;
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
            color: #8e44ad;
        }

        .stat-label {
            color: #bbb;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .reservations-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
        }

        .reservation-card {
            background-color: #1e2124;
            border-radius: 8px;
            padding: 1.5rem;
            border-left: 4px solid #8e44ad;
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
            color: #8e44ad;
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

        .status-form {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #424549;
        }

        .status-form select {
            background-color: #424549;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            margin-right: 10px;
            font-family: "Roboto Mono", monospace;
        }

        .status-form button {
            background-color: #8e44ad;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            cursor: pointer;
            font-family: "Roboto Mono", monospace;
            font-size: 0.9rem;
        }

        .status-form button:hover {
            background-color: #9b59b6;
        }

        .no-reservations {
            text-align: center;
            padding: 3rem;
            color: #bbb;
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
                <!-- Statistiky -->
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

                <!-- Seznam rezervací -->
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

                            <!-- Formulář pro změnu statusu -->
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
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
