<?php
// track_progress.php - Sauvegarde de progression
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function jsonError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function jsonSuccess($message, $data = []) {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ]);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonError('Méthode non autorisée', 405);
    }
    
    require_once '../../private/db_connection.php';
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonError('JSON invalide');
    }
    
    $user_id = intval($data['user_id'] ?? 0);
    $course_id = intval($data['course_id'] ?? 0);
    $listened_time = intval($data['listened_time'] ?? 0);
    $current_position = floatval($data['current_position'] ?? 0);
    $is_completed = boolval($data['is_completed'] ?? false);
    
    if ($user_id <= 0 || $course_id <= 0) {
        jsonError('IDs invalides');
    }
    
    // Vérifier si existe
    $stmt = $pdo->prepare("SELECT id, listened_time FROM user_progress WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$user_id, $course_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update
        $new_time = max($existing['listened_time'], $listened_time);
        $stmt = $pdo->prepare("
            UPDATE user_progress 
            SET listened_time = ?, last_position = ?, completed = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$new_time, $current_position, $is_completed ? 1 : 0, $existing['id']]);
        $action = 'updated';
    } else {
        // Insert
        $stmt = $pdo->prepare("
            INSERT INTO user_progress 
            (user_id, course_id, listened_time, last_position, completed, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$user_id, $course_id, $listened_time, $current_position, $is_completed ? 1 : 0]);
        $action = 'created';
    }
    
    jsonSuccess("Progression $action", [
        'listened_time' => $listened_time,
        'is_completed' => $is_completed
    ]);
    
} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    jsonError('Erreur base de données', 500);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    jsonError('Erreur serveur', 500);
}
?>