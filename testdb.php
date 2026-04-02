<?php
$conn = new mysqli("localhost", "root", "", "petal_pages");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Database connected successfully!<br>";
}

$result = $conn->query("SELECT * FROM moods");
if ($result) {
    echo "Moods table has " . $result->num_rows . " rows.<br>";
    while($row = $result->fetch_assoc()) {
        echo $row['emoji_icon'] . " - " . $row['name'] . "<br>";
    }
} else {
    echo "Error: " . $conn->error;
}
?>