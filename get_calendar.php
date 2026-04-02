<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$conn = new mysqli("localhost", "root", "", "petal_pages");
$month = $_GET['month'];
$year = $_GET['year'];

// Get days with diary entries
$stmt = $conn->prepare("SELECT DISTINCT date FROM diary_entries WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
$stmt->bind_param("iii", $_SESSION['user_id'], $month, $year);
$stmt->execute();
$result = $stmt->get_result();

$hasEntries = [];
while($row = $result->fetch_assoc()) {
    $hasEntries[] = $row['date'];
}

$response = [
    'daysInMonth' => cal_days_in_month(CAL_GREGORIAN, $month, $year),
    'firstDayOfWeek' => date('w', strtotime("$year-$month-01")),
    'hasEntries' => $hasEntries
];

echo json_encode($response);
?>