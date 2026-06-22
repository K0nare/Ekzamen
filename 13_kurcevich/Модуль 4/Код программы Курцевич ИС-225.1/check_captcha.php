<?php
session_start();
header('Content-Type: application/json');

// Проверяем статус капчи
$captcha_passed = isset($_SESSION['captcha_passed']) && $_SESSION['captcha_passed'] === true;
$captcha_blocked = isset($_SESSION['captcha_blocked']) && $_SESSION['captcha_blocked'] === true;

echo json_encode([
    'captcha_passed' => $captcha_passed && !$captcha_blocked,
    'captcha_blocked' => $captcha_blocked,
    'puzzle_attempts' => $_SESSION['puzzle_attempts'] ?? 0,
    'timestamp' => time()
]);
?>