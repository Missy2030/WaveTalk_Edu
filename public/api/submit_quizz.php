<?php
session_start();
require_once '../../private/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

$user_id = $_SESSION['user_id'];
$quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
$score = isset($_POST['score']) ? intval($_POST['score']) : 0;
$chapter_id = isset($_POST['chapter_id']) ? intval($_POST['chapter_id']) : 0;

try {
    // Enregistrer la tentative
    $stmt = $pdo->prepare("
        INSERT INTO quiz_attempts (user_id, quiz_id, score, attempted_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $quiz_id, $score]);
    
    // Mettre à jour la progression de l'utilisateur
    $stmt = $pdo->prepare("
        UPDATE user_progress 
        SET quiz_score = ?, 
            quiz_completed = 1,
            updated_at = NOW()
        WHERE user_id = ? AND chapter_id = ?
    ");
    $stmt->execute([$score, $user_id, $chapter_id]);
    
    // Calculer l'expérience gagnée
    $exp_gained = $score * 10; // 10 points d'exp par point de score
    
    // Ajouter l'expérience à l'utilisateur
    $stmt = $pdo->prepare("
        UPDATE users 
        SET experience = experience + ?, 
            total_quiz_score = total_quiz_score + ?,
            total_quizzes = total_quizzes + 1
        WHERE id = ?
    ");
    $stmt->execute([$exp_gained, $score, $user_id]);
    
    // Vérifier le niveau
    $stmt = $pdo->prepare("SELECT experience FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    $new_level = floor($user['experience'] / 1000) + 1;
    
    // Mettre à jour le niveau si nécessaire
    $stmt = $pdo->prepare("SELECT level FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_level = $stmt->fetch()['level'];
    
    if ($new_level > $current_level) {
        $stmt = $pdo->prepare("UPDATE users SET level = ? WHERE id = ?");
        $stmt->execute([$new_level, $user_id]);
        
        // Débloquer le badge "Niveau atteint"
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO user_badges (user_id, badge_id, unlocked_at)
            VALUES (?, 3, NOW())
        ");
        $stmt->execute([$user_id]);
        
        // Badge spécial pour le niveau 5
        if ($new_level >= 5) {
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO user_badges (user_id, badge_id, unlocked_at)
                VALUES (?, 4, NOW())
            ");
            $stmt->execute([$user_id]);
        }
    }
    
    // Badge pour 5 quiz réussis
    if ($score >= 7) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as successful_quizzes 
            FROM quiz_attempts 
            WHERE user_id = ? AND score >= 7
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        if ($result['successful_quizzes'] == 5) {
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO user_badges (user_id, badge_id, unlocked_at)
                VALUES (?, 5, NOW())
            ");
            $stmt->execute([$user_id]);
        }
    }
    
    echo json_encode(['success' => true, 'exp_gained' => $exp_gained, 'level_up' => ($new_level > $current_level)]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>