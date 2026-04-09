<?php
require_once 'db.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $conn = getDB();
        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE name = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['name'];

                // Log the login as a command
                $userId = $row['id'];
                $stmt2 = $conn->prepare("INSERT INTO commands (user_id, command_type, command_status) VALUES (?, 'connect', 'connected to monitoring system')");
                $stmt2->bind_param("i", $userId);
                $stmt2->execute();

                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
        $conn->close();
    } else {
        $error = 'Please enter username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rain Detection System - Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: #fff;
            border-radius: 16px;
            padding: 48px 40px;
            width: 380px;
            text-align: center;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }

        .avatar-icon {
            width: 64px;
            height: 64px;
            background: #7c5cbf;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .avatar-icon svg {
            width: 32px;
            height: 32px;
            fill: white;
        }

        h1 {
            color: #7c5cbf;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .subtitle {
            color: #888;
            font-size: 14px;
            margin-bottom: 28px;
        }

        .error-msg {
            background: #fee2e2;
            color: #dc2626;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 16px;
            text-align: left;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 14px;
            color: #333;
            background: #fafafa;
            outline: none;
            transition: border-color 0.2s;
            margin-bottom: 14px;
        }

        input:focus {
            border-color: #7c5cbf;
            background: #fff;
        }

        input::placeholder { color: #aaa; }

        button[type="submit"] {
            width: 100%;
            background: linear-gradient(135deg, #7c5cbf, #5b4da6);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 13px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
            margin-top: 4px;
        }

        button[type="submit"]:hover { opacity: 0.92; }

        .register-link {
            margin-top: 20px;
            font-size: 13px;
            color: #888;
        }

        .register-link a {
            color: #7c5cbf;
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="avatar-icon">
            <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
        </div>
        <h1>Rain Detection System</h1>
        <p class="subtitle">Sign in to your account</p>

        <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Username" autocomplete="username" required>
            <input type="password" name="password" placeholder="Password" autocomplete="current-password" required>
            <button type="submit">Sign In</button>
        </form>

        <div class="register-link">
            Don't have an account? <a href="register.php">Create account &rarr;</a>
        </div>
    </div>
</body>
</html>
