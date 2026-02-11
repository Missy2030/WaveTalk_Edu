
<?php
// Session gérée par db_connection.php
require_once '../private/db_connection.php';

// Vérifier connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Utilisateur';

// Récupérer les badges de l'utilisateur
try {
    // Tous les badges disponibles
    $stmt = $pdo->query("SELECT * FROM badges ORDER BY id");
    $all_badges = $stmt->fetchAll();
    
    // Badges débloqués par l'utilisateur
    $stmt = $pdo->prepare("
        SELECT b.*, ub.unlocked_at
        FROM user_badges ub
        JOIN badges b ON ub.badge_id = b.id
        WHERE ub.user_id = ?
        ORDER BY ub.unlocked_at DESC
    ");
    $stmt->execute([$user_id]);
    $unlocked_badges = $stmt->fetchAll();
    
    $unlocked_count = count($unlocked_badges);
    $total_badges = count($all_badges);
    
    // Statistiques
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT CASE WHEN b.category = 'progression' THEN b.id END) as progression_badges,
            COUNT(DISTINCT CASE WHEN b.category = 'quiz' THEN b.id END) as quiz_badges,
            COUNT(DISTINCT CASE WHEN b.category = 'engagement' THEN b.id END) as engagement_badges
        FROM badges b
        LEFT JOIN user_badges ub ON b.id = ub.badge_id AND ub.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();
    
} catch (Exception $e) {
    error_log("Badges error: " . $e->getMessage());
    $all_badges = [];
    $unlocked_badges = [];
    $unlocked_count = 0;
    $total_badges = 0;
    $stats = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏆 Mes Badges - WaveTalk Édu</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badges-header {
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            color: white;
            padding: 60px 20px 40px;
            border-radius: 0 0 20px 20px;
            margin-bottom: 40px;
        }
        
        .badges-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .badges-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
            margin: 40px 0;
        }
        
        .badge-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border: 1px solid #e5e7eb;
        }
        
        .badge-card.unlocked {
            border: 2px solid #F59E0B;
        }
        
        .badge-card.locked {
            opacity: 0.6;
            filter: grayscale(50%);
        }
        
        .badge-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 15px;
        }
        
        .badge-unlocked .badge-icon {
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            color: white;
        }
        
        .badge-locked .badge-icon {
            background: #e5e7eb;
            color: #9ca3af;
        }
        
        .badge-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-bottom: 10px;
        }
        
        .status-unlocked {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .status-locked {
            background: rgba(156, 163, 175, 0.1);
            color: #6b7280;
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
                <a href="student/dashboard.php" class="nav-link">📊 Dashboard</a>
                <a href="badges.php" class="nav-link active">🏆 Badges</a>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student'): ?>
                    <a href="student/certificates.php" class="nav-link">📜 Certificats</a>
                <?php endif; ?>
                <!-- Ajoutez dans nav-links -->
<a href="logout.php" class="btn btn-outline">
    <i class="fas fa-sign-out-alt"></i> Déconnexion
</a>
            </div>
        </div>
    </nav>
    
    <!-- Header Badges -->
    <header class="badges-header">
        <div style="max-width: 1200px; margin: 0 auto;">
            <h1 style="background: none; -webkit-text-fill-color: white; color: white; margin-bottom: 15px;">
                <i class="fas fa-trophy"></i> Mes Badges
            </h1>
            <p style="color: rgba(255,255,255,0.9); font-size: 1.1rem;">
                Collectionnez des badges en progressant dans votre apprentissage !
            </p>
            
            <div class="badges-stats">
                <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 15px; padding: 20px; text-align: center; border: 1px solid rgba(255,255,255,0.2);">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 5px;">
                        <?php echo $unlocked_count; ?>/<?php echo $total_badges; ?>
                    </div>
                    <div style="color: rgba(255,255,255,0.9);">Badges obtenus</div>
                </div>
                
                <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 15px; padding: 20px; text-align: center; border: 1px solid rgba(255,255,255,0.2);">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 5px;">
                        <?php echo $stats['progression_badges'] ?? 0; ?>
                    </div>
                    <div style="color: rgba(255,255,255,0.9);">Progression</div>
                </div>
                
                <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 15px; padding: 20px; text-align: center; border: 1px solid rgba(255,255,255,0.2);">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 5px;">
                        <?php echo $stats['quiz_badges'] ?? 0; ?>
                    </div>
                    <div style="color: rgba(255,255,255,0.9);">Quiz</div>
                </div>
                
                <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 15px; padding: 20px; text-align: center; border: 1px solid rgba(255,255,255,0.2);">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 5px;">
                        <?php echo $stats['engagement_badges'] ?? 0; ?>
                    </div>
                    <div style="color: rgba(255,255,255,0.9);">Engagement</div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Contenu principal -->
    <main style="max-width: 1200px; margin: 0 auto 60px; padding: 0 20px;">
        
        <!-- Tous les badges -->
        <h2 style="margin-bottom: 25px;">Collection complète</h2>
        
        <?php if (empty($all_badges)): ?>
            <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                <i class="fas fa-award" style="font-size: 4rem; color: #d1d5db; margin-bottom: 20px;"></i>
                <h3 style="color: #374151; margin-bottom: 15px;">Aucun badge disponible</h3>
                <p style="color: #6b7280;">Les badges seront ajoutés prochainement.</p>
            </div>
        <?php else: ?>
            <div class="badges-grid">
                <?php foreach ($all_badges as $badge): 
                    $is_unlocked = false;
                    $unlocked_at = null;
                    
                    foreach ($unlocked_badges as $unlocked) {
                        if ($unlocked['id'] == $badge['id']) {
                            $is_unlocked = true;
                            $unlocked_at = $unlocked['unlocked_at'];
                            break;
                        }
                    }
                ?>
                <div class="badge-card <?php echo $is_unlocked ? 'unlocked' : 'locked'; ?>">
                    <div class="badge-icon <?php echo $is_unlocked ? 'badge-unlocked' : 'badge-locked'; ?>">
                        <?php echo htmlspecialchars($badge['icon'] ?? '🏆'); ?>
                    </div>
                    
                    <span class="badge-status <?php echo $is_unlocked ? 'status-unlocked' : 'status-locked'; ?>">
                        <?php echo $is_unlocked ? '✓ Débloqué' : '🔒 Verrouillé'; ?>
                    </span>
                    
                    <h3 style="margin: 10px 0; color: #374151;"><?php echo htmlspecialchars($badge['name']); ?></h3>
                    
                    <p style="color: #6b7280; font-size: 0.95rem; margin-bottom: 15px; line-height: 1.5;">
                        <?php echo htmlspecialchars($badge['description']); ?>
                    </p>
                    
                    <?php if ($is_unlocked && $unlocked_at): ?>
                        <div style="font-size: 0.85rem; color: #9ca3af;">
                            <i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($unlocked_at)); ?>
                        </div>
                    <?php else: ?>
                        <div style="font-size: 0.85rem; color: #ef4444;">
                            <i class="fas fa-lock"></i> <?php echo htmlspecialchars($badge['requirement'] ?? 'Condition à remplir'); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Prochain badge à débloquer -->
        <?php if ($unlocked_count < $total_badges): ?>
        <div style="background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%); color: white; border-radius: 20px; padding: 30px; margin-top: 40px;">
            <h3 style="margin: 0 0 15px; color: white;">
                <i class="fas fa-flag"></i> Prochain objectif
            </h3>
            <p style="margin: 0 0 20px; opacity: 0.9;">
                Continue d'apprendre pour débloquer ton prochain badge !
            </p>
            <a href="category.php" class="btn" style="background: white; color: #6366F1;">
                <i class="fas fa-play"></i> Continuer à apprendre
            </a>
        </div>
        <?php endif; ?>
        
    </main>
    
    <!-- Footer -->
    <footer style="background: var(--gray-900); color: white; padding: 40px 20px; text-align: center;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <p>WaveTalk Édu &copy; <?php echo date('Y'); ?> </p>
        </div>
    </footer>
    
    <script>
        console.log('🏆 Page badges chargée');
    </script>
</body>
</html>
<?php
