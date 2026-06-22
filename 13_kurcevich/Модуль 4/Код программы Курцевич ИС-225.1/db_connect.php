<?php
// db_connect.php
function getDBConnection() {
    $host = '134.90.167.42';
    $port = '10306';
    $db = 'project_Chumakov';
    $user = 'Chumakov';
    $pass = 'CJf-0P';

    $conn = new mysqli($host, $user, $pass, $db, $port);

    if ($conn->connect_error) {
        error_log("Ошибка подключения к базе данных: " . $conn->connect_error);
        die("Ошибка подключения к базе данных. Пожалуйста, проверьте логи.");
    }

    $conn->set_charset("utf8");
    return $conn;
}

// Для обратной совместимости, создаем глобальную переменную $conn
$conn = getDBConnection();

function sql_escape($conn, $data) {
    return $conn->real_escape_string(htmlspecialchars(trim($data)));
}
?>