<?php
// Inclure l'indicateur de switch
include '../includes/switch_indicator.php';
// Session gérée par db_connection.php
require_once '../../private/db_connection.php';

// Vérifier connexion
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] === 'parent') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer les statistiques de l'utilisateur
try {
    // Statistiques générales
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT up.chapter_id) as completed_chapters,
            COUNT(DISTINCT CASE WHEN up.completed = 1 THEN up.chapter_id END) as fully_completed,
            SUM(up.listened_time) as total_listening_time,
            AVG(up.quiz_score) as avg_quiz_score,
            COUNT(DISTINCT c.id) as active_courses,
            u.experience,
            u.level
        FROM users u
        LEFT JOIN user_progress up ON u.id = up.user_id
        LEFT JOIN chapters ch ON up.chapter_id = ch.id
        LEFT JOIN courses c ON ch.course_id = c.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();
    
    // Cours en progression
    $stmt = $pdo->prepare("
        SELECT c.*,
               COUNT(DISTINCT ch.id) as total_chapters,
               COUNT(DISTINCT CASE WHEN up.completed = 1 THEN up.chapter_id END) as completed_count,
               MAX(up.updated_at) as last_activity
        FROM courses c
        JOIN chapters ch ON c.id = ch.course_id
        LEFT JOIN user_progress up ON ch.id = up.chapter_id AND up.user_id = ?
        WHERE c.is_active = 1
        GROUP BY c.id
        HAVING completed_count > 0 OR last_activity IS NOT NULL
        ORDER BY last_activity DESC
        LIMIT 6
    ");
    $stmt->execute([$user_id]);
    $active_courses = $stmt->fetchAll();
    
    // Badges récents
    $stmt = $pdo->prepare("
        SELECT b.*, ub.unlocked_at
        FROM user_badges ub
        JOIN badges b ON ub.badge_id = b.id
        WHERE ub.user_id = ?
        ORDER BY ub.unlocked_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_badges = $stmt->fetchAll();
    
    // Activité récente
    $stmt = $pdo->prepare("
        SELECT up.*, 
               ch.title as chapter_title,
               c.title as course_title,
               c.subject,
               DATE_FORMAT(up.updated_at, '%d/%m/%Y %H:%i') as formatted_date
        FROM user_progress up
        JOIN chapters ch ON up.chapter_id = ch.id
        JOIN courses c ON ch.course_id = c.id
        WHERE up.user_id = ?
        ORDER BY up.updated_at DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $recent_activity = $stmt->fetchAll();
    
    // Dans dashboard.php, cherchez ces lignes (vers la ligne 40-45) :
$stmt = $pdo->prepare("
    SELECT 
        c.title as course_title,
        c.description,
        u.first_name,
        u.last_name,
        u.grade_level,
        COUNT(DISTINCT ch.id) as total_chapters,
        COUNT(DISTINCT CASE WHEN up.completed = 1 THEN up.chapter_id END) as completed_chapters
    FROM courses c
    JOIN chapters ch ON c.id = ch.course_id
    LEFT JOIN user_progress up ON ch.id = up.chapter_id AND up.user_id = ?
    JOIN users u ON u.id = ?
    WHERE c.id = ?
    GROUP BY c.id, u.id
");
    $stmt->execute([$user_id, $user_id]);
    $recommendations = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $stats = [];
    $active_courses = [];
    $recent_badges = [];
    $recent_activity = [];
    $recommendations = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Mon Tableau de Bord - WaveTalk Édu</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<!-- Ajoutez cette section après le header -->
<div class="welcome-animation" style="text-align: center; margin: 20px 0 40px;">
    <h2 id="typewriter-text" style="font-size: 2rem; color: #6366F1; min-height: 60px; display: flex; align-items: center; justify-content: center; font-family: 'Courier New', monospace;"></h2>
</div>

<!-- Inclure le script typewriter -->
<script src="../js/typewriter.js"></script>
<script>
    // Définir le rôle utilisateur pour l'animation
    document.body.setAttribute('data-user-role', 'student');
</script>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../index.php" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-podcast"></i>
                </div>
                <span>WaveTalk Édu</span>
            </a>


            
            <div class="nav-links">
                <a href="../index.php" class="nav-link">🏠 Accueil</a>
                <a href="../category.php" class="nav-link">📚 Cours</a>
                <a href="dashboard.php" class="nav-link active">📊 Dashboard</a>
                <a href="../badges.php" class="nav-link">🏆 Badges</a>
                  <?php include '../includes/account_switcher.php'; ?>
                <?php if ($_SESSION['user_role'] === 'parent'): ?>
                    <a href="../parent/dashboard.php" class="btn btn-outline">
                        <i class="fas fa-exchange-alt"></i> Mode Parent
                    </a>
                <?php endif; ?>
                
                 <!-- Ajoutez cette ligne dans la nav-links (vers la ligne 80) -->
<a href="../logout.php" class="btn btn-outline">
    <i class="fas fa-sign-out-alt"></i> Déconnexion
</a>
            </div>
        </div>
    </nav>
    <!-- Indicateur si on est en mode "switché" -->
<?php if (isset($_SESSION['is_switched']) && $_SESSION['is_switched']): ?>
<div class="switch-indicator" style="
    background: #F59E0B;
    color: white;
    padding: 10px 20px;
    text-align: center;
    border-radius: 0 0 10px 10px;
    margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(245, 158, 11, 0.2);
">
    <i class="fas fa-user-secret"></i>
    <strong>Mode "Voir comme" activé</strong> - 
    Vous visualisez l'interface de <?php echo htmlspecialchars($_SESSION['user_name']); ?>
    
    <a href="../switch_account.php?switch_back=1" 
       style="
            margin-left: 15px;
            background: white;
            color: #F59E0B;
            padding: 5px 15px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.2s;
       "
       onmouseover="this.style.background='#D97706'; this.style.color='white'"
       onmouseout="this.style.background='white'; this.style.color='#F59E0B'">
        <i class="fas fa-arrow-left"></i> Revenir à moi
    </a>
</div>
<?php endif; ?>
    <!-- Header Dashboard -->
    <header style="background: linear-gradient(135deg, #1f80c0ff 0%, #6e0588ff 100%); color: white; padding: 60px 20px 40px;">
        <div style="max-width: 1200px; margin: 0 auto; position: relative;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h1 style="background: none; -webkit-text-fill-color: white; color: white; margin-bottom: 10px;">
                        Bienvenue, <?php echo htmlspecialchars($_SESSION['user_name']); ?> !
                    </h1>
                    <p style="color: rgba(255,255,255,0.9); font-size: 1.1rem;">
                        Voici un aperçu de votre progression et vos activités récentes.
                    </p>
                </div>
                
                <div class="score-bubble" style="width: 100px; height: 100px; font-size: 2rem;">
                    Niv. <?php echo $stats['level'] ?? 1; ?>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Contenu principal -->
    <main style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
        
        <!-- Section Statistiques -->
        <section class="dashboard-section">
            <h2><i class="fas fa-chart-line"></i> Mes Statistiques</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="stat-card-mini">
                    <div class="stat-icon" style="background: var(--gradient-primary);">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['completed_chapters'] ?? 0; ?></div>
                        <div class="stat-label">Chapitres terminés</div>
                    </div>
                </div>
                
                <div class="stat-card-mini">
                    <div class="stat-icon" style="background: var(--gradient-accent);">
                        <i class="far fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo floor(($stats['total_listening_time'] ?? 0) / 3600); ?>h</div>
                        <div class="stat-label">Temps d'écoute</div>
                    </div>
                </div>
                
                <div class="stat-card-mini">
                    <div class="stat-icon" style="background: var(--gradient-warm);">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo round($stats['avg_quiz_score'] ?? 0, 1); ?>/10</div>
                        <div class="stat-label">Score moyen</div>
                    </div>
                </div>
                
                <div class="stat-card-mini">
                    <div class="stat-icon" style="background: var(--gradient-cool);">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['experience'] ?? 0; ?></div>
                        <div class="stat-label">Points d'expérience</div>
                    </div>
                </div>
            </div>
            
            <!-- Barre de progression niveau -->
            <div style="margin-top: 30px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span>Progression du niveau</span>
                    <span><?php echo (($stats['experience'] ?? 0) % 1000); ?>/1000 XP</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo (($stats['experience'] ?? 0) % 1000) / 10; ?>%;"></div>
                </div>
            </div>
        </section>
        
        <!-- Grille principale -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Cours en progression -->
            <div class="dashboard-section">
                <h2><i class="fas fa-play-circle"></i> Cours en progression</h2>
                
                <?php if (empty($active_courses)): ?>
                    <div class="empty-state" style="padding: 30px;">
                        <i class="fas fa-book-open" style="font-size: 2rem;"></i>
                        <p>Commencez votre premier cours !</p>
                        <a href="../category.php" class="btn btn-primary mt-3">Explorer les cours</a>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($active_courses as $course): 
                            $progress = $course['total_chapters'] > 0 ? round(($course['completed_count'] / $course['total_chapters']) * 100) : 0;
                        ?>
                        <div class="course-card-mini hover-lift">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h4 style="margin: 0;"><?php echo htmlspecialchars($course['title']); ?></h4>
                                <span class="badge badge-primary"><?php echo ucfirst($course['subject']); ?></span>
                            </div>
                            
                            <div class="progress-container">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                                </div>
                                <div style="text-align: center; margin-top: 5px; font-size: 0.9rem; color: var(--gray-500);">
                                    <?php echo $progress; ?>% • <?php echo $course['completed_count']; ?>/<?php echo $course['total_chapters']; ?> chapitres
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; margin-top: 15px;">
                                <a href="../course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-play"></i> Continuer
                                </a>
                                <span class="text-sm text-gray-500">
                                    <?php echo !empty($course['last_activity']) ? 'Dernière activité' : 'Pas encore commencé'; ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Badges récents -->
            <div class="dashboard-section">
                <h2><i class="fas fa-trophy"></i> Badges récents</h2>
                
                <?php if (empty($recent_badges)): ?>
                    <div class="empty-state" style="padding: 30px;">
                        <i class="fas fa-award" style="font-size: 2rem;"></i>
                        <p>Débloquez votre premier badge !</p>
                        <a href="../badges.php" class="btn btn-primary mt-3">Voir les badges</a>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recent_badges as $badge): ?>
                        <div class="badge-card-small hover-lift">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--gradient-warm); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: white;">
                                    <?php echo htmlspecialchars($badge['icon']); ?>
                                </div>
                                <div style="flex: 1;">
                                    <h4 style="margin: 0 0 5px;"><?php echo htmlspecialchars($badge['name']); ?></h4>
                                    <p style="margin: 0; font-size: 0.9rem; color: var(--gray-500);">
                                        <?php echo date('d/m/Y', strtotime($badge['unlocked_at'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="../badges.php" class="btn btn-outline">Voir tous les badges</a>
                </div>
            </div>
            
            <!-- Recommandations -->
            <div class="dashboard-section">
                <h2><i class="fas fa-lightbulb"></i> Recommandations</h2>
                
                <?php if (empty($recommendations)): ?>
                    <div class="empty-state" style="padding: 30px;">
                        <i class="fas fa-compass" style="font-size: 2rem;"></i>
                        <p>Completez des cours pour obtenir des recommandations</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recommendations as $course): ?>
                        <div class="recommendation-card-small hover-lift">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                <h4 style="margin: 0;"><?php echo htmlspecialchars($course['title']); ?></h4>
                                <span class="badge badge-success">Nouveau</span>
                            </div>
                            <p style="margin: 0 0 15px; font-size: 0.9rem; color: var(--gray-500); line-height: 1.5;">
                                <?php echo htmlspecialchars(substr($course['description'], 0, 80)); ?>...
                            </p>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span class="badge badge-outline"><?php echo $course['chapter_count']; ?> chapitres</span>
                                <a href="../course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-play"></i> Démarrer
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Activité récente -->
        <div class="dashboard-section mt-8">
            <h2><i class="fas fa-history"></i> Activité récente</h2>
            
            <?php if (empty($recent_activity)): ?>
                <div class="empty-state" style="padding: 30px;">
                    <i class="fas fa-history" style="font-size: 2rem;"></i>
                    <p>Aucune activité récente</p>
                </div>
            <?php else: ?>
                <div class="activity-timeline">
                    <?php foreach ($recent_activity as $activity): ?>
                    <div class="activity-item" style="display: flex; align-items: flex-start; gap: 15px; padding: 15px 0; border-bottom: 1px solid var(--gray-200);">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $activity['completed'] ? 'var(--success)' : 'var(--primary)'; ?>20; display: flex; align-items: center; justify-content: center; color: <?php echo $activity['completed'] ? 'var(--success)' : 'var(--primary)'; ?>; flex-shrink: 0;">
                            <i class="fas fa-<?php echo $activity['completed'] ? 'check' : 'headphones'; ?>"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <strong><?php echo htmlspecialchars($activity['course_title']); ?></strong>
                                    <span style="color: var(--gray-500);"> • <?php echo htmlspecialchars($activity['chapter_title']); ?></span>
                                </div>
                                <span style="font-size: 0.85rem; color: var(--gray-400);"><?php echo $activity['formatted_date']; ?></span>
                            </div>
                            <div style="margin-top: 5px;">
                                <?php if ($activity['completed']): ?>
                                    <span class="badge badge-success">Terminé</span>
                                <?php elseif ($activity['quiz_score']): ?>
                                    <span class="badge badge-warning">Quiz: <?php echo $activity['quiz_score']; ?>/10</span>
                                <?php else: ?>
                                    <span class="badge badge-primary">En progression</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
    </main>
    
    <!-- Footer -->
    <footer style="background:  linear-gradient(135deg, #1f80c0ff 0%, #6e0588ff 100%); color: white; padding: 40px 20px; text-align: center;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <p>WaveTalk Édu &copy; <?php echo date('Y'); ?> </p>
        </div>
    </footer>
    
    <script>
        // Initialisation du dashboard
        console.log('📊 WaveTalk Édu - Dashboard chargé');
        
        // Animation des cartes
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.hover-lift');
            cards.forEach(card => {
                card.style.transition = 'transform 0.3s, box-shadow 0.3s';
            });
        });
    </script>
</body>
</html>