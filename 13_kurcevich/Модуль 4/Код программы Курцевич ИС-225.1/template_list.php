<?php
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';  
require_once 'config.php';

$table_name = $_GET['table'] ?? '';
$config = getTableConfig($table_name);

if (!$config) {
    header("Location: index.php");
    exit;
}

$columns = $config['columns'];
$joins = $config['joins'] ?? [];
$title = $config['title'];

$select_columns = ['t.id'];
foreach ($columns as $col => $label) {
    if ($col === 'id') continue;
    if (isset($joins[$col])) {
        $join = $joins[$col];
        $select_columns[] = "j_{$col}.{$join['display_column']} as {$col}";
    } else {
        $select_columns[] = "t.{$col}";
    }
}

$sql = "SELECT " . implode(', ', $select_columns) . " FROM {$table_name} t";
foreach ($joins as $col => $join) {
    $sql .= " LEFT JOIN {$join['table']} j_{$col} ON t.{$join['foreign_key']} = j_{$col}.id";
}
$sql .= " ORDER BY t.id DESC";

$result = $conn->query($sql);
$items = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?> - <?= SITE_NAME ?></title>
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
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        tr:hover { background: #f8f9fa; }
        .actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
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
        .empty-row { text-align: center; padding: 2rem; color: #6c757d; }
        .table-container { overflow-x: auto; }
        .btn-group { display: flex; gap: 1rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <h1><?= $title ?></h1>
            <div class="btn-group">
                <a href="routes.php?table=<?= $table_name ?>&action=add" class="btn btn-primary">Добавить</a>
                <a href="index.php" class="btn btn-secondary">На главную</a>
            </div>
        </div>
        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <?php foreach ($columns as $col => $label): ?>
                            <th><?= $label ?></th>
                        <?php endforeach; ?>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="<?= count($columns) + 1 ?>" class="empty-row">Нет данных</td></tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <?php foreach ($columns as $col => $label): ?>
                                    <td><?= htmlspecialchars($item[$col] ?? '-') ?></td>
                                <?php endforeach; ?>
                                <td class="actions">
                                    <a href="routes.php?table=<?= $table_name ?>&action=edit&id=<?= $item['id'] ?>" class="btn btn-warning">Редакт</a>
                                    <a href="routes.php?table=<?= $table_name ?>&action=delete&id=<?= $item['id'] ?>" class="btn btn-danger" onclick="return confirm('Удалить?')">Удалить</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>