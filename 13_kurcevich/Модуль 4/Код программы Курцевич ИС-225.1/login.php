<?php
session_start();

require_once 'db_connect.php';
require_once 'config.php';

function getFailedAttemptsFromDB($conn, $username) {
    $stmt = $conn->prepare("SELECT failed_attempts, is_blocked FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user) {
        return ['attempts' => $user['failed_attempts'], 'is_blocked' => $user['is_blocked']];
    }
    return ['attempts' => 0, 'is_blocked' => 0];
}

function incrementFailedAttempts($conn, $username) {
    $current = getFailedAttemptsFromDB($conn, $username);
    $new_attempts = $current['attempts'] + 1;
    $is_blocked = $new_attempts >= 3 ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE users SET failed_attempts = ?, is_blocked = ?, last_failed_attempt = NOW() WHERE username = ?");
    $stmt->bind_param("iis", $new_attempts, $is_blocked, $username);
    $stmt->execute();
    
    return ['attempts' => $new_attempts, 'is_blocked' => $is_blocked];
}

function resetFailedAttempts($conn, $username) {
    $stmt = $conn->prepare("UPDATE users SET failed_attempts = 0, is_blocked = 0, last_failed_attempt = NULL WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
}

if (isset($_GET['ajax_action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['ajax_action'] === 'captcha_failed') {
        $result = incrementFailedAttempts($conn, 'admin');
        echo json_encode([
            'success' => true, 
            'total_failed_attempts' => $result['attempts'],
            'is_blocked' => $result['is_blocked'] == 1
        ]);
        exit;
    }
    
    if ($_GET['ajax_action'] === 'captcha_success') {
        $_SESSION['captcha_passed'] = true;
        echo json_encode(['success' => true, 'captcha_passed' => true]);
        exit;
    }
    
    if ($_GET['ajax_action'] === 'get_status') {
        $status = getFailedAttemptsFromDB($conn, 'admin');
        echo json_encode([
            'captcha_passed' => $_SESSION['captcha_passed'] ?? false,
            'captcha_blocked' => $status['is_blocked'] == 1,
            'total_failed_attempts' => $status['attempts']
        ]);
        exit;
    }
}

if (!isset($_SESSION['init_done'])) {
    $_SESSION['init_done'] = true;
    $_SESSION['captcha_passed'] = false;
}

$errors = [];
$captcha_passed = $_SESSION['captcha_passed'] === true;
$db_status = getFailedAttemptsFromDB($conn, 'admin');
$total_failed_attempts = $db_status['attempts'];
$is_blocked = $db_status['is_blocked'] == 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($is_blocked) {
        $errors[] = "Вы заблокированы. Обратитесь к администратору";
    } elseif (!$captcha_passed) {
        $errors[] = "Пожалуйста, пройдите проверку (соберите пазл)";
    }
    
    if (empty($username)) $errors[] = "Введите имя пользователя";
    if (empty($password)) $errors[] = "Введите пароль";
    
    if (empty($errors) && $captcha_passed && !$is_blocked) {
        $stmt = $conn->prepare("SELECT id, username, password, role, is_blocked FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user && $user['password'] === $password) {
            resetFailedAttempts($conn, $username);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['logged_in'] = true;
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['captcha_passed'] = false;
            
            $_SESSION['login_success'] = "Вы успешно авторизовались";
            header("Location: index.php");
            exit;
        } else {
            $result = incrementFailedAttempts($conn, $username);
            $errors[] = "Вы ввели неверный логин или пароль. Пожалуйста проверьте ещё раз введенные данные";
            
            $_SESSION['captcha_passed'] = false;
            $captcha_passed = false;
            
            $db_status = getFailedAttemptsFromDB($conn, 'admin');
            $total_failed_attempts = $db_status['attempts'];
            $is_blocked = $db_status['is_blocked'] == 1;
        }
    }
}

if (isset($_GET['reset_all'])) {
    $stmt = $conn->prepare("UPDATE users SET is_blocked = 0, failed_attempts = 0");
    $stmt->execute();
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, <?= THEME_COLOR_PRIMARY ?> 0%, <?= THEME_COLOR_SECONDARY ?> 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .container { max-width: 420px; width: 100%; }
        .card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        h1 { color: #2c3e50; font-size: 1.5rem; font-weight: 600; }
        .subtitle { color: #7f8c8d; font-size: 0.85rem; }
        .captcha-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        .captcha-status {
            padding: 0.6rem;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .captcha-success { background: #d4edda; color: #155724; }
        .captcha-pending { background: #fff3cd; color: #856404; }
        .captcha-blocked { background: #f8d7da; color: #721c24; }
        .captcha-link {
            display: inline-block;
            padding: 0.6rem 1.25rem;
            background: <?= THEME_COLOR_PRIMARY ?>;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        .captcha-link:hover { opacity: 0.9; }
        .input-group { margin-bottom: 1rem; }
        .input-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        .input-group input:focus {
            outline: none;
            border-color: <?= THEME_COLOR_PRIMARY ?>;
            background: white;
        }
        .input-group input:disabled { background: #e9ecef; cursor: not-allowed; }
        .login-btn {
            width: 100%;
            padding: 0.75rem;
            background: <?= THEME_COLOR_PRIMARY ?>;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
        }
        .login-btn:hover { opacity: 0.9; }
        .login-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .alert {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .attempts-info {
            font-size: 0.8rem;
            text-align: center;
            margin-top: 0.5rem;
            color: #6c757d;
        }
        .reset-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            font-size: 0.75rem;
            color: <?= THEME_COLOR_PRIMARY ?>;
            text-decoration: none;
        }
        .auto-check-info {
            font-size: 0.7rem;
            text-align: center;
            margin-top: 0.5rem;
            color: #95a5a6;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
            margin-top: 1rem;
            text-align: center;
        }
        .btn-secondary:hover { background: #5a6268; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <h1>Вход в систему</h1>
            <p class="subtitle"><?= SITE_NAME ?> - <?= SITE_SUBTITLE ?></p>
        </div>
        
        <?php foreach ($errors as $error): ?>
            <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
        
        <div class="captcha-box" id="captchaBox">
            <strong>Проверка безопасности</strong>
            <div id="captchaStatus">
                <?php if ($is_blocked): ?>
                    <div class="captcha-status captcha-blocked">ДОСТУП ЗАБЛОКИРОВАН</div>
                    <div style="color: #e74c3c; font-size: 0.85rem;">Превышено количество попыток (3). Обратитесь к администратору.</div>
                <?php elseif ($captcha_passed): ?>
                    <div class="captcha-status captcha-success">Проверка пройдена</div>
                    <div style="color: #27ae60; font-size: 0.85rem;">Капча пройдена! Теперь можно ввести данные.</div>
                <?php else: ?>
                    <div class="captcha-status captcha-pending">Требуется собрать пазл</div>
                    <a href="captcha_puzzle.php" class="captcha-link" target="_blank">Пройти проверку</a>
                <?php endif; ?>
            </div>
        </div>
        
        <form method="POST" id="loginForm">
            <div class="input-group">
                <input type="text" name="username" id="username" placeholder="Логин" <?= (!$captcha_passed || $is_blocked) ? 'disabled' : '' ?>>
            </div>
            <div class="input-group">
                <input type="password" name="password" id="password" placeholder="Пароль" <?= (!$captcha_passed || $is_blocked) ? 'disabled' : '' ?>>
            </div>
            <button type="submit" id="loginBtn" class="login-btn" <?= (!$captcha_passed || $is_blocked) ? 'disabled' : '' ?>>Войти</button>
        </form>
        
        <div class="attempts-info" id="attemptsInfo">
            Неудачных попыток: <?= $total_failed_attempts ?> / 3
        </div>
        
        <div class="auto-check-info">Статус капчи обновляется автоматически</div>
        
        <?php if ($is_blocked): ?>
            <a href="?reset_all=1" class="reset-link">Сбросить блокировку</a>
        <?php endif; ?>
    </div>
</div>

<script>
function updateCaptchaStatus() {
    fetch('login.php?ajax_action=get_status')
        .then(r => r.json())
        .then(data => {
            const attemptsInfo = document.getElementById('attemptsInfo');
            if (attemptsInfo) {
                attemptsInfo.innerHTML = 'Неудачных попыток: ' + data.total_failed_attempts + ' / 3';
            }
            
            const captchaStatus = document.getElementById('captchaStatus');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const loginBtn = document.getElementById('loginBtn');
            
            if (data.captcha_blocked) {
                captchaStatus.innerHTML = '<div class="captcha-status captcha-blocked">ДОСТУП ЗАБЛОКИРОВАН</div><div style="color: #e74c3c; font-size: 0.85rem;">Превышено количество попыток (3). Обратитесь к администратору.</div>';
                usernameInput.disabled = true;
                passwordInput.disabled = true;
                loginBtn.disabled = true;
            } 
            else if (data.captcha_passed) {
                captchaStatus.innerHTML = '<div class="captcha-status captcha-success">Проверка пройдена</div><div style="color: #27ae60; font-size: 0.85rem;">Капча пройдена! Теперь можно ввести данные.</div>';
                usernameInput.disabled = false;
                passwordInput.disabled = false;
                loginBtn.disabled = false;
            } 
            else {
                captchaStatus.innerHTML = '<div class="captcha-status captcha-pending">Требуется собрать пазл</div><a href="captcha_puzzle.php" class="captcha-link" target="_blank">Пройти проверку</a>';
                usernameInput.disabled = true;
                passwordInput.disabled = true;
                loginBtn.disabled = true;
            }
        })
        .catch(e => console.error(e));
}

setInterval(updateCaptchaStatus, 2000);
updateCaptchaStatus();
</script>
</body>
</html>