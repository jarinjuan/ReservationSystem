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
    <link rel="stylesheet" href="assets/discord-style.css">
    <title>Hlavní panel - Rezervační systém</title>
    <style>
        .welcome {
            font-size: 1.2rem;
            color: #dcddde;
        }

        .actions {
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-top: 3rem;
        }

        .actions .button {
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .actions {
                flex-direction: column;
                gap: 1rem;
            }

            .actions .button {
                margin-bottom: 0;
            }
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

        <h1>Hlavní panel</h1>
        
        <div class="dashboard-content">
            <?php if($_SESSION["role"] == 'Reader'): ?>
                <p>Jako Reader můžete prohlížet všechny rezervace v systému.</p>
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
                        <a href="add-classroom.php" class="button admin">Upravit učebny</a>
                        <a href="manage-users.php" class="button admin">Spravovat uživatele</a>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="logout.php" class="button logout">Odhlásit se</a>
            </div>
        </div>
    </div>
</body>
</html>