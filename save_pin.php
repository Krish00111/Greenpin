<?php

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok'=>false, 'error'=>'Not authenticated']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    echo json_encode(['ok'=>false, 'error'=>'Invalid input']);
    exit;
}

$lat = floatval($data['lat']);
$lng = floatval($data['lng']);
$suggestion = $data['suggestion'] ?? '';

try {
    $db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("INSERT INTO pins (user_id, lat, lng, crop_suggestion, metadata) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([ $_SESSION['user_id'], $lat, $lng, $suggestion, null ]);
    echo json_encode(['ok'=>true]);
} catch (Exception $e) {
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}

