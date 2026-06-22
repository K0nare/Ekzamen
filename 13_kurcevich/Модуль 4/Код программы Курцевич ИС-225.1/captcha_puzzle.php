<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();

if (isset($_GET['reset'])) {
    unset($_SESSION['puzzle_positions']);
    unset($_SESSION['puzzle_correct']);
    header('Location: captcha_puzzle.php');
    exit;
}

if (!isset($_SESSION['puzzle_positions']) || isset($_GET['refresh'])) {
    $positions = [];
    $possiblePositions = [[20, 20], [140, 20], [20, 60], [140, 60]];
    shuffle($possiblePositions);
    
    for ($i = 1; $i <= 4; $i++) {
        $pos = array_pop($possiblePositions);
        $positions[] = ['id' => $i, 'x' => $pos[0], 'y' => $pos[1]];
    }
    
    $_SESSION['puzzle_positions'] = $positions;
    $_SESSION['puzzle_correct'] = [
        ['id' => 1, 'x' => 0, 'y' => 0],
        ['id' => 2, 'x' => 120, 'y' => 0],
        ['id' => 3, 'x' => 0, 'y' => 120],
        ['id' => 4, 'x' => 120, 'y' => 120]
    ];
}
$positions = $_SESSION['puzzle_positions'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    
    $input = file_get_contents('php://input');
    parse_str($input, $post_data);
    
    if (!isset($post_data['check_puzzle']) || !isset($post_data['positions'])) {
        echo json_encode(['success' => false, 'message' => 'Неверные данные']);
        exit;
    }
    
    $user_positions = json_decode($post_data['positions'], true);
    $correct_positions = $_SESSION['puzzle_correct'] ?? [];
    
    $is_correct = true;
    $placed_count = 0;
    
    if (is_array($user_positions)) {
        foreach ($user_positions as $pos) {
            if (!isset($pos['parent']) || $pos['parent'] !== 'puzzleArea') {
                $is_correct = false;
                continue;
            }
            
            $found = false;
            foreach ($correct_positions as $correct) {
                if ($pos['id'] == $correct['id'] && 
                    abs(($pos['x'] ?? 0) - $correct['x']) <= 40 && 
                    abs(($pos['y'] ?? 0) - $correct['y']) <= 40) {
                    $found = true;
                    $placed_count++;
                    break;
                }
            }
            if (!$found) $is_correct = false;
        }
    } else {
        $is_correct = false;
    }
    
    if ($is_correct && $placed_count === 4) {
        echo json_encode(['success' => true, 'message' => 'Пазл собран правильно!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Пазл собран неправильно!']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Проверка - Пазл</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }
        .container {
            background: #ffffff;
            padding: 2rem;
            border-radius: 16px;
            text-align: center;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h2 { margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50; }
        .subtitle { color: #7f8c8d; margin-bottom: 1.5rem; font-size: 0.9rem; }
        .game-container { display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap; }
        .puzzle-area {
            width: 240px;
            height: 240px;
            border: 3px solid #667eea;
            border-radius: 12px;
            position: relative;
            background: #f0f0f0;
            overflow: hidden;
        }
        .spawn-area {
            width: 280px;
            height: 200px;
            border: 2px dashed #bdc3c7;
            border-radius: 12px;
            position: relative;
            background: #fafafa;
            overflow: hidden;
        }
        .zone {
            position: absolute;
            width: 120px;
            height: 120px;
            border: 2px dashed #ddd;
            transition: all 0.2s;
            background: rgba(200,200,200,0.1);
        }
        .zone:nth-child(1) { top: 0; left: 0; }
        .zone:nth-child(2) { top: 0; left: 120px; }
        .zone:nth-child(3) { top: 120px; left: 0; }
        .zone:nth-child(4) { top: 120px; left: 120px; }
        .zone.active { background: rgba(102,126,234,0.2); border-color: #667eea; border-style: solid; }
        .puzzle-piece {
            position: absolute;
            width: 120px;
            height: 120px;
            cursor: grab;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .puzzle-piece:active { cursor: grabbing; }
        .puzzle-piece img { width: 100%; height: 100%; object-fit: cover; pointer-events: none; }
        .puzzle-piece.correct { border: 3px solid #27ae60; }
        .button-container { display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem; }
        button {
            padding: 0.7rem 1.5rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        button:hover { background: #5a67d8; }
        .reset-btn { background: #95a5a6; }
        .reset-btn:hover { background: #7f8c8d; }
        .warning-text { margin-top: 1rem; padding: 0.5rem; background: #fff3cd; border-radius: 8px; font-size: 0.8rem; color: #856404; }
        .loading { display: none; margin-top: 1rem; color: #667eea; }
        .info-text { margin-top: 1rem; font-size: 0.75rem; color: #95a5a6; }
    </style>
</head>
<body>
<div class="container">
    <h2>🧩 Соберите пазл</h2>
    <p class="subtitle">Перетащите фрагменты в правильные позиции</p>
    
    <div class="game-container">
        <div class="puzzle-area" id="puzzleArea">
            <div class="zone"></div><div class="zone"></div>
            <div class="zone"></div><div class="zone"></div>
        </div>
        <div class="spawn-area" id="spawnArea">
            <?php foreach ($positions as $piece): 
                $imgPath = "captcha/puzzle{$piece['id']}.png";
                if (file_exists($imgPath)): ?>
                    <div class="puzzle-piece" id="piece_<?= $piece['id'] ?>" 
                         style="left: <?= $piece['x'] ?>px; top: <?= $piece['y'] ?>px;" draggable="true">
                        <img src="<?= $imgPath ?>" alt="Фрагмент <?= $piece['id'] ?>">
                    </div>
                <?php else: ?>
                    <div class="puzzle-piece" id="piece_<?= $piece['id'] ?>" 
                         style="left: <?= $piece['x'] ?>px; top: <?= $piece['y'] ?>px; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; color: white;" draggable="true">
                        <?= $piece['id'] ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="warning-text">
        ⚡ При нажатии "Подтвердить" будет проверена правильность сборки.<br>
        При неправильной сборке засчитывается неудачная попытка!
    </div>
    
    <input type="hidden" name="positions" id="positions">
    <div class="button-container">
        <button type="button" id="submitBtn">✅ Подтвердить сборку</button>
        <button type="button" id="resetBtn" class="reset-btn">🔄 Сбросить</button>
    </div>
    <div class="loading" id="loading">Проверка...</div>
    <div class="info-text">💡 Перетащите каждый фрагмент в подсвеченную зону</div>
</div>

<script>
    const pieces = document.querySelectorAll('.puzzle-piece');
    const puzzleArea = document.getElementById('puzzleArea');
    const spawnArea = document.getElementById('spawnArea');
    const submitBtn = document.getElementById('submitBtn');
    const resetBtn = document.getElementById('resetBtn');
    const positionsInput = document.getElementById('positions');
    const zones = document.querySelectorAll('.zone');
    const loading = document.getElementById('loading');
    
    let draggedPiece = null;
    let initialX, initialY;
    
    async function sendResult(success) {
        const action = success ? 'captcha_success' : 'captcha_failed';
        const response = await fetch(`login.php?ajax_action=${action}`);
        return await response.json();
    }
    
    function dragStart(e) {
        draggedPiece = this;
        const rect = this.getBoundingClientRect();
        initialX = e.clientX - rect.left;
        initialY = e.clientY - rect.top;
        zones.forEach(z => z.classList.add('active'));
        e.dataTransfer.setData('text/plain', this.id);
    }
    
    function dragEnd() {
        zones.forEach(z => z.classList.remove('active'));
        updatePositions();
        draggedPiece = null;
    }
    
    pieces.forEach(piece => {
        piece.setAttribute('draggable', 'true');
        piece.addEventListener('dragstart', dragStart);
        piece.addEventListener('dragend', dragEnd);
    });
    
    puzzleArea.addEventListener('dragover', (e) => e.preventDefault());
    spawnArea.addEventListener('dragover', (e) => e.preventDefault());
    
    puzzleArea.addEventListener('drop', (e) => {
        e.preventDefault();
        if (!draggedPiece) return;
        
        const rect = puzzleArea.getBoundingClientRect();
        let newX = e.clientX - rect.left - initialX;
        let newY = e.clientY - rect.top - initialY;
        newX = Math.max(0, Math.min(120, newX));
        newY = Math.max(0, Math.min(120, newY));
        
        const id = parseInt(draggedPiece.id.replace('piece_', ''));
        const threshold = 60;
        
        if (id === 1 && newX < threshold && newY < threshold) { newX = 0; newY = 0; }
        else if (id === 2 && Math.abs(newX - 120) < threshold && newY < threshold) { newX = 120; newY = 0; }
        else if (id === 3 && newX < threshold && Math.abs(newY - 120) < threshold) { newX = 0; newY = 120; }
        else if (id === 4 && Math.abs(newX - 120) < threshold && Math.abs(newY - 120) < threshold) { newX = 120; newY = 120; }
        
        draggedPiece.style.left = newX + 'px';
        draggedPiece.style.top = newY + 'px';
        puzzleArea.appendChild(draggedPiece);
        updatePositions();
    });
    
    spawnArea.addEventListener('drop', (e) => {
        e.preventDefault();
        if (!draggedPiece) return;
        
        const rect = spawnArea.getBoundingClientRect();
        let newX = e.clientX - rect.left - initialX;
        let newY = e.clientY - rect.top - initialY;
        newX = Math.max(0, Math.min(160, newX));
        newY = Math.max(0, Math.min(80, newY));
        
        draggedPiece.style.left = newX + 'px';
        draggedPiece.style.top = newY + 'px';
        spawnArea.appendChild(draggedPiece);
        updatePositions();
    });
    
    function updatePositions() {
        const positions = [];
        pieces.forEach(piece => {
            const id = parseInt(piece.id.replace('piece_', ''));
            const parent = piece.parentElement.id;
            const x = parseInt(piece.style.left) || 0;
            const y = parseInt(piece.style.top) || 0;
            
            if (parent === 'puzzleArea') {
                const correctX = (id - 1) % 2 * 120;
                const correctY = Math.floor((id - 1) / 2) * 120;
                if (Math.abs(x - correctX) <= 40 && Math.abs(y - correctY) <= 40) {
                    piece.classList.add('correct');
                } else {
                    piece.classList.remove('correct');
                }
            } else {
                piece.classList.remove('correct');
            }
            positions.push({ id, x, y, parent });
        });
        positionsInput.value = JSON.stringify(positions);
    }
    
    async function checkPuzzle() {
        const positions = positionsInput.value;
        if (!positions) { alert('Ошибка получения позиций'); return; }
        
        loading.style.display = 'block';
        submitBtn.disabled = true;
        
        try {
            const formData = new URLSearchParams();
            formData.append('check_puzzle', '1');
            formData.append('positions', positions);
            
            const response = await fetch('captcha_puzzle.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData.toString()
            });
            
            const result = await response.json();
            const loginResult = await sendResult(result.success);
            
            if (result.success) {
                alert('✅ ' + result.message);
                if (window.opener) {
                    window.opener.updateCaptchaStatus();
                }
                window.close();
            } else {
                alert('❌ ' + result.message + '\nПопыток: ' + (loginResult.total_failed_attempts || '?') + '/3');
                if (loginResult.is_blocked) {
                    alert('🔒 Вы заблокированы!');
                    if (window.opener) {
                        window.opener.updateCaptchaStatus();
                    }
                    window.close();
                } else {
                    window.location.href = 'captcha_puzzle.php?refresh=1';
                }
            }
        } catch(error) {
            console.error('Ошибка:', error);
            alert('Ошибка: ' + error.message);
        } finally {
            loading.style.display = 'none';
            submitBtn.disabled = false;
        }
    }
    
    resetBtn.addEventListener('click', () => {
        if (confirm('Сбросить пазл?')) window.location.href = 'captcha_puzzle.php?refresh=1';
    });
    
    submitBtn.addEventListener('click', checkPuzzle);
    updatePositions();
</script>
</body>
</html>