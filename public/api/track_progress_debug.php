<?php
// track_progress_debug.php - VERSION DEBUG avec erreurs détaillées
// Place dans public/api/ et teste avec celle-ci pour voir l'erreur exacte

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function jsonError($message, $code = 400, $details = null) {
    http_response_code($code);
    echo json_encode([
        'success' => false, 
        'error' => $message,
        'details' => $details,
        'debug' => [
            'file' => __FILE__,
            'time' => date('Y-m-d H:i:s')
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

function jsonSuccess($message, $data = []) {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonError('Méthode non autorisée', 405);
    }
    
    // Connexion BDD
    try {
        require_once '../../private/db_connection.php';
    } catch (Exception $e) {
        jsonError('Erreur connexion BDD', 500, $e->getMessage());
    }
    
    // Récupérer données
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonError('JSON invalide', 400, json_last_error_msg());
    }
    
    // Valider
    $user_id = intval($data['user_id'] ?? 0);
    $course_id = intval($data['course_id'] ?? 0);
    $listened_time = intval($data['listened_time'] ?? 0);
    $current_position = floatval($data['current_position'] ?? 0);
    $is_completed = boolval($data['is_completed'] ?? false);
    
    if ($user_id <= 0 || $course_id <= 0) {
        jsonError('IDs invalides', 400, "user_id=$user_id, course_id=$course_id");
    }
    
    // Vérifier que le user existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        jsonError('User inexistant', 400, "User ID $user_id n'existe pas");
    }
    
    // Vérifier que le cours existe
    $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    if (!$stmt->fetch()) {
        jsonError('Cours inexistant', 400, "Course ID $course_id n'existe pas");
    }
    
    // Vérifier si progression existe
    $stmt = $pdo->prepare("SELECT id, listened_time FROM user_progress WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$user_id, $course_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // UPDATE
        $new_time = max($existing['listened_time'], $listened_time);
        
        $stmt = $pdo->prepare("
            UPDATE user_progress 
            SET listened_time = ?, 
                last_position = ?, 
                completed = ?, 
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $new_time, 
            $current_position, 
            $is_completed ? 1 : 0, 
            $existing['id']
        ]);
        
        if (!$result) {
            jsonError('Erreur UPDATE', 500, $stmt->errorInfo());
        }
        
        jsonSuccess('Progression mise à jour', [
            'action' => 'updated',
            'id' => $existing['id'],
            'listened_time' => $new_time,
            'is_completed' => $is_completed
        ]);
        
    } else {
        // INSERT
        $stmt = $pdo->prepare("
            INSERT INTO user_progress 
            (user_id, course_id, listened_time, last_position, completed, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $result = $stmt->execute([
            $user_id, 
            $course_id, 
            $listened_time, 
            $current_position, 
            $is_completed ? 1 : 0
        ]);
        
        if (!$result) {
            jsonError('Erreur INSERT', 500, $stmt->errorInfo());
        }
        
        jsonSuccess('Progression créée', [
            'action' => 'created',
            'id' => $pdo->lastInsertId(),
            'listened_time' => $listened_time,
            'is_completed' => $is_completed
        ]);
    }
    
} catch (PDOException $e) {
    jsonError('Erreur base de données', 500, [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'sql_state' => $e->errorInfo ?? null
    ]);
} catch (Exception $e) {
    jsonError('Erreur serveur', 500, [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>