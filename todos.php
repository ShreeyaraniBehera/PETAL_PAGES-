<?php
session_start();
if (!isset($_SESSION['user_id'])) header("Location: index.php");

$conn = new mysqli("localhost", "root", "", "petal_pages");

// Handle delete request
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM todos WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $_GET['delete'], $_SESSION['user_id']);
    $stmt->execute();
    header("Location: todos.php");
    exit();
}

// Handle toggle complete
if (isset($_GET['toggle'])) {
    $stmt = $conn->prepare("UPDATE todos SET is_done = NOT is_done WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $_GET['toggle'], $_SESSION['user_id']);
    $stmt->execute();
    header("Location: todos.php");
    exit();
}

// Get user's todos
$stmt = $conn->prepare("SELECT * FROM todos WHERE user_id = ? ORDER BY is_done ASC, date DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$todos_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Petal Pages - To-Do List</title>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:wght@400;700&family=Great+Vibes&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #F1DFDD; font-family: 'Poppins', sans-serif; }
        .script { font-family: 'Great Vibes', cursive; }
        
        /* Sidebar */
        .sidebar {
            width: 220px;
            height: 100vh;
            background: #4A202A;
            position: fixed;
            padding: 30px 20px;
            top: 0;
            left: 0;
        }
        
        .sidebar h3 {
            color: white;
            margin-bottom: 30px;
            font-size: 20px;
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
        
        /* Main Content */
        .main-content {
            margin-left: 240px;
            padding: 30px;
            min-height: 100vh;
        }
        
        /* Header */
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h2 {
            font-size: 32px;
            color: #4A202A;
        }
        
        /* Add Task Form */
        .add-task-form {
            background: white;
            padding: 25px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4A202A;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #F1DFDD;
            border-radius: 12px;
            font-size: 14px;
            transition: 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #D86487;
        }
        
        .add-task-form button {
            background: #D86487;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            transition: 0.3s;
        }
        
        .add-task-form button:hover {
            background: #76172C;
            transform: translateY(-2px);
        }
        
        /* Success/Error Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #4CAF50;
            color: white;
        }
        
        .alert-error {
            background: #f44336;
            color: white;
        }
        
        /* Todo Stats */
        .todo-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            padding: 15px 25px;
            border-radius: 15px;
            text-align: center;
            flex: 1;
            max-width: 150px;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #D86487;
        }
        
        .stat-label {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        /* Todo List */
        .todo-list {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .todo-header {
            background: #4A202A;
            color: white;
            padding: 15px 20px;
            display: grid;
            grid-template-columns: 50px 1fr 100px;
            font-weight: 500;
        }
        
        .todo-item {
            padding: 15px 20px;
            display: grid;
            grid-template-columns: 50px 1fr 100px;
            align-items: center;
            border-bottom: 1px solid #F1DFDD;
            transition: 0.3s;
        }
        
        .todo-item:hover {
            background: #FFF5F3;
        }
        
        .todo-check {
            width: 25px;
            height: 25px;
            cursor: pointer;
        }
        
        .todo-text {
            font-size: 16px;
            color: #333;
        }
        
        .todo-text.completed {
            text-decoration: line-through;
            color: #999;
        }
        
        .todo-actions {
            display: flex;
            gap: 10px;
        }
        
        .todo-actions button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
            border-radius: 8px;
            transition: 0.3s;
        }
        
        .todo-actions button:hover {
            background: #F1DFDD;
        }
        
        .delete-btn:hover {
            color: #f44336;
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
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .todo-item {
            animation: fadeIn 0.3s ease;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>🌸 Petal Pages</h3>
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
        <h2><span class="script">your</span> to-do list</h2>
    </div>
    
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">
            ✅ <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-error">
            ❌ <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Add Task Form -->
    <form method="POST" action="config/db.php" class="add-task-form">
        <input type="hidden" name="action" value="add_todo">
        <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
        
        <div class="form-group">
            <label>📝 New Task</label>
            <input type="text" name="task" placeholder="What do you need to do?" required>
        </div>
        
        <button type="submit">➕ Add Task</button>
    </form>
    
    <!-- Todo Statistics -->
    <?php
    $total = $todos_result->num_rows;
    $completed = 0;
    $todos_result->data_seek(0);
    while($todo = $todos_result->fetch_assoc()) {
        if($todo['is_done']) $completed++;
    }
    $todos_result->data_seek(0);
    ?>
    
    <div class="todo-stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo $total; ?></div>
            <div class="stat-label">Total Tasks</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $completed; ?></div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $total - $completed; ?></div>
            <div class="stat-label">Pending</div>
        </div>
    </div>
    
    <!-- Todo List -->
    <div class="todo-list">
        <div class="todo-header">
            <div></div>
            <div>Task</div>
            <div>Actions</div>
        </div>
        
        <?php if($total == 0): ?>
            <div class="empty-state">
                <span>📝</span>
                <p>No tasks yet! Add your first task above.</p>
                <p style="font-size: 12px; margin-top: 10px;">Stay organized and productive ✨</p>
            </div>
        <?php else: ?>
            <?php while($todo = $todos_result->fetch_assoc()): ?>
                <div class="todo-item">
                    <div>
                        <a href="?toggle=<?php echo $todo['id']; ?>" style="text-decoration: none;">
                            <div class="todo-check" style="
                                width: 25px;
                                height: 25px;
                                border-radius: 50%;
                                border: 2px solid <?php echo $todo['is_done'] ? '#4CAF50' : '#D86487'; ?>;
                                background: <?php echo $todo['is_done'] ? '#4CAF50' : 'white'; ?>;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                cursor: pointer;
                            ">
                                <?php if($todo['is_done']): ?>
                                    <span style="color: white; font-size: 14px;">✓</span>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                    
                    <div class="todo-text <?php echo $todo['is_done'] ? 'completed' : ''; ?>">
                        <?php echo htmlspecialchars($todo['title']); ?>
                        <?php if($todo['date']): ?>
                            <div style="font-size: 11px; color: #999; margin-top: 4px;">
                                📅 <?php echo date('M j, Y', strtotime($todo['date'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="todo-actions">
                        <a href="?toggle=<?php echo $todo['id']; ?>" style="text-decoration: none;">
                            <button type="button" title="<?php echo $todo['is_done'] ? 'Undo' : 'Complete'; ?>">
                                <?php echo $todo['is_done'] ? '🔄' : '✅'; ?>
                            </button>
                        </a>
                        <a href="?delete=<?php echo $todo['id']; ?>" onclick="return confirm('Delete this task?')" style="text-decoration: none;">
                            <button type="button" class="delete-btn" title="Delete">🗑️</button>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>