<?php
// Session gérée par db_connection.php
require_once '../../private/db_connection.php';

// Vérifier connexion
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'parent') {
    header('Location: ../login.php');
    exit();
}

$parent_id = $_SESSION['user_id'];

// DEBUG : Afficher les infos session
echo "<!-- DEBUG: user_id=" . $_SESSION['user_id'] . ", user_name=" . $_SESSION['user_name'] . " -->";

// Récupérer les enfants
try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.grade_level
        FROM parent_children pc
        JOIN users u ON pc.student_id = u.id
        WHERE pc.parent_id = ? AND pc.status = 'active'
        ORDER BY u.first_name
    ");
    $stmt->execute([$parent_id]);
    $children = $stmt->fetchAll();
    
    $total_children = count($children);
    
} catch (Exception $e) {
    error_log("Parent dashboard error: " . $e->getMessage());
    $children = [];
    $total_children = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>👨‍👦 Dashboard Parent - WaveTalk Édu</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../index.php" class="logo">
                <i class="fas fa-podcast"></i>
                <span>WaveTalk Édu</span>
            </a>
            
            <div class="nav-links">
                <a href="../index.php">🏠 Accueil</a>
                <a href="../category.php">📚 Cours</a>
                <a href="dashboard.php" style="color: #4CAF50;">📊 Dashboard</a>
                
                <!-- Widget de bascule -->
                <?php include '../includes/account_switcher.php'; ?>
                
                <a href="../logout.php" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Header -->
    <header style="background: linear-gradient(135deg, #1f80c0ff 0%, #6e0588ff 100%); color: white; padding: 60px 20px 40px; text-align: center;">
        <h1 style="margin-bottom: 20px;">Bienvenue, <?php echo htmlspecialchars($_SESSION['user_name']); ?> !</h1>
        <p style="font-size: 1.2rem; opacity: 0.9;">Suivez la progression de vos enfants</p>
        
        <div style="display: inline-block; background: rgba(255,255,255,0.2); padding: 15px 30px; border-radius: 50px; margin-top: 20px;">
            <div style="font-size: 2.5rem; font-weight: bold;"><?php echo $total_children; ?></div>
            <div>enfant(s)</div>
        </div>
    </header>
    
    <!-- Contenu principal -->
    <main style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
        
        <?php if ($total_children === 0): ?>
        <!-- Aucun enfant -->
        <div style="text-align: center; padding: 60px 20px; background: #f8f9fa; border-radius: 20px; margin: 40px 0;">
            <i class="fas fa-user-graduate" style="font-size: 4rem; color: #d1d5db; margin-bottom: 20px;"></i>
            <h2 style="color: #374151; margin-bottom: 15px;">Aucun enfant associé</h2>
            <p style="color: #6b7280; max-width: 500px; margin: 0 auto 30px;">
                Vous n'avez pas encore d'enfant associé à votre compte.
            </p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <a href="add_child.php" class="btn btn-primary" style="padding: 15px 30px;">
                    <i class="fas fa-user-plus"></i> Ajouter un enfant
                </a>
                <a href="../register.php" class="btn btn-outline" style="padding: 15px 30px;">
                    <i class="fas fa-question-circle"></i> Guide d'inscription
                </a>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Liste des enfants -->
        <h2 style="margin-bottom: 30px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-child" style="color: #6366F1;"></i>
            Vos enfants (<?php echo $total_children; ?>)
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px;">
            <?php foreach ($children as $child): ?>
            <div style="background: white; border-radius: 15px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 600;">
                        <?php echo strtoupper(substr($child['first_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h3 style="margin: 0 0 5px; color: #374151;">
                            <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                        </h3>
                        <div style="color: #6b7280; font-size: 0.9rem;">
                            <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($child['grade_level']); ?>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <a href="view_student.php?student_id=<?php echo $child['id']; ?>"  
                       class="btn" style="background: #10B981; color: white; flex: 1;">
                        <i class="fas fa-eye"></i> Voir résultats
                    </a>
                    
                    <a href="../switch_account.php?switch_to=student&target_id=<?php echo $child['id']; ?>" 
                       class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Bouton ajouter -->
        <div style="text-align: center; margin-top: 40px;">
            <a href="add_child.php" class="btn btn-outline" style="padding: 12px 25px;">
                <i class="fas fa-plus"></i> Ajouter un autre enfant
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Debug info (optionnel) -->
        <?php if (isset($_GET['debug'])): ?>
        <div style="margin-top: 40px; padding: 20px; background: #f3f4f6; border-radius: 10px;">
            <h3>📊 Debug Info</h3>
            <pre>Parent ID: <?php echo $parent_id; ?>
Session: <?php print_r($_SESSION); ?>
Children: <?php echo json_encode($children, JSON_PRETTY_PRINT); ?></pre>
        </div>
        <?php endif; ?>
        
    </main>
    
    <!-- Footer -->
    <footer style="background: #333; color: white; padding: 40px 20px; text-align: center;">
        <p>WaveTalk Édu &copy; <?php echo date('Y'); ?></p>
    </footer>
    
    <script>
        console.log('Dashboard parent chargé');
    </script>
</body>
</html>