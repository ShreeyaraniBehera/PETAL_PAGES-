<?php
session_start();
if (!isset($_SESSION['user_id'])) header("Location: index.php");

$conn = new mysqli("localhost", "root", "", "petal_pages");

// Get mood entries for the current month
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Get all moods first
$all_moods_result = $conn->query("SELECT * FROM moods ORDER BY id");
$all_moods = [];
$mood_emojis = [];
$mood_colors = [];
while($row = $all_moods_result->fetch_assoc()) {
    $all_moods[] = $row;
    $mood_emojis[$row['name']] = $row['emoji_icon'];
    $mood_colors[$row['name']] = $row['color_hex'];
}

// Get mood entries for the selected month
$stmt = $conn->prepare("
    SELECT d.date, m.name, m.emoji_icon, m.color_hex 
    FROM diary_entries d 
    JOIN moods m ON d.mood_id = m.id 
    WHERE d.user_id = ? AND MONTH(d.date) = ? AND YEAR(d.date) = ?
    ORDER BY d.date
");
$stmt->bind_param("iii", $_SESSION['user_id'], $month, $year);
$stmt->execute();
$mood_entries = $stmt->get_result();

// Calculate mood statistics
$mood_counts = [];
foreach($all_moods as $mood) {
    $mood_counts[$mood['name']] = 0;
}

$total_entries = 0;
$mood_entries_array = [];
while($entry = $mood_entries->fetch_assoc()) {
    if(isset($mood_counts[$entry['name']])) {
        $mood_counts[$entry['name']]++;
        $total_entries++;
    }
    $mood_entries_array[] = $entry;
}
$mood_entries->data_seek(0);

// Prepare data for chart
$chart_labels = [];
$chart_data = [];
$chart_colors = [];
foreach($mood_counts as $name => $count) {
    if($count > 0) {
        $chart_labels[] = $mood_emojis[$name] . ' ' . $name;
        $chart_data[] = $count;
        $chart_colors[] = $mood_colors[$name];
    }
}

// Get daily mood data for line chart
$daily_moods = [];
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
for($i = 1; $i <= $days_in_month; $i++) {
    $daily_moods[$i] = null;
}
foreach($mood_entries_array as $entry) {
    $day = (int)date('j', strtotime($entry['date']));
    $daily_moods[$day] = $entry;
}

// Get dominant mood
$dominant_mood = '';
$dominant_count = 0;
foreach($mood_counts as $name => $count) {
    if($count > $dominant_count) {
        $dominant_count = $count;
        $dominant_mood = $name;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Petal Pages - Mood Garden</title>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:wght@400;700&family=Great+Vibes&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .sidebar a:hover { background: #D86487; }
        
        .sidebar h3 {
            color: white;
            margin-bottom: 30px;
        }
        
        .main-content {
            margin-left: 240px;
            padding: 30px;
        }
        
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
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            transition: 0.3s;
            cursor: pointer;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-emoji {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #D86487;
        }
        
        .stat-label {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        
        /* Charts Row */
        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
        }
        
        .chart-card h3 {
            color: #4A202A;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #F1DFDD;
        }
        
        canvas {
            max-height: 280px;
            max-width: 100%;
        }
        
        /* Month Selector */
        .month-selector {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .month-nav {
            display: flex;
            gap: 10px;
        }
        
        .month-nav button {
            background: #D86487;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 25px;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .month-nav button:hover {
            background: #76172C;
        }
        
        .current-month {
            font-size: 18px;
            font-weight: 500;
            color: #4A202A;
        }
        
        /* Mood Timeline */
        .timeline-section {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .timeline-section h3 {
            color: #4A202A;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #F1DFDD;
        }
        
        .mood-timeline {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
        }
        
        .mood-day {
            width: 45px;
            height: 45px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #F9F9F9;
            cursor: pointer;
            transition: 0.3s;
            font-size: 12px;
        }
        
        .mood-day:hover {
            transform: scale(1.1);
        }
        
        .mood-day .day-number {
            font-size: 14px;
            font-weight: bold;
        }
        
        .mood-day .mood-emoji {
            font-size: 18px;
        }
        
        .mood-day.empty {
            background: #F1DFDD;
            opacity: 0.6;
        }
        
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #F1DFDD;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
        }
        
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .empty-state span {
            font-size: 50px;
            display: block;
            margin-bottom: 15px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .chart-card, .stats-grid, .timeline-section {
            animation: fadeIn 0.5s ease;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding: 20px 10px;
            }
            .sidebar h3 {
                font-size: 12px;
            }
            .sidebar a {
                font-size: 12px;
                text-align: center;
            }
            .main-content {
                margin-left: 90px;
                padding: 15px;
            }
            .charts-row {
                grid-template-columns: 1fr;
            }
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
        <h2><span class="script">your</span> mood garden</h2>
        <p>Track your emotions and discover patterns in your mood journey</p>
    </div>
    
    <!-- Month Selector -->
    <div class="month-selector">
        <div class="current-month">
            📅 <?php echo date('F Y', strtotime("$year-$month-01")); ?>
        </div>
        <div class="month-nav">
            <button onclick="changeMonth(-1)">◀ Previous</button>
            <button onclick="goToCurrentMonth()">📆 Current</button>
            <button onclick="changeMonth(1)">Next ▶</button>
        </div>
    </div>
    
    <?php if($total_entries > 0): ?>
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-emoji">📝</div>
                <div class="stat-number"><?php echo $total_entries; ?></div>
                <div class="stat-label">Total Entries</div>
            </div>
            <div class="stat-card">
                <div class="stat-emoji"><?php echo $mood_emojis[$dominant_mood] ?? '🌸'; ?></div>
                <div class="stat-number"><?php echo $dominant_count; ?></div>
                <div class="stat-label">Most Frequent Mood</div>
                <div style="font-size: 12px; margin-top: 5px;"><?php echo $dominant_mood; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-emoji">📊</div>
                <div class="stat-number"><?php echo round(($total_entries / $days_in_month) * 100); ?>%</div>
                <div class="stat-label">Journaling Consistency</div>
            </div>
        </div>
        
        <!-- Charts -->
        <div class="charts-row">
            <div class="chart-card">
                <h3>🎨 Mood Distribution</h3>
                <canvas id="moodPieChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>📈 Mood Trend</h3>
                <canvas id="moodLineChart"></canvas>
            </div>
        </div>
        
        <!-- Mood Timeline -->
        <div class="timeline-section">
            <h3>📅 Mood Timeline (<?php echo date('F Y', strtotime("$year-$month-01")); ?>)</h3>
            <div class="mood-timeline" id="moodTimeline">
                <?php for($day = 1; $day <= $days_in_month; $day++): 
                    $mood = $daily_moods[$day];
                ?>
                    <div class="mood-day <?php echo $mood ? '' : 'empty'; ?>" 
                         onclick="viewDiaryEntry(<?php echo $day; ?>)"
                         title="<?php echo $mood ? $mood['name'] : 'No entry'; ?>">
                        <div class="day-number"><?php echo $day; ?></div>
                        <div class="mood-emoji"><?php echo $mood ? $mood['emoji_icon'] : '❓'; ?></div>
                    </div>
                <?php endfor; ?>
            </div>
            <div class="legend">
                <?php foreach($all_moods as $mood): ?>
                    <div class="legend-item">
                        <div class="legend-color" style="background: <?php echo $mood['color_hex']; ?>"></div>
                        <span><?php echo $mood['emoji_icon']; ?> <?php echo $mood['name']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <span>🌱</span>
            <p>No mood entries yet for <?php echo date('F Y', strtotime("$year-$month-01")); ?></p>
            <p style="font-size: 14px; margin-top: 10px;">
                <a href="diary.php" style="color: #D86487;">Write a diary entry</a> to start tracking your mood!
            </p>
        </div>
    <?php endif; ?>
    
    <!-- Mood Tips -->
    <div style="background: #FFF5F3; border-radius: 15px; padding: 15px; margin-top: 20px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 24px;">💡</span>
            <div>
                <strong>Mood Tracking Tip</strong>
                <p style="font-size: 13px; color: #666; margin-top: 5px;">
                    Writing about your feelings helps process emotions. Try to journal daily for the best insights!
                </p>
            </div>
        </div>
    </div>
</div>

<script>
let currentMonth = <?php echo $month; ?>;
let currentYear = <?php echo $year; ?>;

// Mood Distribution Chart (Pie)
<?php if($total_entries > 0 && !empty($chart_data)): ?>
const pieCtx = document.getElementById('moodPieChart').getContext('2d');
new Chart(pieCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($chart_data); ?>,
            backgroundColor: <?php echo json_encode($chart_colors); ?>,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { font: { size: 11 } }
            }
        }
    }
});

// Mood Trend Chart (Line)
const lineData = [];
const lineLabels = [];
<?php for($day = 1; $day <= $days_in_month; $day++): 
    $mood = $daily_moods[$day];
    $moodValue = 0;
    if($mood) {
        foreach($all_moods as $index => $m) {
            if($m['name'] == $mood['name']) {
                $moodValue = $index + 1;
                break;
            }
        }
    }
?>
lineLabels.push(<?php echo $day; ?>);
lineData.push(<?php echo $moodValue; ?>);
<?php endfor; ?>

const lineCtx = document.getElementById('moodLineChart').getContext('2d');
new Chart(lineCtx, {
    type: 'line',
    data: {
        labels: lineLabels,
        datasets: [{
            label: 'Mood Level',
            data: lineData,
            borderColor: '#D86487',
            backgroundColor: 'rgba(216, 100, 135, 0.1)',
            tension: 0.3,
            fill: true,
            pointBackgroundColor: function(context) {
                const value = context.raw;
                const moodNames = <?php echo json_encode(array_column($all_moods, 'name')); ?>;
                const moodColors = <?php echo json_encode(array_column($all_moods, 'color_hex')); ?>;
                if(value > 0 && value <= moodColors.length) {
                    return moodColors[value - 1];
                }
                return '#ccc';
            },
            pointRadius: 6,
            pointHoverRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                max: <?php echo count($all_moods); ?>,
                ticks: {
                    stepSize: 1,
                    callback: function(value) {
                        const moodNames = <?php echo json_encode(array_column($all_moods, 'emoji_icon') . array_column($all_moods, 'name')); ?>;
                        const moods = <?php echo json_encode($all_moods); ?>;
                        if(moods[value - 1]) {
                            return moods[value - 1].emoji_icon + ' ' + moods[value - 1].name;
                        }
                        return value;
                    }
                }
            },
            x: {
                title: { display: true, text: 'Day of Month' }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const moods = <?php echo json_encode($all_moods); ?>;
                        if(moods[context.raw - 1]) {
                            return moods[context.raw - 1].emoji_icon + ' ' + moods[context.raw - 1].name;
                        }
                        return 'No entry';
                    }
                }
            }
        }
    }
});
<?php endif; ?>

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
    window.location.href = `moods.php?month=${currentMonth}&year=${currentYear}`;
}

function goToCurrentMonth() {
    const today = new Date();
    window.location.href = `moods.php?month=${today.getMonth() + 1}&year=${today.getFullYear()}`;
}

function viewDiaryEntry(day) {
    const date = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    window.location.href = `dashboard.php?date=${date}`;
}
</script>

</body>
</html>