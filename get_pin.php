<?php

session_start();
header('Content-Type: application/json');

//Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$pins = [];

try {
    //Connect to the database
    $db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

 
    
    $stmt = $db->prepare("SELECT id, lat, lng, crop_suggestion FROM pins WHERE user_id = ?");
    $stmt->execute([$user_id]);

    //Fetch all results
    $pins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //Send the pins back as JSON
    echo json_encode($pins);

} catch (Exception $e) {
    http_response_code(500); 
    echo json_encode(['error' => $e->getMessage()]);
}