<?php
session_start();
require_once 'assets/database.php';
$conn = connectionDB();

$login_error = "";
$register_error = "";
$register_success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $username = trim($_POST["login_username"]);
    $password = trim($_POST["login_password"]);

    if (empty($username) || empty($password)) {
        $login_error = "Prosím vyplňte uživatelské jméno a heslo.";
    } else {
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                if (password_verify($password, $row["password"])) {
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $row["id"];
                    $_SESSION["username"] = $row["username"];
                    $_SESSION["role"] = $row["role"];
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $login_error = "Neplatné heslo. Zkuste to prosím znovu.";
                }
            } else {
                $login_error = "Uživatelské jméno neexistuje. Zkuste to prosím znovu.";
            }
        } else {
            $login_error = "Chyba při přípravě SQL dotazu pro přihlášení.";
            echo "Detailní chyba: " . mysqli_error($conn);
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    $username = trim($_POST["register_username"]);
    $password = trim($_POST["register_password"]);
    $role = trim($_POST["register_role"]);  

    if (empty($username) || empty($password) || empty($role)) {
        $register_error = "Prosím vyplňte všechna pole.";
    } else {
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);

        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "s", $username);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);

            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $register_error = "Toto uživatelské jméno již existuje. Vyberte prosím jiné.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert_sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
                $insert_stmt = mysqli_prepare($conn, $insert_sql);

                if ($insert_stmt) {
                    mysqli_stmt_bind_param($insert_stmt, "sss", $username, $hashed_password, $role);
                    if (mysqli_stmt_execute($insert_stmt)) {
                        $register_success = "Registrace proběhla úspěšně! Nyní se můžete přihlásit.";
                    } else {
                        $register_error = "Něco se pokazilo při ukládání uživatele.";
                    }
                } else {
                    $register_error = "Chyba při přípravě SQL dotazu pro registraci.";
                }
            }
        } else {
            $register_error = "Chyba při přípravě SQL dotazu pro kontrolu uživatele.";
        }
    }
}


if (mysqli_connect_error()) {
    die("Připojení do DB selhalo: " . mysqli_connect_error());
}
?>
<!DOCTYPE html>
<html lang="cz">
<head>
    <meta charset="UTF-8">
    <title>Rezervační systém</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:ital,wght@0,100..700;1,100..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/discord-style.css">
    <style>
        
        main {
            text-align: center;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .formsDiv {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 2rem;
        }

        form {
            border: none;
            border-radius: 8px;
            padding: 2rem;
            width: 350px;
            background-color: #2f3136;
            box-shadow: 0 8px 16px rgba(0,0,0,0.24);
            transition: all 0.3s ease;
        }

        form:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.35);
        }

        img {
            height: 5vh;
            width: auto;
            margin-bottom: 1rem;
            filter: brightness(0.9);
        }

        #passwordMargin {
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .formsDiv {
                flex-direction: column;
                align-items: center;
            }
            form {
                width: 90%;
                max-width: 350px;
            }
            h1 {
                font-size: 2rem;
            }
        }

    </style>
</head>
<body>
    <main>
        <h1>Rezervační systém</h1>
        <div class="formsDiv">
            <form method="post">
                <h2>Přihlášení</h2>
                <img src="assets/login.png">
                <?php if(!empty($login_error)): ?><p class="error"><?php echo $login_error; ?></p><?php endif; ?>
                <?php if(!empty($register_success)): ?><p class="success"><?php echo $register_success; ?></p><?php endif; ?>
                <label for="login_username"><b>Uživatelské jméno</b></label>
                <input type="text" name="login_username" required>
                <label for="login_password"><b>Heslo</b></label>
                <input type="password" id="passwordMargin" name="login_password" required>
                <button type="submit" name="login">Přihlásit se</button>
            </form>
            <form method="post">
                <h2>Registrace</h2>
                <img src="assets/register.png">
                <?php if(!empty($register_error)): ?><p class="error"><?php echo $register_error; ?></p><?php endif; ?>
                <label for="register_username"><b>Uživatelské jméno</b></label>
                <input type="text" name="register_username" required>
                <label for="register_password"><b>Heslo</b></label>
                <input type="password" name="register_password" required>
                <label for="register_role"><b>Role</b></label>
                <select name="register_role" required style="margin-bottom: 1.5rem;">
                    <option value="">Vyberte roli</option>
                    <option value="Admin">Administrátor</option>
                    <option value="Reader">Čtenář</option>
                    <option value="Customer">Zákazník</option>
                    <option value="Approver">Schvalovatel</option>
                </select>

                <button type="submit" name="register">Vytvořit účet</button>
            </form>
        </div>
    </main>
</body>
</html>
