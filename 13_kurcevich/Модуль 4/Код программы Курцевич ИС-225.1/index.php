<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'config.php';
require_once 'db_connect.php';

$success_message = $_SESSION['login_success'] ?? '';
unset($_SESSION['login_success']);

$username = $_SESSION['username'];
$role = $_SESSION['user_role'];

// Получаем меню из конфига
$menu_items = getMainMenu();

// Для отладки - можно раскомментировать
// var_dump($menu_items);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - Главная</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, <?= THEME_COLOR_PRIMARY ?> 0%, <?= THEME_COLOR_SECONDARY ?> 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
            flex-wrap: wrap;
            gap: 1rem;
        }
        h1 { color: #2c3e50; font-size: 1.5rem; }
        .user-info {
            background: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .logout-btn {
            padding: 0.5rem 1rem;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }
        .logout-btn:hover { background: #c0392b; }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #28a745;
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }
        .menu-item {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.2s;
            border: 1px solid #e9ecef;
            display: block;
        }
        .menu-item:hover {
            transform: translateY(-3px);
            border-color: <?= THEME_COLOR_PRIMARY ?>;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            background: white;
        }
        .menu-title { font-size: 1rem; font-weight: 500; }
        .admin-panel {
            margin-top: 1.5rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 12px;
        }
        .admin-link {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: <?= THEME_COLOR_PRIMARY ?>;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 0.5rem;
        }
        .admin-link:hover { opacity: 0.9; }
        .welcome { font-size: 1.1rem; margin-bottom: 1rem; }
        .no-menu {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
            background: #f8f9fa;
            border-radius: 12px;
            margin: 1.5rem 0;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <h1><?= SITE_NAME ?> - <?= SITE_SUBTITLE ?></h1>
            <div class="user-info">
                <span>👤 <?= htmlspecialchars($username) ?></span>
                <span><?= $role === 'admin' ? 'Администратор' : 'Пользователь' ?></span>
                <a href="logout.php" class="logout-btn">Выйти</a>
            </div>
        </div>
        
        <?php if ($success_message): ?>
            <div class="success-message"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        
        <div class="welcome">
            Добро пожаловать, <strong><?= htmlspecialchars($username) ?></strong>!
        </div>
        
        <!-- МЕНЮ -->
        <?php if (!empty($menu_items)): ?>
            <div class="menu-grid">
                <?php foreach ($menu_items as $item): ?>
                    <a href="<?= $item['url'] ?>" class="menu-item">
                        <div class="menu-title"><?= $item['title'] ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-menu">
                <p>Меню не настроено. Проверьте config.php</p>
                <p style="font-size: 0.8rem; margin-top: 0.5rem;">Убедитесь, что в $TABLE_CONFIGS есть таблицы</p>
            </div>
        <?php endif; ?>
        
        <!-- АДМИН-ПАНЕЛЬ -->
        <?php if ($role === 'admin'): ?>
            <div class="admin-panel">
                <h3>Админ-панель</h3>
                <p style="margin: 0.5rem 0 1rem 0;">Управление пользователями системы:</p>
                <a href="admin_users.php" class="admin-link">Управление пользователями</a>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>