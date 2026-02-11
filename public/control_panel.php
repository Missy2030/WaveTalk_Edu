
<?php
// Session gérée par db_connection.php
require_once '../private/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// Récupérer les identités liées
$linked_accounts = [];

if ($user_role === 'student') {
    // Élève : lui-même + ses parents
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, 'student' as role, 'Vous' as relation
        FROM users u WHERE u.id = ?
        UNION
        SELECT u.id, u.first_name, u.last_name, u.email, 'parent' as role, pc.relationship as relation
        FROM parent_children pc
        JOIN users u ON pc.parent_id = u.id
        WHERE pc.student_id = ? AND pc.status = 'active'
    ");
    $stmt->execute([$user_id, $user_id]);
    $linked_accounts = $stmt->fetchAll();
    
} elseif ($user_role === 'parent') {
    // Parent : lui-même + ses enfants
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, 'parent' as role, 'Vous' as relation
        FROM users u WHERE u.id = ?
        UNION
        SELECT u.id, u.first_name, u.last_name, u.email, 'student' as role, pc.relationship as relation
        FROM parent_children pc
        JOIN users u ON pc.student_id = u.id
        WHERE pc.parent_id = ? AND pc.status = 'active'
    ");
    $stmt->execute([$user_id, $user_id]);
    $linked_accounts = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔄 Changer de Vue - WaveTalk Édu</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .control-panel {
            max-width: 600px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .account-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: 2px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
        }
        
        .account-card.current {
            border-color: #6366F1;
            background: rgba(99, 102, 241, 0.05);
        }
        
        .account-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .account-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .student-icon {
            background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%);
            color: white;
        }
        
        .parent-icon {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
        }
        
        .account-info {
            flex: 1;
        }
        
        .current-badge {
            background: #6366F1;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .relation-badge {
            background: #f3f4f6;
            color: #6b7280;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-podcast"></i>
                </div>
                <span>WaveTalk Édu</span>
            </a>
            
            <div class="nav-links">
                <a href="index.php" class="nav-link">🏠 Accueil</a>
                <a href="category.php" class="nav-link">📚 Cours</a>
                <?php if ($user_role === 'student'): ?>
                    <a href="student/dashboard.php" class="nav-link">📊 Dashboard</a>
                <?php else: ?>
                    <a href="parent/dashboard.php" class="nav-link">👨‍👦 Dashboard</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Panneau de Contrôle -->
    <main class="control-panel">
        <h1 style="margin-bottom: 10px; text-align: center;">
            <i class="fas fa-exchange-alt"></i> Changer de Vue
        </h1>
        <p style="color: #6b7280; margin-bottom: 30px; text-align: center;">
            Sélectionnez le compte avec lequel vous souhaitez naviguer
        </p>
        
        <div class="accounts-list">
            <?php foreach ($linked_accounts as $account): 
                $is_current = ($account['id'] == $user_id && (
                    ($user_role === 'student' && $account['role'] === 'student') ||
                    ($user_role === 'parent' && $account['role'] === 'parent')
                ));
                $icon_class = $account['role'] === 'student' ? 'student-icon' : 'parent-icon';
                $icon = $account['role'] === 'student' ? '👨‍🎓' : '👨‍👦';
            ?>
            <div class="account-card <?php echo $is_current ? 'current' : ''; ?>">
                <div class="account-icon <?php echo $icon_class; ?>">
                    <?php echo $icon; ?>
                </div>
                
                <div class="account-info">
                    <h3 style="margin: 0 0 5px; font-size: 1.1rem;">
                        <?php echo htmlspecialchars($account['first_name'] . ' ' . $account['last_name']); ?>
                    </h3>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <span class="badge <?php echo $account['role'] === 'student' ? 'badge-primary' : 'badge-success'; ?>">
                            <?php echo $account['role'] === 'student' ? 'Élève' : 'Parent'; ?>
                        </span>
                        <span class="relation-badge">
                            <?php echo htmlspecialchars($account['relation']); ?>
                        </span>
                    </div>
                </div>
                
                <div>
                    <?php if ($is_current): ?>
                        <span class="current-badge">
                            <i class="fas fa-check"></i> Actuel
                        </span>
                    <?php else: ?>
                        <a href="switch_account.php?switch_to=<?php echo $account['role']; ?>&user_id=<?php echo $account['id']; ?>" 
                           class="btn btn-sm btn-primary">
                            <i class="fas fa-play"></i> Utiliser
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Retour -->
        <div style="text-align: center; margin-top: 40px;">
            <a href="<?php echo $user_role === 'student' ? 'student/dashboard.php' : 'parent/dashboard.php'; ?>" 
               class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Retour au dashboard
            </a>
        </div>
    </main>
    
    <!-- Footer -->
    <footer style="background: var(--gray-900); color: white; padding: 40px 20px; text-align: center;">
        <p>WaveTalk Édu &copy; <?php echo date('Y'); ?> - Mastercard Foundation EdTech Fellowship</p>
    </footer>
    
    <script>
        console.log('🔄 Panneau de contrôle chargé');
    </script>
</body>
</html>
<?php
