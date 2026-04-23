<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/_config.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Validate database connection
if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Validate and sanitize input data
$data = json_decode(file_get_contents('php://input'), true);

$userId = filter_var($_COOKIE['userID'] ?? null, FILTER_VALIDATE_INT);
$animeId = isset($data['animeId']) ? htmlspecialchars(trim($data['animeId']), ENT_QUOTES) : null; 
$animeName = isset($data['animeName']) ? htmlspecialchars($data['animeName'], ENT_QUOTES) : null;
$poster = filter_var($data['poster'] ?? null, FILTER_SANITIZE_URL);
$subCount = filter_var($data['subCount'] ?? null, FILTER_VALIDATE_INT);
$dubCount = filter_var($data['dubCount'] ?? null, FILTER_VALIDATE_INT);
$episodeNumber = filter_var($data['episodeNumber'] ?? null, FILTER_VALIDATE_INT);

if ($userId === null || $animeId === null || !$animeName || $episodeNumber === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid required data']);
    exit();
}

// Start transaction for atomic operations
$conn->begin_transaction();

try {
    // 1. Update watch_history table
    $sqlUpdateWatchHistory = "
        INSERT INTO watch_history 
        (user_id, anime_id, anime_name, poster, sub_count, dub_count, episode_number, created_at, updated_at) 
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, NOW(), NOW()) 
        ON DUPLICATE KEY UPDATE 
        episode_number = VALUES(episode_number),
        poster = VALUES(poster),
        sub_count = VALUES(sub_count),
        dub_count = VALUES(dub_count),
        updated_at = NOW()";

    $stmtUpdateWatchHistory = $conn->prepare($sqlUpdateWatchHistory);
    if (!$stmtUpdateWatchHistory) {
        throw new Exception("Failed to prepare statement for watch_history: " . $conn->error);
    }

    $stmtUpdateWatchHistory->bind_param(
        'isssiii',
        $userId, $animeId, $animeName, $poster, $subCount, $dubCount, $episodeNumber
    );

    if (!$stmtUpdateWatchHistory->execute()) {
        throw new Exception("Error executing watch_history statement: " . $stmtUpdateWatchHistory->error);
    }
    $stmtUpdateWatchHistory->close();

    // 2. Update watched_episode table
    $sqlUpdateWatchedEpisodes = "
        INSERT INTO watched_episode 
        (user_id, anime_id, episodes_watched, updated_at) 
        VALUES 
        (?, ?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE 
        episodes_watched = IF(
            FIND_IN_SET(?, episodes_watched) > 0, 
            episodes_watched, 
            CONCAT(IFNULL(episodes_watched, ''), IF(episodes_watched IS NULL OR episodes_watched = '', '', ','), ?)
        ),
        updated_at = NOW()";

    $stmtUpdateWatchedEpisodes = $conn->prepare($sqlUpdateWatchedEpisodes);
    if (!$stmtUpdateWatchedEpisodes) {
        throw new Exception("Failed to prepare statement for watched_episode: " . $conn->error);
    }

    $episodeStr = (string)$episodeNumber;
    $stmtUpdateWatchedEpisodes->bind_param(
        'issss',
        $userId, $animeId, $episodeStr, $episodeStr, $episodeStr
    );

    if (!$stmtUpdateWatchedEpisodes->execute()) {
        throw new Exception("Error executing watched_episode statement: " . $stmtUpdateWatchedEpisodes->error);
    }
    $stmtUpdateWatchedEpisodes->close();

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Watch History Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating watch history']);
} finally {
    $conn->close();
}
?>
