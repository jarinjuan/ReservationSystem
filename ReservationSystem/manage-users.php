<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

$user_role = strtolower($_SESSION["role"]);
if($user_role != 'admin' && $_SESSION["role"] != 'Admin' && $_SESSION["role"] != 'adminek'){
    header("location: dashboard.php");
    exit;
}
require_once 'assets/database.php';
$conn = connectionDB();

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_user"])) {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $role = trim($_POST["role"]);

    if (empty($username) || empty($password) || empty($role)) {
        $error = "Prosím vyplňte všechna pole.";
    } elseif (strlen($password) < 6) {
        $error = "Heslo musí mít alespoň 6 znaků.";
    } else {
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "s", $username);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);
            
            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $error = "Uživatelské jméno již existuje.";
            } else {
                
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                
                $insert_sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                
                if ($insert_stmt) {
                    mysqli_stmt_bind_param($insert_stmt, "sss", $username, $hashed_password, $role);
                    
                    if (mysqli_stmt_execute($insert_stmt)) {
                        $success = "Uživatel '$username' byl úspěšně přidán!";
                        
                        $username = $password = $role = "";
                    } else {
                        $error = "Chyba při přidávání uživatele.";
                    }
                } else {
                    $error = "Chyba při přípravě SQL dotazu.";
                }
            }
        } else {
            $error = "Chyba při kontrole uživatelského jména.";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_user"])) {
    $user_id = intval($_POST["user_id"]);
    
    if ($user_id > 0 && $user_id != $_SESSION["id"]) {
        $check_reservations_sql = "SELECT COUNT(*) as count FROM reservations WHERE user_id = ?";
        $check_reservations_stmt = mysqli_prepare($conn, $check_reservations_sql);
        
        if ($check_reservations_stmt) {
            mysqli_stmt_bind_param($check_reservations_stmt, "i", $user_id);
            mysqli_stmt_execute($check_reservations_stmt);
            $result = mysqli_stmt_get_result($check_reservations_stmt);
            $row = mysqli_fetch_assoc($result);
            
            if ($row['count'] > 0) {
                $error = "Nelze smazat uživatele, který má aktivní rezervace.";
            } else {
                $delete_sql = "DELETE FROM users WHERE id = ?";
                $delete_stmt = mysqli_prepare($conn, $delete_sql);
                
                if ($delete_stmt) {
                    mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
                    
                    if (mysqli_stmt_execute($delete_stmt)) {
                        $success = "Uživatel byl úspěšně smazán!";
                    } else {
                        $error = "Chyba při mazání uživatele.";
                    }
                } else {
                    $error = "Chyba při přípravě SQL dotazu pro mazání.";
                }
            }
        }
    } else {
        $error = "Nelze smazat sebe sama nebo neplatné ID uživatele.";
    }
}

$users_sql = "SELECT u.id, u.username, u.role, 
                     COUNT(r.id) as reservation_count
              FROM users u 
              LEFT JOIN reservations r ON u.id = r.user_id 
              GROUP BY u.id, u.username, u.role 
              ORDER BY u.id";
$users_result = mysqli_query($conn, $users_sql);
$users = [];
if ($users_result) {
    $users = mysqli_fetch_all($users_result, MYSQLI_ASSOC);
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
    <title>Spravovat uživatele - Rezervační systém</title>
    <style>
        .content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        .form-section, .list-section {
            background-color: #2f3136;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 8px 16px rgba(0,0,0,0.24);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background-color: #40444b;
            border-radius: 8px;
            overflow: hidden;
        }

        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #36393f;
        }

        .users-table th {
            background-color: #36393f;
            font-weight: 600;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .users-table tr:hover {
            background-color: #484c52;
        }

        @media (max-width: 768px) {
            .content {
                grid-template-columns: 1fr;
            }

            .users-table {
                font-size: 0.9rem;
            }

            .users-table th,
            .users-table td {
                padding: 0.5rem;
            }
        }

    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Spravovat uživatele</h1>
            <div class="user-info">
                <div>Uživatel: <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></div>
                <div class="user-role"><?php echo htmlspecialchars($_SESSION["role"]); ?></div>
            </div>
        </header>

        <div class="content">
            <div class="form-section">
                <h2>Přidat nového uživatele</h2>
                
                <?php if(!empty($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                    <div class="success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="username">Uživatelské jméno</label>
                        <input type="text" name="username" required 
                               value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Heslo</label>
                        <input type="password" name="password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select name="role" required>
                            <option value="">Vyberte roli</option>
                            <option value="Admin" <?php echo (isset($role) && $role == 'Admin') ? 'selected' : ''; ?>>Administrátor</option>
                            <option value="Approver" <?php echo (isset($role) && $role == 'Approver') ? 'selected' : ''; ?>>Schvalovatel</option>
                            <option value="Customer" <?php echo (isset($role) && $role == 'Customer') ? 'selected' : ''; ?>>Zákazník</option>
                            <option value="Reader" <?php echo (isset($role) && $role == 'Reader') ? 'selected' : ''; ?>>Čtenář</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="add_user" class="button">Přidat uživatele</button>
                </form>
                
                <a href="dashboard.php" class="back-link">Zpět na dashboard</a>
            </div>

            <div class="list-section">
                <h2>Existující uživatelé</h2>
            
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($users); ?></div>
                        <div class="stat-label">Celkem uživatelů</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($users, function($u) { return strtolower($u['role']) == 'admin' || $u['role'] == 'Admin'; })); ?></div>
                        <div class="stat-label">Adminů</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo array_sum(array_column($users, 'reservation_count')); ?></div>
                        <div class="stat-label">Celkem rezervací</div>
                    </div>
                </div>
                
                <?php if (empty($users)): ?>
                    <p>Žádní uživatelé v databázi.</p>
                <?php else: ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Uživatelské jméno</th>
                                <th>Role</th>
                                <th>Rezervace</th>
                                <th>Akce</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo strtolower($user['role']); ?>">
                                            <?php echo htmlspecialchars($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $user['reservation_count']; ?></td>
                                    <td>
                                        <?php if($user['id'] != $_SESSION["id"]): ?>
                                            <form method="post" style="display: inline;" 
                                                  onsubmit="return confirm('Opravdu chcete smazat uživatele <?php echo htmlspecialchars($user['username']); ?>?');">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="delete_user" class="button danger">Smazat</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: #bbb; font-size: 0.9rem;">Aktuální uživatel</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
