<?php
session_start();
if (!isset($_SESSION['user_id'])) header("Location: index.php");

$conn = new mysqli("localhost", "root", "", "petal_pages");

// Handle delete request
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM diary_entries WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $_GET['delete'], $_SESSION['user_id']);
    $stmt->execute();
    header("Location: diary.php?success=Entry deleted");
    exit();
}

// Handle edit - get entry data
$edit_entry = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM diary_entries WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $_GET['edit'], $_SESSION['user_id']);
    $stmt->execute();
    $edit_entry = $stmt->get_result()->fetch_assoc();
}

// Get all diary entries for the user
$stmt = $conn->prepare("
    SELECT d.*, m.emoji_icon, m.name as mood_name, m.color_hex 
    FROM diary_entries d 
    LEFT JOIN moods m ON d.mood_id = m.id 
    WHERE d.user_id = ? 
    ORDER BY d.date DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$entries = $stmt->get_result();

// Get moods for dropdown
$moods_result = $conn->query("SELECT * FROM moods ORDER BY id");

// Get current date for form
$current_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Petal Pages - Diary</title>
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
            overflow-y: auto;
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
        
        .sidebar a:hover {
            background: #D86487;
        }
        
        .main-content {
            margin-left: 240px;
            padding: 30px;
        }
        
        /* Header */
        .page-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .page-header h2 {
            font-size: 32px;
            color: #4A202A;
        }
        
        .new-entry-btn {
            background: #D86487;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 30px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
        }
        
        .new-entry-btn:hover {
            background: #76172C;
            transform: translateY(-2px);
        }
        
        /* Two Column Layout */
        .diary-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 25px;
        }
        
        /* Entries List */
        .entries-list {
            background: white;
            border-radius: 20px;
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .entries-list h3 {
            color: #4A202A;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #F1DFDD;
        }
        
        .entry-card {
            background: #FFF5F3;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            transition: 0.3s;
            cursor: pointer;
            position: relative;
        }
        
        .entry-card:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .entry-card.selected {
            border: 2px solid #D86487;
            background: white;
        }
        
        .entry-date {
            font-size: 14px;
            color: #D86487;
            font-weight: 500;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .entry-mood {
            display: inline-block;
            background: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .entry-preview {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .entry-actions {
            position: absolute;
            top: 15px;
            right: 15px;
            display: flex;
            gap: 8px;
            opacity: 0;
            transition: 0.3s;
        }
        
        .entry-card:hover .entry-actions {
            opacity: 1;
        }
        
        .entry-actions button {
            background: white;
            border: none;
            padding: 5px 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .entry-actions button:hover {
            background: #D86487;
            color: white;
        }
        
        /* Form Panel */
        .form-panel {
            background: white;
            border-radius: 20px;
            padding: 25px;
            position: sticky;
            top: 30px;
            max-height: calc(100vh - 60px);
            overflow-y: auto;
        }
        
        .form-panel h3 {
            color: #4A202A;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #F1DFDD;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4A202A;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border-radius: 12px;
            border: 2px solid #F1DFDD;
            transition: 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #D86487;
        }
        
        .form-group textarea {
            height: 200px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .form-actions button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            transition: 0.3s;
        }
        
        .btn-save {
            background: #D86487;
            color: white;
        }
        
        .btn-save:hover {
            background: #76172C;
        }
        
        .btn-cancel {
            background: #F1DFDD;
            color: #666;
        }
        
        .btn-cancel:hover {
            background: #ddd;
        }
        
        /* Alert Messages */
        .alert {
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #4CAF50;
            color: white;
        }
        
        .alert-error {
            background: #f44336;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state span {
            font-size: 50px;
            display: block;
            margin-bottom: 15px;
        }
        
        /* Mood indicator colors */
        .mood-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .diary-layout {
                grid-template-columns: 1fr;
            }
            .form-panel {
                position: static;
            }
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .entry-card {
            animation: slideIn 0.3s ease;
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
        <h2><span class="script">your</span> diary</h2>
        <a href="?new=1" class="new-entry-btn">✏️ Write New Entry</a>
    </div>
    
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    
    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-error">❌ <?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>
    
    <div class="diary-layout">
        <!-- Left: List of entries -->
        <div class="entries-list">
            <h3>📖 Past Entries</h3>
            <?php if($entries->num_rows > 0): ?>
                <?php while($entry = $entries->fetch_assoc()): ?>
                    <div class="entry-card" onclick="selectEntry(<?php echo $entry['id']; ?>)" id="entry-<?php echo $entry['id']; ?>">
                        <div class="entry-date">
                            <span>📅 <?php echo date('F j, Y', strtotime($entry['date'])); ?></span>
                            <span class="entry-mood">
                                <?php echo $entry['emoji_icon']; ?> <?php echo $entry['mood_name']; ?>
                            </span>
                        </div>
                        <div class="entry-preview">
                            <?php echo nl2br(htmlspecialchars(substr($entry['content'], 0, 150))) . (strlen($entry['content']) > 150 ? '...' : ''); ?>
                        </div>
                        <div class="entry-actions" onclick="event.stopPropagation()">
                            <a href="?edit=<?php echo $entry['id']; ?>">
                                <button title="Edit">✏️</button>
                            </a>
                            <a href="?delete=<?php echo $entry['id']; ?>" onclick="return confirm('Delete this entry?')">
                                <button title="Delete">🗑️</button>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <span>📭</span>
                    <p>No diary entries yet.</p>
                    <p style="font-size: 12px; margin-top: 10px;">Click "Write New Entry" to start your journey!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Right: Entry Form -->
        <div class="form-panel">
            <h3><?php echo $edit_entry ? '✏️ Edit Entry' : (isset($_GET['new']) ? '✨ New Entry' : '📝 Write Entry'); ?></h3>
            
            <form method="POST" action="config/db.php">
                <input type="hidden" name="action" value="<?php echo $edit_entry ? 'update_diary' : 'save_diary'; ?>">
                <input type="hidden" name="entry_id" value="<?php echo $edit_entry ? $edit_entry['id'] : ''; ?>">
                <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                
                <div class="form-group">
                    <label>📅 Date</label>
                    <input type="date" name="date" value="<?php echo $edit_entry ? $edit_entry['date'] : $current_date; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>😊 How are you feeling?</label>
                    <select name="mood_id" required>
                        <?php 
                        $moods_result->data_seek(0);
                        while($mood = $moods_result->fetch_assoc()): 
                            $selected = ($edit_entry && $edit_entry['mood_id'] == $mood['id']) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $mood['id']; ?>" <?php echo $selected; ?>>
                                <?php echo $mood['emoji_icon']; ?> <?php echo $mood['name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>🎨 Background Theme (Optional)</label>
                    <select name="bg_theme">
                        <option value="default" <?php echo ($edit_entry && $edit_entry['bg_theme'] == 'default') ? 'selected' : ''; ?>>Default</option>
                        <?php
                        if(is_dir("assets/backgrounds/")) {
                            foreach(scandir("assets/backgrounds/") as $file) {
                                if($file != "." && $file != "..") {
                                    $selected = ($edit_entry && $edit_entry['bg_theme'] == "assets/backgrounds/$file") ? 'selected' : '';
                                    echo "<option value='assets/backgrounds/$file' $selected>$file</option>";
                                }
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>📝 Your Entry</label>
                    <textarea name="content" placeholder="Write your thoughts here..." required><?php echo $edit_entry ? htmlspecialchars($edit_entry['content']) : ''; ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-save">💾 Save Entry</button>
                    <?php if($edit_entry || isset($_GET['new'])): ?>
                        <a href="diary.php" class="btn-cancel" style="text-align: center; text-decoration: none; line-height: 44px;">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
            
            <!-- Writing Tips -->
            <div style="margin-top: 20px; padding: 15px; background: #FFF5F3; border-radius: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 24px;">💡</span>
                    <div>
                        <strong>Writing Tip</strong>
                        <p style="font-size: 12px; color: #666; margin-top: 5px;">
                            Try writing freely without judgment. Let your thoughts flow naturally!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectEntry(entryId) {
    // Highlight selected entry
    document.querySelectorAll('.entry-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.getElementById('entry-' + entryId).classList.add('selected');
    
    // Optional: Scroll to entry
    document.getElementById('entry-' + entryId).scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// If coming from edit, highlight the entry being edited
<?php if($edit_entry): ?>
window.addEventListener('load', function() {
    selectEntry(<?php echo $edit_entry['id']; ?>);
});
<?php endif; ?>
</script>

</body>
</html>