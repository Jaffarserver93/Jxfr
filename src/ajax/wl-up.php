<?php
session_start();
if (!isset($_COOKIE['userID'])) {
    header('Location: login.php');
    exit();
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/_config.php');

header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get JSON input data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$type = $data['type'] ?? null;
$movieId = $data['movieId'] ?? null;
$animeName = $data['animeName'] ?? null;
$poster = $data['poster'] ?? null;
$subCount = $data['subCount'] ?? null;
$dubCount = $data['dubCount'] ?? null;
$animeType = $data['animeType'] ?? null;

if (!$type || !$movieId || !$animeName) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

// Ensure database connection exists
if (!isset($conn) || !$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    error_log("Database connection failed");
    exit();
}

try {
    // SQL query for insert or update
    $sql = "INSERT INTO watchlist (user_id, anime_id, anime_name, type, poster, sub_count, dub_count, anime_type, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()) 
            ON DUPLICATE KEY UPDATE 
                anime_name = ?, 
                type = ?, 
                poster = ?, 
                sub_count = ?, 
                dub_count = ?, 
                anime_type = ?, 
                updated_at = NOW()";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param(
        'issssiiissssii',
        $_COOKIE['userID'], 
        $movieId,           
        $animeName,        
        $type,             
        $poster,           
        $subCount,         
        $dubCount,         
        $animeType,        
        $animeName,        
        $type,             
        $poster,           
        $subCount,         
        $dubCount,         
        $animeType
    );

    // Execute query
    if (!$stmt->execute()) {
        throw new Exception("SQL execution failed: " . $stmt->error);
    }

    echo json_encode(['success' => true, 'message' => 'Watchlist updated successfully']);
    $stmt->close();

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error updating watchlist: ' . $e->getMessage()
    ]);
    exit();
}