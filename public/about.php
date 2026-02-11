<?php
// public/about.php
// Session gérée par db_connection.php
require_once '../private/db_connection.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À propos - WaveTalk Édu</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   
<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'parent'): ?>
<div class="account-switcher">
    <div class="current-account">
        <i class="fas fa-user"></i>
        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
    </div>
    
    <div class="switch-options">
        <?php 
        // Récupérer les enfants
        $stmt = $pdo->prepare("
            SELECT u.id, u.first_name, u.last_name 
            FROM parent_children pc
            JOIN users u ON pc.student_id = u.id
            WHERE pc.parent_id = ? AND pc.status = 'active'
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $children = $stmt->fetchAll();
        
        foreach ($children as $child): 
        ?>
        <a href="parent/view_student.php?student_id=<?php echo $child['id']; ?>" 
           class="switch-option">
            <i class="fas fa-child"></i>
            <?php echo htmlspecialchars($child['first_name']); ?>
            <span class="badge">Voir résultats</span>
        </a>
        
        <a href="switch_account.php?switch_to=student&target_id=<?php echo $child['id']; ?>" 
           class="switch-option">
            <i class="fas fa-sign-in-alt"></i>
            Se connecter comme <?php echo htmlspecialchars($child['first_name']); ?>
        </a>
        <?php endforeach; ?>
        
        <!-- Retour au compte parent si on est en mode "vue élève" -->
        <?php if (isset($_SESSION['is_switched']) && $_SESSION['is_switched']): ?>
        <a href="switch_account.php?switch_back=1" class="switch-option warning">
            <i class="fas fa-arrow-left"></i>
            Revenir à mon compte parent
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <h1>À propos de WaveTalk Édu</h1>
        <p>Plateforme éducative audio innovante...</p>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>