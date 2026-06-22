<?php
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';
require_once 'config.php';

$table_name = $_GET['table'] ?? '';
$action = $_GET['action'] ?? 'add';
$item_id = $_GET['id'] ?? '';

$config = getTableConfig($table_name);

if (!$config) {
    header("Location: index.php");
    exit;
}

if ($config['admin_only'] && $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$title = $config['title'];
$fields = $config['form_fields'];
$message = '';
$message_type = '';
$form_data = [];

if ($action === 'edit' && !empty($item_id)) {
    $stmt = $conn->prepare("SELECT * FROM {$table_name} WHERE id = ?");
    $stmt->bind_param("s", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $form_data = $result->fetch_assoc();
    
    if (!$form_data) {
        header("Location: routes.php?table={$table_name}&action=list");
        exit;
    }
}

// Загрузка опций для select полей
$select_options = [];
foreach ($fields as $field_name => $field_config) {
    if ($field_config['type'] === 'select' && isset($field_config['options_table'])) {
        $options_table = $field_config['options_table'];
        $options_display = $field_config['options_display'];
        $options_value = $field_config['options_value'] ?? 'id';
        $result = $conn->query("SELECT {$options_value} as value, {$options_display} as name FROM {$options_table} ORDER BY name");
        if ($result) {
            $select_options[$field_name] = [];
            while ($row = $result->fetch_assoc()) {
                $select_options[$field_name][] = $row;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['action'] ?? '';
    
    if ($post_action === 'delete' && !empty($item_id)) {
        $stmt = $conn->prepare("DELETE FROM {$table_name} WHERE id = ?");
        $stmt->bind_param("s", $item_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Запись успешно удалена!";
            $_SESSION['message_type'] = "success";
            header("Location: routes.php?table={$table_name}&action=list");
            exit;
        }
    }
    
    if ($post_action === 'save') {
        $data = [];
        $errors = [];
        
        foreach ($fields as $field_name => $field_config) {
            $value = trim($_POST[$field_name] ?? '');
            
            if ($field_config['required'] && empty($value) && $value !== '0') {
                $errors[] = "Поле '{$field_config['label']}' обязательно для заполнения!";
            }
            
            if ($field_config['type'] === 'checkbox') {
                $value = isset($_POST[$field_name]) ? 1 : 0;
            }
            
            $data[$field_name] = $value;
        }
        
        if (empty($errors)) {
            if ($action === 'edit' && !empty($item_id)) {
                $set_parts = [];
                $params = [];
                $types = '';
                
                foreach ($data as $field => $value) {
                    $set_parts[] = "{$field} = ?";
                    $params[] = $value;
                    $types .= 's';
                }
                
                $params[] = $item_id;
                $types .= 's';
                
                $sql = "UPDATE {$table_name} SET " . implode(', ', $set_parts) . " WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Запись успешно обновлена!";
                    $_SESSION['message_type'] = "success";
                    header("Location: routes.php?table={$table_name}&action=list");
                    exit;
                } else {
                    $message = "Ошибка при обновлении!";
                    $message_type = "error";
                }
            } else {
                $columns = array_keys($data);
                $placeholders = array_fill(0, count($columns), '?');
                $values = array_values($data);
                $types = str_repeat('s', count($values));
                
                $sql = "INSERT INTO {$table_name} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$values);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Запись успешно добавлена!";
                    $_SESSION['message_type'] = "success";
                    header("Location: routes.php?table={$table_name}&action=list");
                    exit;
                } else {
                    $message = "Ошибка при добавлении!";
                    $message_type = "error";
                }
            }
        } else {
            $message = implode('<br>', $errors);
            $message_type = "error";
            $form_data = $data;
        }
    }
}

$is_edit = ($action === 'edit' && !empty($item_id));
$page_title = $is_edit ? "Редактирование" : "Добавление";
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?> <?= $title ?> - <?= SITE_NAME ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, <?= THEME_COLOR_PRIMARY ?> 0%, <?= THEME_COLOR_SECONDARY ?> 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container { max-width: 600px; margin: 0 auto; }
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
        .form-group { margin-bottom: 1.25rem; }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        .form-group label .required { color: #dc3545; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: <?= THEME_COLOR_PRIMARY ?>;
        }
        .form-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
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
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .checkbox-group input { width: auto; }
        .checkbox-group label { margin-bottom: 0; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <h1><?= $page_title ?> <?= $title ?></h1>
            <a href="routes.php?table=<?= $table_name ?>&action=list" class="btn btn-secondary">← Назад</a>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= $message ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="save">
            
            <?php foreach ($fields as $field_name => $field_config): ?>
                <div class="form-group">
                    <?php if ($field_config['type'] === 'checkbox'): ?>
                        <div class="checkbox-group">
                            <input type="checkbox" name="<?= $field_name ?>" value="1" id="cb_<?= $field_name ?>"
                                <?= (isset($form_data[$field_name]) && $form_data[$field_name]) ? 'checked' : '' ?>>
                            <label for="cb_<?= $field_name ?>">
                                <?= htmlspecialchars($field_config['label']) ?>
                                <?php if ($field_config['required']): ?><span class="required">*</span><?php endif; ?>
                            </label>
                        </div>
                    <?php elseif ($field_config['type'] === 'select' && isset($select_options[$field_name])): ?>
                        <label><?= htmlspecialchars($field_config['label']) ?>
                            <?php if ($field_config['required']): ?><span class="required">*</span><?php endif; ?>
                        </label>
                        <select name="<?= $field_name ?>" <?= $field_config['required'] ? 'required' : '' ?>>
                            <option value="">-- Выберите --</option>
                            <?php foreach ($select_options[$field_name] as $option): ?>
                                <option value="<?= $option['value'] ?>" 
                                    <?= (isset($form_data[$field_name]) && $form_data[$field_name] == $option['value']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($option['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($field_config['type'] === 'textarea'): ?>
                        <label><?= htmlspecialchars($field_config['label']) ?>
                            <?php if ($field_config['required']): ?><span class="required">*</span><?php endif; ?>
                        </label>
                        <textarea name="<?= $field_name ?>" rows="4"><?= htmlspecialchars($form_data[$field_name] ?? '') ?></textarea>
                    <?php else: ?>
                        <label><?= htmlspecialchars($field_config['label']) ?>
                            <?php if ($field_config['required']): ?><span class="required">*</span><?php endif; ?>
                        </label>
                        <input type="<?= $field_config['type'] ?>" name="<?= $field_name ?>" 
                            value="<?= htmlspecialchars($form_data[$field_name] ?? $field_config['default'] ?? '') ?>"
                            <?= $field_config['required'] ? 'required' : '' ?>
                            <?= isset($field_config['step']) ? 'step="' . $field_config['step'] . '"' : '' ?>>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <div class="form-buttons">
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="routes.php?table=<?= $table_name ?>&action=list" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>