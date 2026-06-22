<?php
require_once 'db_connect.php';

$sql = "SELECT id, name, snils, phone FROM customer LIMIT 5";
$result = $conn->query($sql);

echo "<h2>Проверка подключения к БД</h2>";

if ($result) {
    echo "<p>✅ Подключение работает. Найдено записей: " . $result->num_rows . "</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>INN</th><th>Phone</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['inn']) . "</td>";
        echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Ошибка запроса: " . $conn->error . "</p>";
}
?>