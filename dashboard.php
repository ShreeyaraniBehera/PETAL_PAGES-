<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "petal_pages");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get selected date (default to today)
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get diary entry for selected date
$stmt = $conn->prepare("SELECT d.*, m.emoji_icon, m.name as mood_name 
                        FROM diary_entries d 
                        LEFT JOIN moods m ON d.mood_id = m.id 
                        WHERE d.user_id = ? AND d.date = ?");
$stmt->bind_param("is", $_SESSION['user_id'], $selected_date);
$stmt->execute();
$diary_entry = $stmt->get_result()->fetch_assoc();

// Get todos for selected date
$stmt = $conn->prepare("SELECT * FROM todos WHERE user_id = ? AND date = ? ORDER BY is_done ASC");
$stmt->bind_param("is", $_SESSION['user_id'], $selected_date);
$stmt->execute();
$todos = $stmt->get_result();

// Get habits for selected date
$stmt = $conn->prepare("SELECT h.*, 
                        (SELECT completed FROM habit_logs WHERE habit_id = h.id AND date = ?) as completed
                        FROM habits h 
                        WHERE h.user_id = ?");
$stmt->bind_param("si", $selected_date, $_SESSION['user_id']);
$stmt->execute();
$habits = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Petal Pages - Dashboard</title>
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
        
        .welcome {
            background: white;
            padding: 20px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        /* Calendar */
        .calendar-section {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .month-nav {
            display: flex;
            gap: 15px;
        }
        
        .month-nav button {
            background: #D86487;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }
        
        .calendar-day-header {
            text-align: center;
            padding: 10px;
            font-weight: bold;
            color: #D86487;
        }
        
        .calendar-day {
            background: #F9F9F9;
            padding: 15px;
            text-align: center;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
            position: relative;
        }
        
        .calendar-day:hover {
            background: #EEAAC3;
            transform: scale(1.05);
        }
        
        .calendar-day.selected {
            background: #D86487;
            color: white;
        }
        
        .calendar-day.has-entry {
            background: #4CAF50;
            color: white;
        }
        
        .calendar-day.has-entry.selected {
            background: #D86487;
        }
        
        .calendar-day.today {
            border: 2px solid #D86487;
            font-weight: bold;
        }
        
        /* Content Panel */
        .content-panel {
            background: white;
            border-radius: 20px;
            padding: 25px;
        }
        
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #F1DFDD;
        }
        
        .panel-header h3 {
            color: #4A202A;
        }
        
        .add-btn {
            background: #D86487;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .diary-entry {
            background: #FFF5F3;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        
        .diary-mood {
            display: inline-block;
            background: white;
            padding: 5px 12px;
            border-radius: 20px;
            margin-bottom: 10px;
        }
        
        .diary-content {
            margin-top: 10px;
            line-height: 1.6;
        }
        
        .todo-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            border-bottom: 1px solid #F1DFDD;
        }
        
        .todo-check {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #D86487;
            background: white;
            cursor: pointer;
        }
        
        .todo-check.completed {
            background: #4CAF50;
            border-color: #4CAF50;
        }
        
        .todo-text {
            flex: 1;
        }
        
        .todo-text.completed {
            text-decoration: line-through;
            color: #999;
        }
        
        .habit-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            border-bottom: 1px solid #F1DFDD;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .empty-state span {
            font-size: 40px;
            display: block;
            margin-bottom: 10px;
        }
        
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #F1DFDD;
        }
        
        .quick-btn {
            flex: 1;
            text-align: center;
            padding: 12px;
            background: #F9F9F9;
            border-radius: 12px;
            text-decoration: none;
            color: #4A202A;
            transition: 0.3s;
        }
        
        .quick-btn:hover {
            background: #EEAAC3;
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
    <div class="welcome">
        <?php
        $result = $conn->query("SELECT username FROM users WHERE id = {$_SESSION['user_id']}");
        $user = $result->fetch_assoc();
        echo "<h3>🌸 Welcome back, " . htmlspecialchars($user['username']) . "!</h3>";
        echo "<p>Today is " . date('l, F j, Y') . "</p>";
        ?>
    </div>
    
    <!-- Calendar -->
    <div class="calendar-section">
        <div class="calendar-header">
            <h3>📅 Select a Date</h3>
            <div class="month-nav">
                <button onclick="changeMonth(-1)">◀ Previous</button>
                <button onclick="goToToday()">Today</button>
                <button onclick="changeMonth(1)">Next ▶</button>
            </div>
        </div>
        
        <div class="calendar-grid" id="calendar">
            <div class="calendar-day-header">Sun</div>
            <div class="calendar-day-header">Mon</div>
            <div class="calendar-day-header">Tue</div>
            <div class="calendar-day-header">Wed</div>
            <div class="calendar-day-header">Thu</div>
            <div class="calendar-day-header">Fri</div>
            <div class="calendar-day-header">Sat</div>
        </div>
    </div>
    
    <!-- Content for selected date -->
    <div class="content-panel">
        <div class="panel-header">
            <h3>📖 <?php echo date('F j, Y', strtotime($selected_date)); ?></h3>
            <a href="diary.php?date=<?php echo $selected_date; ?>" class="add-btn">✏️ Write Entry</a>
        </div>
        
        <!-- Diary Entry -->
        <div class="diary-section">
            <h4>📝 Diary Entry</h4>
            <?php if($diary_entry): ?>
                <div class="diary-entry">
                    <div class="diary-mood">
                        <?php echo $diary_entry['emoji_icon']; ?> <?php echo $diary_entry['mood_name']; ?>
                    </div>
                    <div class="diary-content">
                        <?php echo nl2br(htmlspecialchars($diary_entry['content'])); ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <span>📭</span>
                    <p>No diary entry for this day.</p>
                    <a href="diary.php?date=<?php echo $selected_date; ?>" style="color: #D86487;">Write something →</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- To-Do List -->
        <div style="margin-top: 25px;">
            <h4>✅ To-Do List</h4>
            <?php if($todos->num_rows > 0): ?>
                <?php while($todo = $todos->fetch_assoc()): ?>
                    <div class="todo-item">
                        <div class="todo-check <?php echo $todo['is_done'] ? 'completed' : ''; ?>"></div>
                        <div class="todo-text <?php echo $todo['is_done'] ? 'completed' : ''; ?>">
                            <?php echo htmlspecialchars($todo['title']); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state" style="padding: 20px;">
                    <span>✅</span>
                    <p>No tasks for this day.</p>
                    <a href="todos.php" style="color: #D86487;">Add a task →</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Habits -->
        <div style="margin-top: 25px;">
            <h4>🌿 Habits</h4>
            <?php if($habits->num_rows > 0): ?>
                <?php while($habit = $habits->fetch_assoc()): ?>
                    <div class="habit-item">
                        <div class="todo-check <?php echo $habit['completed'] ? 'completed' : ''; ?>"></div>
                        <div class="todo-text <?php echo $habit['completed'] ? 'completed' : ''; ?>">
                            <?php echo htmlspecialchars($habit['name']); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state" style="padding: 20px;">
                    <span>🌿</span>
                    <p>No habits added yet.</p>
                    <a href="habits.php" style="color: #D86487;">Add a habit →</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="diary.php?date=<?php echo $selected_date; ?>" class="quick-btn">📖 Write Diary</a>
            <a href="todos.php" class="quick-btn">✅ Manage Tasks</a>
            <a href="habits.php" class="quick-btn">🌿 Track Habits</a>
        </div>
    </div>
</div>

<script>
let currentMonth = <?php echo date('n'); ?>;
let currentYear = <?php echo date('Y'); ?>;

function loadCalendar() {
    fetch(`get_calendar.php?month=${currentMonth}&year=${currentYear}`)
        .then(response => response.json())
        .then(data => {
            const calendar = document.getElementById('calendar');
            const oldDays = calendar.querySelectorAll('.calendar-day');
            oldDays.forEach(day => day.remove());
            
            // Add empty cells for first day
            for(let i = 0; i < data.firstDayOfWeek; i++) {
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'calendar-day';
                emptyDiv.style.background = 'transparent';
                emptyDiv.style.cursor = 'default';
                calendar.appendChild(emptyDiv);
            }
            
            // Add days
            for(let i = 1; i <= data.daysInMonth; i++) {
                const dateStr = `${currentYear}-${String(currentMonth).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
                const dayDiv = document.createElement('div');
                dayDiv.className = 'calendar-day';
                dayDiv.innerHTML = i;
                
                if(data.hasEntries.includes(dateStr)) {
                    dayDiv.classList.add('has-entry');
                }
                
                if(dateStr === '<?php echo $selected_date; ?>') {
                    dayDiv.classList.add('selected');
                }
                
                if(dateStr === new Date().toISOString().split('T')[0]) {
                    dayDiv.classList.add('today');
                }
                
                dayDiv.onclick = () => {
                    window.location.href = `?date=${dateStr}`;
                };
                
                calendar.appendChild(dayDiv);
            }
        });
}

function changeMonth(delta) {
    currentMonth += delta;
    if(currentMonth < 1) {
        currentMonth = 12;
        currentYear--;
    }
    if(currentMonth > 12) {
        currentMonth = 1;
        currentYear++;
    }
    loadCalendar();
}

function goToToday() {
    const today = new Date();
    currentMonth = today.getMonth() + 1;
    currentYear = today.getFullYear();
    window.location.href = `?date=${today.toISOString().split('T')[0]}`;
}

loadCalendar();
</script>

</body>
</html>