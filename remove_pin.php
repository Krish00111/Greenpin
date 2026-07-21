<?php

session_start();
header('Content-Type: application/json');

// Check user e login kar u ke nai
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get the pin ID from the JSON input
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$pin_id = $data['id'] ?? 0;

if ($pin_id == 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid Pin ID']);
    exit;
}

try {
    // Connect to the database
    $db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //Prepare and execute the delete query
    $stmt = $db->prepare("DELETE FROM pins WHERE id = ? AND user_id = ?");
    $stmt->execute([ $pin_id, $_SESSION['user_id'] ]);

    //Check if a row was actually deleted
    if ($stmt->rowCount() > 0) {
        echo json_encode(['ok' => true]);
    } else {
        
        echo json_encode(['ok' => false, 'error' => 'Pin not found or no permission']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}