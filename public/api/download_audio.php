<?php
/**
 * API de téléchargement audio - VERSION CORRIGÉE
 * Permet de télécharger les fichiers audio des cours
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../private/db_connection.php';

// Vérifier la connexion
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$user_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($course_id === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de cours invalide']);
    exit;
}

try {
    // Récupérer les informations du cours
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            title, 
            audio_file, 
            audio_url, 
            is_downloadable,
            download_count
        FROM courses 
        WHERE id = ? AND is_active = 1
    ");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Cours non trouvé']);
        exit;
    }
    
    // Vérifier si le cours est téléchargeable
    if (!$course['is_downloadable']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Ce cours n\'est pas disponible au téléchargement']);
        exit;
    }
    
    // Déterminer l'URL du fichier audio
    $audio_path = '';
    if (!empty($course['audio_file'])) {
        $audio_path = $course['audio_file'];
    } elseif (!empty($course['audio_url'])) {
        $audio_path = $course['audio_url'];
    }
    
    if (empty($audio_path)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Aucun fichier audio disponible']);
        exit;
    }
    
    // Nettoyer le chemin
    $audio_path = str_replace('/home/s', '', $audio_path);
    
    // Vérifier si l'utilisateur a déjà téléchargé ce cours
    $stmt = $pdo->prepare("
        SELECT id, downloaded_at 
        FROM course_downloads 
        WHERE user_id = ? AND course_id = ?
    ");
    $stmt->execute([$user_id, $course_id]);
    $existing_download = $stmt->fetch();
    
    if ($existing_download) {
        // Mettre à jour la date du dernier accès
        $stmt = $pdo->prepare("
            UPDATE course_downloads 
            SET last_accessed = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$existing_download['id']]);
    } else {
        // Enregistrer un nouveau téléchargement
        $stmt = $pdo->prepare("
            INSERT INTO course_downloads (user_id, course_id, downloaded_at, last_accessed)
            VALUES (?, ?, NOW(), NOW())
        ");
        $stmt->execute([$user_id, $course_id]);
        
        // Incrémenter le compteur de téléchargements du cours
        $stmt = $pdo->prepare("
            UPDATE courses 
            SET download_count = download_count + 1 
            WHERE id = ?
        ");
        $stmt->execute([$course_id]);
    }
    
    // Retourner le succès avec les informations
    echo json_encode([
        'success' => true,
        'message' => 'Téléchargement enregistré',
        'download_url' => $audio_path,
        'filename' => sanitize_filename($course['title']) . '.m4a',
        'already_downloaded' => !empty($existing_download)
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur download_audio: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur lors du téléchargement'
    ]);
}

/**
 * Nettoyer un nom de fichier
 */
function sanitize_filename($filename) {
    // Remplacer les caractères spéciaux
    $filename = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $filename);
    // Remplacer les espaces multiples par un seul
    $filename = preg_replace('/\s+/', '_', $filename);
    // Limiter la longueur
    return substr($filename, 0, 100);
}
?>