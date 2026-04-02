<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "petal_pages");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// REGISTER
if (isset($_POST['action']) && $_POST['action'] == "register") {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        echo "User already exists!";
        exit();
    }

    $sql = "INSERT INTO users (username, email, password_hash) VALUES ('$username', '$email', '$password')";
    if ($conn->query($sql)) {
        $user = $conn->query("SELECT * FROM users WHERE email='$email'")->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        header("Location: ../dashboard.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

// LOGIN
if (isset($_POST['action']) && $_POST['action'] == "login") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $res = $conn->query("SELECT * FROM users WHERE email='$email'");
    $user = $res->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: ../dashboard.php");
        exit();
    } else {
        echo "Invalid email or password!";
        echo "<br><a href='../index.php'>Go back</a>";
    }
}

// ========== SAVE DIARY ENTRY ==========
if (isset($_POST['action']) && $_POST['action'] == "save_diary") {
    $user_id = $_POST['user_id'];
    $date = $_POST['date'];
    $mood_id = $_POST['mood_id'];
    $bg_theme = $_POST['bg_theme'];
    $content = $_POST['content'];
    
    $stmt = $conn->prepare("INSERT INTO diary_entries (user_id, date, mood_id, bg_theme, content) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isiss", $user_id, $date, $mood_id, $bg_theme, $content);
    
    if ($stmt->execute()) {
        header("Location: ../diary.php?success=Entry saved!");
    } else {
        header("Location: ../diary.php?error=" . $conn->error);
    }
    exit();
}
// ========== UPDATE DIARY ENTRY ==========
if (isset($_POST['action']) && $_POST['action'] == "update_diary") {
    $entry_id = $_POST['entry_id'];
    $user_id = $_POST['user_id'];
    $date = $_POST['date'];
    $mood_id = $_POST['mood_id'];
    $bg_theme = $_POST['bg_theme'];
    $content = $_POST['content'];
    
    $stmt = $conn->prepare("UPDATE diary_entries SET date = ?, mood_id = ?, bg_theme = ?, content = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sisssi", $date, $mood_id, $bg_theme, $content, $entry_id, $user_id);
    
    if ($stmt->execute()) {
        header("Location: ../diary.php?success=Entry updated!");
    } else {
        header("Location: ../diary.php?error=" . urlencode($conn->error));
    }
    exit();
}
// ========== ADD HABIT ==========
if (isset($_POST['action']) && $_POST['action'] == "add_habit") {
    $user_id = $_POST['user_id'];
    $habit_name = $_POST['habit'];
    
    $stmt = $conn->prepare("INSERT INTO habits (user_id, name) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $habit_name);
    
    if ($stmt->execute()) {
        header("Location: ../habits.php?success=Habit added!");
    } else {
        header("Location: ../habits.php?error=" . $conn->error);
    }
    exit();
}

// ========== ADD TODO ==========
if (isset($_POST['action']) && $_POST['action'] == "add_todo") {
    $user_id = $_POST['user_id'];
    $task = $_POST['task'];
    
    $stmt = $conn->prepare("INSERT INTO todos (user_id, title, date, is_done) VALUES (?, ?, CURDATE(), 0)");
    $stmt->bind_param("is", $user_id, $task);
    
    if ($stmt->execute()) {
        header("Location: ../todos.php?success=Task added!");
    } else {
        header("Location: ../todos.php?error=" . $conn->error);
    }
    exit();
}
?>