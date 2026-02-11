<?php
session_start();
require_once '../../private/db_connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    // Enregistrer la notification en base
    $stmt = $pdo->prepare("
        INSERT INTO notifications 
        (user_id, title, message, type, is_read, created_at)
        VALUES (?, ?, ?, ?, 0, NOW())
    ");
    $stmt->execute([
        $user_id,
        $input['title'] ?? 'Notification WaveTalk',
        $input['message'] ?? '',
        $input['type'] ?? 'system'
    ]);
    
    $notification_id = $pdo->lastInsertId();
    
    // Si l'utilisateur a accepté les notifications push, envoyer via service web
    $stmt = $pdo->prepare("SELECT push_subscription FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    $response = [
        'success' => true,
        'notification_id' => $notification_id,
        'push_sent' => false
    ];
    
    if ($user['push_subscription']) {
        // Ici, vous intégreriez un service comme Firebase Cloud Messaging
        // Pour l'instant, on simule l'envoi
        $response['push_sent'] = true;
        $response['push_message'] = 'Notification enregistrée pour envoi push';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>