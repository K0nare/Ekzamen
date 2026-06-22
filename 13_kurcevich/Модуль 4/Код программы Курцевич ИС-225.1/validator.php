<?php
require_once 'db_connect.php';

function validateSnils($snils) {
    if (empty($snils) || trim($snils) === '') {
        return ['valid' => null, 'message' => 'СНИЛС не указан'];
    }
    
    $clean = preg_replace('/[\s\-]/', '', $snils);
    
    if (!preg_match('/^\d+$/', $clean)) {
        return ['valid' => false, 'message' => 'СНИЛС должен содержать только цифры'];
    }
    
    if (strlen($clean) !== 11) {
        return ['valid' => false, 'message' => 'СНИЛС должен быть 11 цифр'];
    }
    
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += intval($clean[$i]) * (9 - $i);
    }
    
    $checkDigit = intval(substr($clean, 9, 2));
    $expected = ($sum % 101) % 100;
    
    if ($expected === 0 || $expected === 100) {
        $expected = 0;
    }
    
    if ($checkDigit !== $expected) {
        return ['valid' => false, 'message' => 'Неверное контрольное число СНИЛС'];
    }
    
    return ['valid' => true, 'message' => 'СНИЛС корректный'];
}

$sql = "SELECT id, name, snils, phone FROM customer ORDER BY name";
$result = $conn->query($sql);

$customers = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
}

$validCount = 0;
$invalidCount = 0;
$emptyCount = 0;

foreach ($customers as $customer) {
    $res = validateSnils($customer['snils']);
    if ($res['valid'] === true) $validCount++;
    elseif ($res['valid'] === false) $invalidCount++;
    else $emptyCount++;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Валидация СНИЛС клиентов</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #43A047 0%, #1B5E20 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { color: #2c3e50; margin-bottom: 0.5rem; }
        .subtitle { color: #6c757d; margin-bottom: 1.5rem; }
        .stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #e9ecef;
            border-radius: 8px;
        }
        .stat { text-align: center; flex: 1; }
        .stat-number { font-size: 1.8rem; font-weight: bold; }
        .stat-label { font-size: 0.8rem; color: #6c757d; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            font-size: 0.85rem;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th { background: #f8f9fa; }
        .valid-snils { background: #d4edda; }
        .invalid-snils { background: #f8d7da; }
        .empty-snils { background: #fff3cd; }
        .btn-group { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
        button {
            background: #6c757d;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        button:hover { opacity: 0.9; }
        .table-container {
            max-height: 500px;
            overflow-y: auto;
        }
        .footer {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
            font-size: 0.75rem;
            color: #6c757d;
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
<div class="container">
    <h1>Валидация СНИЛС клиентов</h1>
    <p class="subtitle">Проверка корректности СНИЛС (11 цифр, контрольная сумма)</p>
    
    <div class="stats">
        <div class="stat"><div class="stat-number"><?= count($customers) ?></div><div class="stat-label">Всего клиентов</div></div>
        <div class="stat"><div class="stat-number" style="color:#28a745;"><?= $validCount ?></div><div class="stat-label">СНИЛС корректный</div></div>
        <div class="stat"><div class="stat-number" style="color:#dc3545;"><?= $invalidCount ?></div><div class="stat-label">СНИЛС некорректный</div></div>
        <div class="stat"><div class="stat-number" style="color:#ffc107;"><?= $emptyCount ?></div><div class="stat-label">СНИЛС не указан</div></div>
    </div>
    
    <div class="btn-group">
        <button onclick="location.reload()">🔄 Обновить</button>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Наименование</th>
                    <th>СНИЛС</th>
                    <th>Телефон</th>
                    <th>Статус</th>
                    <th>Результат</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): 
                    $res = validateSnils($customer['snils']);
                ?>
                <tr class="<?= $res['valid'] === true ? 'valid-snils' : ($res['valid'] === false ? 'invalid-snils' : 'empty-snils') ?>">
                    <td><?= htmlspecialchars($customer['id']) ?></td>
                    <td><?= htmlspecialchars($customer['name']) ?></td>
                    <td><?= htmlspecialchars($customer['snils'] ?: '(не указан)') ?></td>
                    <td><?= htmlspecialchars($customer['phone'] ?: '-') ?></td>
                    <td>
                        <?php if ($res['valid'] === true): ?>
                            <span class="badge badge-success">✅ Корректный</span>
                        <?php elseif ($res['valid'] === false): ?>
                            <span class="badge badge-danger">❌ Некорректный</span>
                        <?php else: ?>
                            <span class="badge badge-warning">⚠️ Не указан</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $res['message'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding:2rem;">Нет данных о клиентах</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="footer">
        <strong>Критерии проверки СНИЛС:</strong><br>
        1. СНИЛС может быть пустым (необязательное поле)<br>
        2. Длина: 11 цифр<br>
        3. Проверка контрольной суммы
    </div>
</div>
</body>
</html>