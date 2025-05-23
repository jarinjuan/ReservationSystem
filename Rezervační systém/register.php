<?php
// Začátek session pro sledování přihlášeného uživatele
session_start();

// Připojení k databázi
require_once 'assets/database.php';

// Kontrola, zda byl odeslán formulář
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Získání dat z formuláře
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $role = trim($_POST["role"]);
    
    // Základní validace
    if (empty($username) || empty($password) || empty($role)) {
        $error = "Prosím vyplňte všechna pole.";
    } else {
        // Kontrola, zda uživatelské jméno již existuje
        $sql = "SELECT id FROM user WHERE username = :username";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error = "Toto uživatelské jméno již existuje. Vyberte prosím jiné.";
        } else {
            // Hashování hesla před uložením do databáze
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Vložení nového uživatele do databáze
            $sql = "INSERT INTO user (username, password, role) VALUES (:username, :password, :role)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->bindParam(":role", $role);
            
            if ($stmt->execute()) {
                // Přesměrování na přihlašovací stránku
                header("Location: index.html?registered=success");
                exit();
            } else {
                $error = "Něco se pokazilo. Zkuste to prosím znovu.";
            }
        }
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
    <title>Registrace</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Roboto Mono", monospace;
        }

        body {
            background-color: #1e2124;
            color: #333;
            line-height: 1.6;
        }

        main {
            text-align: center;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        h2 {
            color: white;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        form {
            border: none;
            border-radius: 12px;
            padding: 2rem;
            width: 350px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            background-color: #424549;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin: 0 auto;
        }

        form:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        label {
            display: block;
            text-align: left;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: white;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 1.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: black;
        }

        button {
            background-color: #1e2124;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 24px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        button:hover {
            background-color: #2980b9;
        }

        img {
            height: 5vh;
            width: auto;
        }

        .error {
            color: #ff6b6b;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .back-link {
            display: block;
            margin-top: 1rem;
            color: white;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <main>
        <h1>Registrace</h1>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <h2>Register</h2>
            <img src="assets/register.png">
            
            <?php if(isset($error)): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            
            <label for="username"><b>Username</b></label>
            <input type="text" placeholder="Enter username" name="username" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
            
            <label for="password"><b>Password</b></label>
            <input type="password" placeholder="Enter password" name="password" required>
            
            <label for="role"><b>Role</b></label>
            <input type="text" placeholder="Enter role" name="role" required value="<?php echo isset($role) ? htmlspecialchars($role) : ''; ?>">
            
            <button type="submit">Create account</button>
            
            <a href="index.html" class="back-link">Zpět na přihlášení</a>
        </form>
    </main>
</body>
</html>