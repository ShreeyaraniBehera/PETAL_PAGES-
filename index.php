<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Petal Pages - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:wght@400;700&family=Great+Vibes&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: linear-gradient(135deg, #F1DFDD, #EEAAC3);
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .script { font-family: 'Great Vibes', cursive; font-size: 2em; }
        .card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 400px;
        }
        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 10px;
            border: 1px solid #ddd;
        }
        button {
            width: 100%;
            background: #76172C;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 30px;
            cursor: pointer;
            margin: 10px 0;
        }
        button:hover { background: #D86487; }
        hr { margin: 20px 0; }
        h1 { text-align: center; margin-bottom: 20px; color: #4A202A; }
    </style>
</head>
<body>
<div class="card">
    <h1><span class="script">your</span><br>Petal Pages</h1>
    
    <form method="POST" action="config/db.php">
        <input type="hidden" name="action" value="login">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    
    <hr>
    
    <form method="POST" action="config/db.php">
        <input type="hidden" name="action" value="register">
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Register</button>
    </form>
</div>
</body>
</html>