<?php
session_start();
require_once '../../private/db_connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

try {
    $pdo->beginTransaction();
    
    // Synchroniser la progression
    if (isset($data['progress']) && is_array($data['progress'])) {
        foreach ($data['progress'] as $progress) {
            $stmt = $pdo->prepare("
                INSERT INTO user_progress 
                (user_id, chapter_id, listened_time, completed, quiz_score, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                listened_time = GREATEST(listened_time, VALUES(listened_time)),
                completed = GREATEST(completed, VALUES(completed)),
                quiz_score = COALESCE(VALUES(quiz_score), quiz_score),
                updated_at = NOW()
            ");
            $stmt->execute([
                $user_id,
                $progress['chapter_id'],
                $progress['listened_time'],
                $progress['completed'] ? 1 : 0,
                $progress['quiz_score'] ?? null
            ]);
        }
    }
    
    // Synchroniser les badges
    if (isset($data['badges']) && is_array($data['badges'])) {
        foreach ($data['badges'] as $badge_id) {
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO user_badges 
                (user_id, badge_id, unlocked_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$user_id, $badge_id]);
        }
    }
    
    // Synchroniser les tentatives de quiz
    if (isset($data['quiz_attempts']) && is_array($data['quiz_attempts'])) {
        foreach ($data['quiz_attempts'] as $attempt) {
            $stmt = $pdo->prepare("
                INSERT INTO quiz_attempts 
                (user_id, quiz_id, score, attempted_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([
                $user_id,
                $attempt['quiz_id'],
                $attempt['score']
            ]);
        }
    }
    
    $pdo->commit();
    
    // Récupérer les données mises à jour
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT chapter_id) as completed_chapters,
            SUM(listened_time) as total_listening_time,
            AVG(quiz_score) as avg_quiz_score
        FROM user_progress 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $updated_stats = $stmt->fetch();
    
    // Récupérer les badges
    $stmt = $pdo->prepare("
        SELECT badge_id FROM user_badges WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $badges = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'synced_at' => date('c'),
        'stats' => $updated_stats,
        'badges' => $badges,
        'message' => 'Progression synchronisée avec succès'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Sync error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>