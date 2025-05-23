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
            echo "Dotaz připraven: " . $sql . "<br>";
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
    $role = trim($_POST["register_role"]);  // Získání vybrané role

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
    <title>Reservation system</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Roboto Mono", monospace; }
        body { background-color: #1e2124; color: #333; line-height: 1.6; }
        main { text-align: center; padding: 2rem; max-width: 1200px; margin: 0 auto; }
        h1 { font-size: 2.5rem; margin-bottom: 2rem; color: #ffffff; text-transform: uppercase; letter-spacing: 2px; }
        h2 { color: white; margin-bottom: 1rem; font-weight: 500; }
        .formsDiv { display: flex; justify-content: center; flex-wrap: wrap; gap: 2rem; }
        form { border: none; border-radius: 12px; padding: 2rem; width: 350px; background-color: #424549; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        form:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.15); }
        label { display: block; text-align: left; margin-bottom: 0.5rem; font-weight: 500; color: white; }
        input { width: 100%; padding: 12px; margin-bottom: 1.5rem; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 1rem; }
        input:focus { outline: none; border-color: black; }
        button { background-color: #1e2124; color: white; border: none; border-radius: 6px; padding: 12px 24px; font-size: 1rem; cursor: pointer; width: 100%; font-weight: 500; text-transform: uppercase; }
        button:hover { background-color: #2980b9; }
        @media (max-width: 768px) {
            .formsDiv { flex-direction: column; align-items: center; }
            form { width: 90%; max-width: 350px; }
            h1 { font-size: 2rem; }
        }
        img { height: 5vh; width: auto; }
        #passwordMargin { margin-bottom: 9vh; }
        .error { color: #ff6b6b; margin-bottom: 1rem; font-weight: 500; }
        .success { color: #51cf66; margin-bottom: 1rem; font-weight: 500; }
    </style>
</head>
<body>
    <main>
        <h1>Reservation system</h1>
        <div class="formsDiv">
            <form method="post">
                <h2>Log-in</h2>
                <img src="assets/login.png">
                <?php if(!empty($login_error)): ?><p class="error"><?php echo $login_error; ?></p><?php endif; ?>
                <?php if(!empty($register_success)): ?><p class="success"><?php echo $register_success; ?></p><?php endif; ?>
                <label for="login_username"><b>Username</b></label>
                <input type="text" name="login_username" required>
                <label for="login_password"><b>Password</b></label>
                <input type="password" id="passwordMargin" name="login_password" required>
                <button type="submit" name="login">Login</button>
            </form>
            <form method="post">
                <h2>Register</h2>
                <img src="assets/register.png">
                <?php if(!empty($register_error)): ?><p class="error"><?php echo $register_error; ?></p><?php endif; ?>
                <label for="register_username"><b>Username</b></label>
                <input type="text" name="register_username" required>
                <label for="register_password"><b>Password</b></label>
                <input type="password" name="register_password" required>
                <label for="register_role"><b>Role</b></label>
                <select name="register_role" required>
                    <option value="Admin">Admin</option>
                    <option value="Reader">Reader</option>
                    <option value="Customer">Customer</option>
                    <option value="Approver">Approver</option>
                </select>

                <button type="submit" name="register">Create account</button>
            </form>
        </div>
    </main>
</body>
</html>
