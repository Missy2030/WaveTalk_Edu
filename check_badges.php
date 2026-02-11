<?php
/**
 * Script d'attribution automatique des badges
 * À exécuter via cron ou appelé après chaque progression
 */

require_once '../../private/db_connection.php';

function check_and_award_badges($user_id, $pdo) {
    $awarded_badges = [];
    
    try {
        // Récupérer les statistiques de l'utilisateur
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT CASE WHEN up.completed = 1 THEN up.course_id END) as courses_completed,
                SUM(up.listened_time) as total_listening_time,
                COUNT(DISTINCT up.course_id) as courses_started,
                AVG(up.quiz_score) as avg_quiz_score
            FROM user_progress up
            WHERE up.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch();
        
        // Récupérer les badges déjà débloqués
        $stmt = $pdo->prepare("
            SELECT badge_id FROM user_badges WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $unlocked_badge_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // BADGE 1: Premier pas - Compléter le premier chapitre
        if ($stats['courses_started'] >= 1 && !in_array(1, $unlocked_badge_ids)) {
            $stmt = $pdo->prepare("
                INSERT INTO user_badges (user_id, badge_id, unlocked_at)
                VALUES (?, 1, NOW())
            ");
            $stmt->execute([$user_id]);
            $awarded_badges[] = 'Premier pas';
        }
        
        // BADGE 2: Quiz Master - Obtenir un score parfait à un quiz
        if ($stats['avg_quiz_score'] >= 95 && !in_array(2, $unlocked_badge_ids)) {
            $stmt = $pdo->prepare("
                INSERT INTO user_badges (user_id, badge_id, unlocked_at)
                VALUES (?, 2, NOW())
            ");
            $stmt->execute([$user_id]);
            $awarded_badges[] = 'Quiz Master';
        }
        
        // BADGE 3: Auditeur assidu - Écouter 5 heures de contenu
        $hours_listened = ($stats['total_listening_time'] ?? 0) / 3600;
        if ($hours_listened >= 5 && !in_array(3, $unlocked_badge_ids)) {
            $stmt = $pdo->prepare("
                INSERT INTO user_badges (user_id, badge_id, unlocked_at)
                VALUES (?, 3, NOW())
            ");
            $stmt->execute([$user_id]);
            $awarded_badges[] = 'Auditeur assidu';
        }
        
        // BADGE 4: Niveau 5 - Atteindre le niveau 5
        $stmt = $pdo->prepare("SELECT level FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (($user['level'] ?? 0) >= 5 && !in_array(4, $unlocked_badge_ids)) {
            $stmt = $pdo->prepare("
                INSERT INTO user_badges (user_id, badge_id, unlocked_at)
                VALUES (?, 4, NOW())
            ");
            $stmt->execute([$user_id]);
            $awarded_badges[] = 'Niveau 5';
        }
        
        // Badge 5: Découvreur - Commencer 3 cours
        if ($stats['courses_started'] >= 3 && !in_array(5, $unlocked_badge_ids)) {
            $stmt = $pdo->prepare("
                INSERT INTO user_badges (user_id, badge_id, unlocked_at)
                VALUES (?, 5, NOW())
            ");
            $stmt->execute([$user_id]);
            $awarded_badges[] = 'Découvreur';
        }
        
        return [
            'success' => true,
            'awarded_badges' => $awarded_badges,
            'stats' => $stats
        ];
        
    } catch (Exception $e) {
        error_log("Erreur check_and_award_badges: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Si appelé directement (pour tester)
if (isset($_GET['user_id'])) {
    header('Content-Type: application/json');
    $user_id = intval($_GET['user_id']);
    $result = check_and_award_badges($user_id, $pdo);
    echo json_encode($result, JSON_PRETTY_PRINT);
}
?>