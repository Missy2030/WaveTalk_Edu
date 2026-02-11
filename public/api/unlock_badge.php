<?php
session_start();
require_once '../../private/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

$user_id = $_SESSION['user_id'];
$badge_id = isset($_GET['badge_id']) ? intval($_GET['badge_id']) : 0;

try {
    // Vérifier si le badge existe
    $stmt = $pdo->prepare("SELECT * FROM badges WHERE id = ?");
    $stmt->execute([$badge_id]);
    $badge = $stmt->fetch();
    
    if (!$badge) {
        echo json_encode(['success' => false, 'error' => 'Badge non trouvé']);
        exit();
    }
    
    // Vérifier si l'utilisateur a déjà ce badge
    $stmt = $pdo->prepare("SELECT * FROM user_badges WHERE user_id = ? AND badge_id = ?");
    $stmt->execute([$user_id, $badge_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => true, 'already_unlocked' => true]);
        exit();
    }
    
    // Débloquer le badge
    $stmt = $pdo->prepare("
        INSERT INTO user_badges (user_id, badge_id, unlocked_at) 
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$user_id, $badge_id]);
    
    echo json_encode([
        'success' => true, 
        'badge' => [
            'name' => $badge['name'],
            'description' => $badge['description'],
            'icon' => $badge['icon']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>