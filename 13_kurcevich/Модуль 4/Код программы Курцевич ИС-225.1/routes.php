<?php
// ========== ФАЙЛ МАРШРУТИЗАЦИИ ==========

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';
require_once 'config.php';

// ↓↓↓ ЭТА СТРОКА ДЛЯ IDE (УБИРАЕТ ПРЕДУПРЕЖДЕНИЕ) ↓↓↓
/** @var callable $getTableConfig Функция из config.php */
global $TABLE_CONFIGS;

$table = $_GET['table'] ?? '';
$action = $_GET['action'] ?? 'list';

$config = getTableConfig($table);

if (!$config) {
    header("Location: index.php");
    exit;
}

if ($config['admin_only'] && $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($action === 'list') {
    include 'template_list.php';
} elseif ($action === 'add' || $action === 'edit' || $action === 'delete') {
    include 'template_form.php';
} else {
    header("Location: index.php");
    exit;
}
?>