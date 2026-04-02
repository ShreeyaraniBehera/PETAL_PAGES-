<?php
session_start();
if (!isset($_SESSION['user_id'])) header("Location: index.php");

$conn = new mysqli("localhost", "root", "", "petal_pages");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Petal Pages - Habits</title>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:wght@400;700&family=Great+Vibes&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #F1DFDD; font-family: 'Poppins', sans-serif; }
        
        .sidebar {
            width: 220px;
            height: 100vh;
            background: #4A202A;
            position: fixed;
            padding: 30px 20px;
            top: 0;
            left: 0;
        }
        
        .sidebar a {
            display: block;
            margin: 15px 0;
            color: white;
            text-decoration: none;
            padding: 10px;
            border-radius: 10px;
        }
        
        .sidebar a:hover { background: #D86487; }
        
        .main-content {
            margin-left: 240px;
            padding: 30px;
        }
        
        .habit-form {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .habit-form input {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #ddd;
        }
        
        .habit-form button {
            background: #76172C;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 30px;
            cursor: pointer;
        }
        
        .habit-list {
            background: white;
            padding: 20px;
            border-radius: 15px;
        }
        
        .habit-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .success {
            background: #4CAF50;
            color: white;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 20px;
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
    <h2 style="margin-bottom: 20px;">🌿 Your Habits</h2>
    
    <?php if(isset($_GET['success'])): ?>
        <div class="success">✅ <?php echo $_GET['success']; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="config/db.php" class="habit-form">
        <input type="hidden" name="action" value="add_habit">
        <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
        <input type="text" name="habit" placeholder="Enter a new habit..." required>
        <button type="submit">➕ Add Habit</button>
    </form>
    
    <div class="habit-list">
        <h3>Your Habits</h3>
        <?php
        $stmt = $conn->prepare("SELECT * FROM habits WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            echo "<p style='color: #999; padding: 20px;'>No habits yet. Add one above!</p>";
        } else {
            while($habit = $result->fetch_assoc()) {
                echo "<div class='habit-item'>";
                echo "<span>📌 " . htmlspecialchars($habit['name']) . "</span>";
                echo "</div>";
            }
        }
        ?>
    </div>
</div>

</body>
</html>