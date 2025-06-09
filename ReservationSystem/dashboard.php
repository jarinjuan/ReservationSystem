<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once 'assets/database.php';
$conn = connectionDB();
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
    <title>Dashboard - Reservation System</title>
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

        .welcome {
            font-size: 1.2rem;
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

        h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .dashboard-content {
            background-color: #424549;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .actions {
            margin-top: 2rem;
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

        .button.logout {
            background-color: #e74c3c;
        }

        .button.logout:hover {
            background-color: #c0392b;
        }

        .button.admin {
            background-color: #8e44ad;
        }

        .button.admin:hover {
            background-color: #9b59b6;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="welcome">Vítejte v rezervačním systému!</div>
            <div class="user-info">
                <div>Uživatel: <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></div>
                <div class="user-role"><?php echo htmlspecialchars($_SESSION["role"]); ?></div>
            </div>
        </header>

        <h1>Dashboard</h1>
        
        <div class="dashboard-content">
            <h2>Vítejte v systému rezervací</h2>
            <p>Jste úspěšně přihlášen jako <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong> s rolí <strong><?php echo htmlspecialchars($_SESSION["role"]); ?></strong>.</p>
            <?php if($_SESSION["role"] == 'Reader'): ?>
                <p>Jako Reader můžete prohlížet všechny rezervace v systému.</p>
            <?php else: ?>
                <p>Zde můžete spravovat vaše rezervace a nastavení.</p>
            <?php endif; ?>
            
            <div class="actions">
                <?php if($_SESSION["role"] == 'Reader'): ?>
                  
                    <a href="view-all-reservations.php" class="button">Zobrazit všechny rezervace</a>
                <?php else: ?>
               
                    <a href="my-reservations.php" class="button">Moje rezervace</a>
                    <a href="create-reservation.php" class="button">Vytvořit rezervaci</a>
                    <?php if(strtolower($_SESSION["role"]) == 'admin' || $_SESSION["role"] == 'Admin' || $_SESSION["role"] == 'adminek' || $_SESSION["role"] == 'Approver'): ?>
                        <a href="approve-reservations.php" class="button admin">Schválit rezervace</a>
                    <?php endif; ?>
                    <?php if(strtolower($_SESSION["role"]) == 'admin' || $_SESSION["role"] == 'Admin' || $_SESSION["role"] == 'adminek'): ?>
                        <a href="add-classroom.php" class="button admin">Přidat učebny</a>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="logout.php" class="button logout">Odhlásit se</a>
            </div>
        </div>
    </div>
</body>
</html>