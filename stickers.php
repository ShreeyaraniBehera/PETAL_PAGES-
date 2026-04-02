<?php
session_start();
if (!isset($_SESSION['user_id'])) header("Location: index.php");

$conn = new mysqli("localhost", "root", "", "petal_pages");

// Save sticker placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_stickers'])) {
    $user_id = $_SESSION['user_id'];
    $page_ref = $_POST['page_ref'];
    
    // Clear existing stickers for this page
    $stmt = $conn->prepare("DELETE FROM sticker_placements WHERE user_id = ? AND page_ref = ?");
    $stmt->bind_param("is", $user_id, $page_ref);
    $stmt->execute();
    
    // Save new placements
    if (isset($_POST['stickers'])) {
        foreach ($_POST['stickers'] as $sticker) {
            $stmt = $conn->prepare("INSERT INTO sticker_placements (user_id, page_ref, sticker_id, x_pos, y_pos, rotation, scale) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issiddd", $user_id, $page_ref, $sticker['file'], $sticker['x'], $sticker['y'], $sticker['rot'], $sticker['scale']);
            $stmt->execute();
        }
    }
    header("Location: stickers.php?success=Stickers saved!");
    exit();
}

// Load saved stickers for current page
$current_page = isset($_GET['page']) ? $_GET['page'] : 'diary';
$saved_stickers = [];
$stmt = $conn->prepare("SELECT * FROM sticker_placements WHERE user_id = ? AND page_ref = ?");
$stmt->bind_param("is", $_SESSION['user_id'], $current_page);
$stmt->execute();
$saved_result = $stmt->get_result();
while($row = $saved_result->fetch_assoc()) {
    $saved_stickers[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Petal Pages - Sticker Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:wght@400;700&family=Great+Vibes&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #F1DFDD; font-family: 'Poppins', sans-serif; }
        .script { font-family: 'Great Vibes', cursive; }
        
        .sidebar {
            width: 220px;
            height: 100vh;
            background: #4A202A;
            position: fixed;
            padding: 30px 20px;
            top: 0;
            left: 0;
            z-index: 100;
        }
        
        .sidebar a {
            display: block;
            margin: 15px 0;
            color: white;
            text-decoration: none;
            padding: 10px;
            border-radius: 10px;
            transition: 0.3s;
        }
        
        .sidebar a:hover { background: #D86487; }
        
        .main-content {
            margin-left: 240px;
            padding: 30px;
        }
        
        /* Page Header */
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h2 {
            font-size: 32px;
            color: #4A202A;
        }
        
        .page-header p {
            color: #999;
            margin-top: 5px;
        }
        
        /* Page Selector */
        .page-selector {
            background: white;
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .page-selector label {
            font-weight: 500;
            color: #4A202A;
        }
        
        .page-buttons {
            display: flex;
            gap: 10px;
        }
        
        .page-btn {
            padding: 8px 20px;
            border: 2px solid #D86487;
            background: white;
            color: #D86487;
            border-radius: 25px;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .page-btn.active {
            background: #D86487;
            color: white;
        }
        
        .page-btn:hover {
            background: #EEAAC3;
            border-color: #EEAAC3;
        }
        
        /* Two Column Layout */
        .studio-layout {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 25px;
        }
        
        /* Sticker Library */
        .sticker-library {
            background: white;
            border-radius: 20px;
            padding: 20px;
            height: fit-content;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .sticker-library h3 {
            color: #4A202A;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #F1DFDD;
        }
        
        .category-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .cat-tab {
            padding: 6px 15px;
            background: #F1DFDD;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: 0.3s;
            font-size: 13px;
        }
        
        .cat-tab.active {
            background: #D86487;
            color: white;
        }
        
        .sticker-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .sticker-item {
            background: #F9F9F9;
            padding: 12px;
            border-radius: 12px;
            text-align: center;
            cursor: grab;
            transition: 0.3s;
            position: relative;
        }
        
        .sticker-item:hover {
            background: #EEAAC3;
            transform: scale(1.05);
        }
        
        .sticker-item:active {
            cursor: grabbing;
        }
        
        .sticker-img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            pointer-events: none;
        }
        
        .sticker-name {
            font-size: 10px;
            color: #999;
            margin-top: 5px;
            pointer-events: none;
        }
        
        /* Canvas Area */
        .canvas-area {
            background: white;
            border-radius: 20px;
            padding: 20px;
            position: relative;
        }
        
        .canvas-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #F1DFDD;
        }
        
        .canvas-header h3 {
            color: #4A202A;
        }
        
        .canvas-controls {
            display: flex;
            gap: 10px;
        }
        
        .canvas-btn {
            background: #F1DFDD;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .canvas-btn:hover {
            background: #D86487;
            color: white;
        }
        
        .canvas-btn.danger:hover {
            background: #f44336;
        }
        
        .sticker-canvas {
            background: #FFF5F3;
            min-height: 500px;
            border-radius: 15px;
            position: relative;
            overflow: hidden;
            background-image: 
                linear-gradient(#F1DFDD 1px, transparent 1px),
                linear-gradient(90deg, #F1DFDD 1px, transparent 1px);
            background-size: 20px 20px;
        }
        
        .canvas-sticker {
            position: absolute;
            cursor: move;
            transition: box-shadow 0.2s;
            user-select: none;
        }
        
        .canvas-sticker:hover {
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }
        
        .canvas-sticker.selected {
            outline: 3px solid #D86487;
            outline-offset: 2px;
            border-radius: 8px;
        }
        
        .canvas-sticker img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            pointer-events: none;
        }
        
        .sticker-controls-popup {
            position: fixed;
            background: white;
            border-radius: 12px;
            padding: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            display: none;
            gap: 8px;
            z-index: 1000;
        }
        
        .sticker-controls-popup button {
            background: #F1DFDD;
            border: none;
            padding: 5px 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .sticker-controls-popup button:hover {
            background: #D86487;
            color: white;
        }
        
        .empty-canvas {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: #ccc;
            pointer-events: none;
        }
        
        .empty-canvas span {
            font-size: 50px;
            display: block;
            margin-bottom: 10px;
        }
        
        .save-section {
            margin-top: 20px;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }
        
        .save-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .save-btn:hover {
            background: #45a049;
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #4CAF50;
            color: white;
        }
        
        /* Resize handle */
        .resize-handle {
            position: absolute;
            bottom: -5px;
            right: -5px;
            width: 12px;
            height: 12px;
            background: #D86487;
            border-radius: 50%;
            cursor: nw-resize;
        }
        
        @keyframes bounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .sticker-item:active {
            animation: bounce 0.2s ease;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h3 style="color: white; margin-bottom: 30px;">🌸 Petal Pages</h3>
    <a href="dashboard.php">📅 Dashboard</a>
    <a href="diary.php">📖 Diary</a>
    <a href="habits.php">🌿 Habits</a>
    <a href="todos.php">✅ Todos</a>
    <a href="moods.php">🎭 Moods</a>
    <a href="stickers.php">🎀 Stickers</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="main-content">
    <div class="page-header">
        <h2><span class="script">your</span> sticker studio</h2>
        <p>Drag stickers onto the canvas, resize, rotate, and decorate!</p>
    </div>
    
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    
    <div class="page-selector">
        <label>📄 Decorate page:</label>
        <div class="page-buttons">
            <button class="page-btn <?php echo $current_page == 'diary' ? 'active' : ''; ?>" onclick="changePage('diary')">📖 Diary</button>
            <button class="page-btn <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>" onclick="changePage('dashboard')">📅 Dashboard</button>
            <button class="page-btn <?php echo $current_page == 'habits' ? 'active' : ''; ?>" onclick="changePage('habits')">🌿 Habits</button>
        </div>
    </div>
    
    <div class="studio-layout">
        <!-- Sticker Library -->
        <div class="sticker-library">
            <h3>🎀 Sticker Collection</h3>
            <div class="category-tabs" id="categoryTabs"></div>
            <div class="sticker-grid" id="stickerGrid"></div>
            <div class="tip" style="margin-top: 15px; padding: 10px; background: #FFF5F3; border-radius: 10px; font-size: 12px; color: #666;">
                💡 Tip: Click and drag stickers onto the canvas!
            </div>
        </div>
        
        <!-- Canvas Area -->
        <div class="canvas-area">
            <div class="canvas-header">
                <h3>🖼️ Your Sticker Canvas</h3>
                <div class="canvas-controls">
                    <button class="canvas-btn" onclick="clearCanvas()">🗑️ Clear All</button>
                    <button class="canvas-btn" onclick="bringToFront()">⬆️ Bring to Front</button>
                </div>
            </div>
            
            <div class="sticker-canvas" id="stickerCanvas">
                <div class="empty-canvas" id="emptyCanvas">
                    <span>✨</span>
                    <p>Drag stickers here!</p>
                </div>
            </div>
            
            <div class="save-section">
                <form method="POST" id="saveForm">
                    <input type="hidden" name="save_stickers" value="1">
                    <input type="hidden" name="page_ref" id="pageRef" value="<?php echo $current_page; ?>">
                    <input type="hidden" name="stickers" id="stickersData">
                    <button type="submit" class="save-btn">💾 Save Stickers for this Page</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Sticker Controls Popup -->
<div class="sticker-controls-popup" id="stickerControls">
    <button onclick="deleteSelected()">🗑️ Delete</button>
    <button onclick="rotateSelected()">🔄 Rotate</button>
    <button onclick="scaleUpSelected()">➕ Enlarge</button>
    <button onclick="scaleDownSelected()">➖ Shrink</button>
</div>

<script>
let currentCategory = '';
let stickers = {};
let canvasStickers = [];
let selectedStickerId = null;
let dragOffsetX = 0, dragOffsetY = 0;
let isDragging = false;
let currentZIndex = 100;

// Sticker categories and files
const categories = ['food', 'nature', 'study', 'characters'];
const categoryNames = { food: '🍕 Food', nature: '🌿 Nature', study: '📚 Study', characters: '🎭 Characters' };

// Load stickers from server
async function loadStickers() {
    for (const cat of categories) {
        const response = await fetch(`get_stickers.php?category=${cat}`);
        const data = await response.json();
        stickers[cat] = data;
    }
    renderCategoryTabs();
    if (categories.length > 0) {
        selectCategory(categories[0]);
    }
}

function renderCategoryTabs() {
    const container = document.getElementById('categoryTabs');
    container.innerHTML = categories.map(cat => 
        `<button class="cat-tab" onclick="selectCategory('${cat}')">${categoryNames[cat]}</button>`
    ).join('');
}

function selectCategory(category) {
    currentCategory = category;
    document.querySelectorAll('.cat-tab').forEach((btn, i) => {
        if (categories[i] === category) btn.classList.add('active');
        else btn.classList.remove('active');
    });
    renderStickers(category);
}

function renderStickers(category) {
    const grid = document.getElementById('stickerGrid');
    const stickerList = stickers[category] || [];
    
    if (stickerList.length === 0) {
        grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: #999; padding: 40px;">✨ No stickers yet. Add images to assets/' + category + '/ folder</div>';
        return;
    }
    
    grid.innerHTML = stickerList.map(sticker => `
        <div class="sticker-item" draggable="true" data-sticker="${sticker.file}" data-category="${category}">
            <img src="assets/${category}/${sticker.file}" class="sticker-img" alt="${sticker.name}">
            <div class="sticker-name">${sticker.name}</div>
        </div>
    `).join('');
    
    // Add drag event listeners
    document.querySelectorAll('.sticker-item').forEach(el => {
        el.addEventListener('dragstart', handleDragStart);
        el.addEventListener('dragend', handleDragEnd);
    });
}

let draggedSticker = null;

function handleDragStart(e) {
    draggedSticker = {
        file: e.target.closest('.sticker-item').dataset.sticker,
        category: e.target.closest('.sticker-item').dataset.category
    };
    e.dataTransfer.setData('text/plain', JSON.stringify(draggedSticker));
    e.target.style.opacity = '0.5';
}

function handleDragEnd(e) {
    e.target.style.opacity = '';
    draggedSticker = null;
}

// Canvas drag and drop
const canvas = document.getElementById('stickerCanvas');

canvas.addEventListener('dragover', (e) => {
    e.preventDefault();
});

canvas.addEventListener('drop', (e) => {
    e.preventDefault();
    const rect = canvas.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    
    let stickerData;
    try {
        stickerData = JSON.parse(e.dataTransfer.getData('text/plain'));
    } catch {
        return;
    }
    
    if (stickerData) {
        addStickerToCanvas(stickerData.file, stickerData.category, x - 35, y - 35);
    }
});

function addStickerToCanvas(file, category, x, y, savedData = null) {
    const emptyDiv = document.getElementById('emptyCanvas');
    if (emptyDiv) emptyDiv.style.display = 'none';
    
    const stickerId = 'sticker_' + Date.now() + '_' + Math.random();
    const stickerDiv = document.createElement('div');
    stickerDiv.className = 'canvas-sticker';
    stickerDiv.id = stickerId;
    stickerDiv.style.left = (savedData ? savedData.x : x) + 'px';
    stickerDiv.style.top = (savedData ? savedData.y : y) + 'px';
    stickerDiv.style.width = (savedData ? savedData.scale * 60 : 60) + 'px';
    stickerDiv.style.height = (savedData ? savedData.scale * 60 : 60) + 'px';
    stickerDiv.style.transform = (savedData ? `rotate(${savedData.rot}deg)` : 'rotate(0deg)');
    stickerDiv.style.zIndex = currentZIndex++;
    
    stickerDiv.innerHTML = `
        <img src="assets/${category}/${file}" alt="sticker">
        <div class="resize-handle"></div>
    `;
    
    stickerDiv.addEventListener('mousedown', (e) => {
        if (e.target.classList.contains('resize-handle')) {
            e.stopPropagation();
            startResize(e, stickerDiv);
        } else {
            selectSticker(stickerId);
            startDrag(e, stickerDiv);
        }
    });
    
    canvas.appendChild(stickerDiv);
    
    canvasStickers.push({
        id: stickerId,
        file: file,
        category: category,
        x: savedData ? savedData.x : x,
        y: savedData ? savedData.y : y,
        rot: savedData ? savedData.rot : 0,
        scale: savedData ? savedData.scale : 1
    });
    
    // Add resize handle functionality
    const resizeHandle = stickerDiv.querySelector('.resize-handle');
    if (resizeHandle) {
        resizeHandle.addEventListener('mousedown', (e) => {
            e.stopPropagation();
            startResize(e, stickerDiv);
        });
    }
}

let isResizing = false;
let startWidth, startHeight, startX, startY, currentSticker;

function startResize(e, sticker) {
    isResizing = true;
    currentSticker = sticker;
    startX = e.clientX;
    startY = e.clientY;
    startWidth = sticker.offsetWidth;
    startHeight = sticker.offsetHeight;
    
    document.addEventListener('mousemove', onResize);
    document.addEventListener('mouseup', stopResize);
    e.preventDefault();
}

function onResize(e) {
    if (!isResizing || !currentSticker) return;
    
    const dx = e.clientX - startX;
    const newWidth = Math.max(30, Math.min(200, startWidth + dx));
    const newHeight = newWidth; // Keep square
    
    currentSticker.style.width = newWidth + 'px';
    currentSticker.style.height = newHeight + 'px';
    
    // Update scale in data
    const stickerData = canvasStickers.find(s => s.id === currentSticker.id);
    if (stickerData) {
        stickerData.scale = newWidth / 60;
    }
}

function stopResize() {
    isResizing = false;
    currentSticker = null;
    document.removeEventListener('mousemove', onResize);
    document.removeEventListener('mouseup', stopResize);
}

function selectSticker(id) {
    // Remove selection from all
    document.querySelectorAll('.canvas-sticker').forEach(s => {
        s.classList.remove('selected');
    });
    
    selectedStickerId = id;
    const selected = document.getElementById(id);
    if (selected) {
        selected.classList.add('selected');
        
        // Show controls popup near the sticker
        const rect = selected.getBoundingClientRect();
        const popup = document.getElementById('stickerControls');
        popup.style.display = 'flex';
        popup.style.left = rect.right + 10 + 'px';
        popup.style.top = rect.top + 'px';
        
        // Hide popup after 2 seconds or on click elsewhere
        setTimeout(() => {
            if (!popup.matches(':hover')) {
                popup.style.display = 'none';
            }
        }, 3000);
    }
}

function startDrag(e, sticker) {
    if (isResizing) return;
    isDragging = true;
    selectedStickerId = sticker.id;
    selectSticker(sticker.id);
    
    const rect = sticker.getBoundingClientRect();
    const canvasRect = canvas.getBoundingClientRect();
    dragOffsetX = e.clientX - rect.left;
    dragOffsetY = e.clientY - rect.top;
    
    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', stopDrag);
    e.preventDefault();
}

function onDrag(e) {
    if (!isDragging || !selectedStickerId) return;
    
    const sticker = document.getElementById(selectedStickerId);
    if (!sticker) return;
    
    const canvasRect = canvas.getBoundingClientRect();
    let newX = e.clientX - canvasRect.left - dragOffsetX;
    let newY = e.clientY - canvasRect.top - dragOffsetY;
    
    // Constrain to canvas
    newX = Math.max(0, Math.min(newX, canvasRect.width - sticker.offsetWidth));
    newY = Math.max(0, Math.min(newY, canvasRect.height - sticker.offsetHeight));
    
    sticker.style.left = newX + 'px';
    sticker.style.top = newY + 'px';
    
    // Update data
    const stickerData = canvasStickers.find(s => s.id === selectedStickerId);
    if (stickerData) {
        stickerData.x = newX;
        stickerData.y = newY;
    }
}

function stopDrag() {
    isDragging = false;
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);
}

function deleteSelected() {
    if (selectedStickerId) {
        const sticker = document.getElementById(selectedStickerId);
        if (sticker) sticker.remove();
        canvasStickers = canvasStickers.filter(s => s.id !== selectedStickerId);
        selectedStickerId = null;
        document.getElementById('stickerControls').style.display = 'none';
        
        if (canvasStickers.length === 0) {
            document.getElementById('emptyCanvas').style.display = 'block';
        }
    }
}

function rotateSelected() {
    if (selectedStickerId) {
        const sticker = document.getElementById(selectedStickerId);
        const stickerData = canvasStickers.find(s => s.id === selectedStickerId);
        if (sticker && stickerData) {
            stickerData.rot = (stickerData.rot + 15) % 360;
            sticker.style.transform = `rotate(${stickerData.rot}deg)`;
        }
    }
}

function scaleUpSelected() {
    if (selectedStickerId) {
        const sticker = document.getElementById(selectedStickerId);
        const stickerData = canvasStickers.find(s => s.id === selectedStickerId);
        if (sticker && stickerData && stickerData.scale < 3) {
            stickerData.scale += 0.2;
            const newSize = 60 * stickerData.scale;
            sticker.style.width = newSize + 'px';
            sticker.style.height = newSize + 'px';
        }
    }
}

function scaleDownSelected() {
    if (selectedStickerId) {
        const sticker = document.getElementById(selectedStickerId);
        const stickerData = canvasStickers.find(s => s.id === selectedStickerId);
        if (sticker && stickerData && stickerData.scale > 0.4) {
            stickerData.scale -= 0.2;
            const newSize = 60 * stickerData.scale;
            sticker.style.width = newSize + 'px';
            sticker.style.height = newSize + 'px';
        }
    }
}

function bringToFront() {
    if (selectedStickerId) {
        const sticker = document.getElementById(selectedStickerId);
        if (sticker) {
            sticker.style.zIndex = currentZIndex++;
        }
    }
}

function clearCanvas() {
    if (confirm('Clear all stickers from this page?')) {
        canvas.innerHTML = '<div class="empty-canvas" id="emptyCanvas"><span>✨</span><p>Drag stickers here!</p></div>';
        canvasStickers = [];
        selectedStickerId = null;
        document.getElementById('stickerControls').style.display = 'none';
    }
}

function changePage(page) {
    window.location.href = `stickers.php?page=${page}`;
}

// Save stickers
document.getElementById('saveForm').addEventListener('submit', function(e) {
    const stickersData = canvasStickers.map(s => ({
        file: s.file,
        category: s.category,
        x: s.x,
        y: s.y,
        rot: s.rot,
        scale: s.scale
    }));
    document.getElementById('stickersData').value = JSON.stringify(stickersData);
});

// Load saved stickers
<?php if(!empty($saved_stickers)): ?>
window.addEventListener('load', function() {
    setTimeout(function() {
        <?php foreach($saved_stickers as $sticker): ?>
        addStickerToCanvas('<?php echo addslashes($sticker['sticker_id']); ?>', 
                          '<?php echo explode("/", $sticker['sticker_id'])[0] ?? "food"; ?>', 
                          <?php echo $sticker['x_pos']; ?>, 
                          <?php echo $sticker['y_pos']; ?>,
                          {rot: <?php echo $sticker['rotation']; ?>, scale: <?php echo $sticker['scale']; ?>, x: <?php echo $sticker['x_pos']; ?>, y: <?php echo $sticker['y_pos']; ?>});
        <?php endforeach; ?>
    }, 100);
});
<?php endif; ?>

// Hide popup when clicking elsewhere
document.addEventListener('click', function(e) {
    const popup = document.getElementById('stickerControls');
    if (!popup.contains(e.target) && !e.target.classList.contains('canvas-sticker') && !e.target.closest('.canvas-sticker')) {
        popup.style.display = 'none';
    }
});

loadStickers();
</script>

</body>
</html>