<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';
require_once 'config.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        
        if (empty($username) || empty($password)) {
            $message = "Заполните все поля!";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $message = "Пользователь с логином '{$username}' уже существует!";
                $message_type = "error";
            } else {
                $stmt = $conn->prepare("INSERT INTO users (username, password, role, is_blocked, failed_attempts) VALUES (?, ?, ?, 0, 0)");
                $stmt->bind_param("sss", $username, $password, $role);
                if ($stmt->execute()) {
                    $message = "Пользователь '{$username}' успешно добавлен!";
                    $message_type = "success";
                    header("Location: admin_users.php");
                    exit;
                } else {
                    $message = "Ошибка при добавлении пользователя!";
                    $message_type = "error";
                }
            }
        }
    }
    
    elseif ($action === 'edit') {
        $user_id = intval($_POST['user_id']);
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        $is_blocked = isset($_POST['is_blocked']) ? 1 : 0;
        
        if (empty($username)) {
            $message = "Имя пользователя не может быть пустым!";
            $message_type = "error";
        } else {
            if (!empty($password)) {
                $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, role = ?, is_blocked = ? WHERE id = ?");
                $stmt->bind_param("sssii", $username, $password, $role, $is_blocked, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ?, role = ?, is_blocked = ? WHERE id = ?");
                $stmt->bind_param("ssii", $username, $role, $is_blocked, $user_id);
            }
            
            if ($stmt->execute()) {
                $message = "Данные пользователя обновлены!";
                $message_type = "success";
                header("Location: admin_users.php");
                exit;
            } else {
                $message = "Ошибка при обновлении данных!";
                $message_type = "error";
            }
        }
    }
    
    elseif ($action === 'delete') {
        $user_id = intval($_POST['user_id']);
        
        if ($user_id == $_SESSION['user_id']) {
            $message = "Нельзя удалить самого себя!";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $message = "Пользователь удален!";
                $message_type = "success";
                header("Location: admin_users.php");
                exit;
            } else {
                $message = "Ошибка при удалении!";
                $message_type = "error";
            }
        }
    }
    
    elseif ($action === 'unblock') {
        $user_id = intval($_POST['user_id']);
        $stmt = $conn->prepare("UPDATE users SET is_blocked = 0, failed_attempts = 0 WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $message = "Пользователь разблокирован!";
            $message_type = "success";
            header("Location: admin_users.php");
            exit;
        } else {
            $message = "Ошибка при разблокировке!";
            $message_type = "error";
        }
    }
}

$users = [];
$result = $conn->query("SELECT id, username, role, is_blocked, failed_attempts FROM users ORDER BY id");
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление пользователями</title>
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
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
            flex-wrap: wrap;
            gap: 1rem;
        }
        h1 { color: #2c3e50; font-size: 1.5rem; }
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: <?= THEME_COLOR_PRIMARY ?>;
            color: white;
        }
        .btn-primary:hover { opacity: 0.9; }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover { background: #218838; }
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover { background: #c82333; }
        .form-group { margin-bottom: 10px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 14px; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .row {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .row .form-group { flex: 1; min-width: 150px; }
        .message {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            display: inline-block;
        }
        .badge-admin { background: <?= THEME_COLOR_PRIMARY ?>; color: white; }
        .badge-user { background: #6c757d; color: white; }
        .badge-blocked { background: #dc3545; color: white; }
        .badge-active { background: #28a745; color: white; }
        .actions { display: flex; gap: 5px; flex-wrap: wrap; }
        .edit-row { display: none; background: #f9f9f9; }
        .edit-form { padding: 15px; background: #f8f9fa; border-radius: 4px; }
        .inline-form { display: inline; }
        .table-container { overflow-x: auto; }
        .btn-group { display: flex; gap: 1rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <h1>Управление пользователями</h1>
            <div class="btn-group">
                <a href="index.php" class="btn btn-secondary">На главную</a>
                <a href="logout.php" class="btn btn-danger">Выйти</a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="card" style="padding: 0 0 1.5rem 0; margin-bottom: 1.5rem;">
            <h2 style="margin-bottom: 1rem;">Добавить пользователя</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="row">
                    <div class="form-group">
                        <label>Логин</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Пароль</label>
                        <input type="text" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Роль</label>
                        <select name="role">
                            <option value="user">Пользователь</option>
                            <option value="admin">Администратор</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Добавить</button>
                    </div>
                </div>
            </form>
        </div>
        
        <h2 style="margin-bottom: 1rem;">Список пользователей</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Логин</th>
                        <th>Роль</th>
                        <th>Статус</th>
                        <th>Попыток</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr id="user-row-<?= $user['id'] ?>">
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td>
                                <span class="badge <?= $user['role'] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                                    <?= $user['role'] === 'admin' ? 'Администратор' : 'Пользователь' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $user['is_blocked'] ? 'badge-blocked' : 'badge-active' ?>">
                                    <?= $user['is_blocked'] ? 'Заблокирован' : 'Активен' ?>
                                </span>
                            </td>
                            <td><?= $user['failed_attempts'] ?></td>
                            <td class="actions">
                                <button class="btn btn-warning" onclick="toggleEdit(<?= $user['id'] ?>)">Редакт</button>
                                
                                <?php if ($user['is_blocked']): ?>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="action" value="unblock">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-success">Разбл</button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" class="inline-form" onsubmit="return confirm('Удалить пользователя <?= htmlspecialchars($user['username']) ?>?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-danger">Удалить</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr class="edit-row" id="edit-row-<?= $user['id'] ?>">
                            <td colspan="6">
                                <div class="edit-form">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <div class="row">
                                            <div class="form-group">
                                                <label>Логин</label>
                                                <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Новый пароль</label>
                                                <input type="text" name="password" placeholder="Оставьте пустым">
                                            </div>
                                            <div class="form-group">
                                                <label>Роль</label>
                                                <select name="role">
                                                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Пользователь</option>
                                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Администратор</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>
                                                    <input type="checkbox" name="is_blocked" <?= $user['is_blocked'] ? 'checked' : '' ?>>
                                                    Заблокирован
                                                </label>
                                            </div>
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-primary">Сохранить</button>
                                                <button type="button" class="btn btn-secondary" onclick="toggleEdit(<?= $user['id'] ?>)">Отмена</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleEdit(userId) {
    var editRow = document.getElementById('edit-row-' + userId);
    if (editRow.style.display === 'none' || editRow.style.display === '') {
        editRow.style.display = 'table-row';
    } else {
        editRow.style.display = 'none';
    }
}
</script>
</body>
</html>