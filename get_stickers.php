<?php
header('Content-Type: application/json');

$category = isset($_GET['category']) ? $_GET['category'] : 'food';

// Security: Only allow specific categories
$allowed_categories = ['food', 'nature', 'study', 'characters'];
if (!in_array($category, $allowed_categories)) {
    echo json_encode([]);
    exit();
}

$dir = "assets/$category/";
$stickers = [];

if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'])) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                $name = str_replace(['_', '-'], ' ', $name);
                $stickers[] = [
                    'file' => $file,
                    'name' => ucfirst($name)
                ];
            }
        }
    }
}

echo json_encode($stickers);
?>