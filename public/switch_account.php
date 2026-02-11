<?php
require_once __DIR__ . '/../private/db_connection.php';

// Vérifier connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_role = $_SESSION['user_role'];

// Si on veut switcher vers un compte enfant
if (isset($_GET['switch_to']) && isset($_GET['target_id'])) {
    $target_id = intval($_GET['target_id']);
    $switch_to = $_GET['switch_to'];
    
    // Parent veut se connecter comme son enfant
    if ($current_role === 'parent' && $switch_to === 'student') {
        // Vérifier que c'est bien son enfant
        $stmt = $pdo->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email, u.role
            FROM parent_children pc
            JOIN users u ON pc.student_id = u.id
            WHERE pc.parent_id = ? AND pc.student_id = ? AND pc.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$current_user_id, $target_id]);
        $target_user = $stmt->fetch();
        
        if ($target_user) {
            // Sauvegarder l'identité du parent
            $_SESSION['original_user_id'] = $current_user_id;
            $_SESSION['original_user_role'] = $current_role;
            $_SESSION['original_user_name'] = $_SESSION['user_name'];
            
            // Passer à l'identité de l'enfant
            $_SESSION['user_id'] = $target_user['id'];
            $_SESSION['user_name'] = $target_user['first_name'];
            $_SESSION['user_role'] = 'student';
            $_SESSION['is_switched'] = true;
            
            // Redirection vers le dashboard élève
            header('Location: student/dashboard.php');
            exit();
        } else {
            $_SESSION['error'] = "Vous n'êtes pas autorisé à accéder à ce compte.";
            header('Location: parent/dashboard.php');
            exit();
        }
    }
}

// Si on veut revenir au compte parent
if (isset($_GET['switch_back']) && isset($_SESSION['original_user_id'])) {
    // Restaurer l'identité du parent
    $_SESSION['user_id'] = $_SESSION['original_user_id'];
    $_SESSION['user_name'] = $_SESSION['original_user_name'];
    $_SESSION['user_role'] = $_SESSION['original_user_role'];
    
    // Nettoyer les variables de switch
    unset($_SESSION['original_user_id']);
    unset($_SESSION['original_user_role']);
    unset($_SESSION['original_user_name']);
    unset($_SESSION['is_switched']);
    
    $_SESSION['success'] = "Vous êtes de retour sur votre compte parent.";
    
    header('Location: parent/dashboard.php');
    exit();
}

// Redirection par défaut
header('Location: index.php');
exit();
?>