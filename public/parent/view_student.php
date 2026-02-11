<?php
// Session gérée par db_connection.php
require_once '../../private/db_connection.php';

// Vérifier que c'est bien un parent connecté
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'parent') {
    header('Location: ../login.php');
    exit();
}

$parent_id = $_SESSION['user_id'];
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

// Vérifier que ce parent a bien le droit de voir cet élève
try {
    $stmt = $pdo->prepare("
        SELECT pc.*, u.first_name, u.last_name, u.grade_level, u.email
        FROM parent_children pc
        JOIN users u ON pc.student_id = u.id
        WHERE pc.parent_id = ? AND pc.student_id = ? AND pc.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$parent_id, $student_id]);
    $authorization = $stmt->fetch();
    
    if (!$authorization) {
        $_SESSION['error'] = "Vous n'êtes pas autorisé à voir les résultats de cet élève.";
        header('Location: /dashboard.php');
        exit();
    }
    
    $student_name = $authorization['first_name'] . ' ' . $authorization['last_name'];
    $student_grade = $authorization['grade_level'];
    
    // Récupérer la progression de l'élève
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.title as course_title,
            c.subject,
            c.description,
            COUNT(DISTINCT ch.id) as total_chapters,
            COUNT(DISTINCT CASE WHEN up.completed = 1 THEN up.chapter_id END) as completed_chapters,
            AVG(up.quiz_score) as avg_quiz_score,
            MAX(up.updated_at) as last_activity,
            SUM(ch.duration) as total_duration,
            SUM(up.listened_time) as total_listened
        FROM courses c
        LEFT JOIN chapters ch ON c.id = ch.course_id
        LEFT JOIN user_progress up ON ch.id = up.chapter_id AND up.user_id = ?
        WHERE c.is_active = 1
        GROUP BY c.id
        HAVING total_chapters > 0
        ORDER BY last_activity DESC
    ");
    $stmt->execute([$student_id]);
    $student_progress = $stmt->fetchAll();
    
    // Statistiques globales
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT up.chapter_id) as total_chapters_attempted,
            COUNT(DISTINCT CASE WHEN up.completed = 1 THEN up.chapter_id END) as chapters_completed,
            SUM(up.listened_time) as total_listening_time,
            AVG(up.quiz_score) as avg_quiz_score,
            COUNT(DISTINCT up.course_id) as courses_started
        FROM user_progress up
        WHERE up.user_id = ?
    ");
    $stmt->execute([$student_id]);
    $global_stats = $stmt->fetch();
    
    // Badges obtenus
    $stmt = $pdo->prepare("
        SELECT b.*, ub.unlocked_at
        FROM user_badges ub
        JOIN badges b ON ub.badge_id = b.id
        WHERE ub.user_id = ?
        ORDER BY ub.unlocked_at DESC
        LIMIT 5
    ");
    $stmt->execute([$student_id]);
    $student_badges = $stmt->fetchAll();
    
    // Activité récente
    $stmt = $pdo->prepare("
        SELECT 
            up.*,
            ch.title as chapter_title,
            c.title as course_title,
            DATE_FORMAT(up.updated_at, '%d/%m/%Y %H:%i') as formatted_date
        FROM user_progress up
        JOIN chapters ch ON up.chapter_id = ch.id
        JOIN courses c ON ch.course_id = c.id
        WHERE up.user_id = ?
        ORDER BY up.updated_at DESC
        LIMIT 10
    ");
    $stmt->execute([$student_id]);
    $recent_activity = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("View student error: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors du chargement des données.";
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Résultats de <?php echo htmlspecialchars($student_name); ?> - WaveTalk Édu</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .student-header {
            background: linear-gradient(135deg, #8B5CF6 0%, #6366F1 100%);
            color: white;
            padding: 50px 20px 30px;
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .course-progress-item {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }
        
        .progress-container {
            margin: 15px 0;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: white;
            text-decoration: none;
            margin-bottom: 20px;
            opacity: 0.9;
        }
        
        .back-button:hover {
            opacity: 1;
        }
        
        .view-mode-badge {
            background: #F59E0B;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-block;
            margin-left: 15px;
        }
    </style>
</head>
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
                <a href="dashboard.php" class="nav-link">📊 Dashboard</a>
                <a href="view_student.php?student_id=<?php echo $student_id; ?>" class="nav-link active">
                    👁️ Vue élève
                </a>
                
                <!-- Indicateur de mode "vue élève" -->
                <div class="view-mode-badge">
                    <i class="fas fa-eye"></i> Vue des résultats
                </div>
                
                <!-- Bouton pour revenir au dashboard parent -->
                <a href="dashboard.php" class="btn btn-outline" style="background: white; color: #6366F1;">
                    <i class="fas fa-arrow-left"></i> Retour au parent
                </a>
            </div>
        </div>
    </nav>
    
    <!-- En-tête élève -->
    <header class="student-header">
        <div style="max-width: 1200px; margin: 0 auto;">
            <a href="dashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Retour à mes enfants
            </a>
            
            <h1 style="background: none; -webkit-text-fill-color: white; color: white; margin-bottom: 10px;">
                <i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($student_name); ?>
            </h1>
            <p style="color: rgba(255,255,255,0.9);">
                Niveau scolaire : <?php echo htmlspecialchars($student_grade); ?> 
                • Élève de <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">
                        <?php echo $global_stats['chapters_completed'] ?? 0; ?>
                    </div>
                    <div>Chapitres terminés</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value">
                        <?php echo floor(($global_stats['total_listening_time'] ?? 0) / 3600); ?>h
                    </div>
                    <div>Temps d'écoute</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value">
                        <?php echo round($global_stats['avg_quiz_score'] ?? 0, 1); ?>/10
                    </div>
                    <div>Score moyen quiz</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value">
                        <?php echo $global_stats['courses_started'] ?? 0; ?>
                    </div>
                    <div>Cours démarrés</div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Contenu principal -->
    <main style="max-width: 1200px; margin: 0 auto 60px; padding: 0 20px;">
        
        <!-- Cours en progression -->
        <section style="margin-bottom: 40px;">
            <h2 style="margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-chart-line" style="color: #6366F1;"></i>
                Progression dans les cours
            </h2>
            
            <?php if (empty($student_progress)): ?>
                <div class="empty-state" style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 15px;">
                    <i class="fas fa-book-open" style="font-size: 3rem; color: #d1d5db; margin-bottom: 20px;"></i>
                    <h3 style="color: #374151; margin-bottom: 10px;">Aucun cours commencé</h3>
                    <p style="color: #6b7280;">Cet élève n'a pas encore commencé de cours.</p>
                </div>
            <?php else: ?>
                <div class="courses-progress">
                    <?php foreach ($student_progress as $course): 
                        $progress = $course['total_chapters'] > 0 
                            ? round(($course['completed_chapters'] / $course['total_chapters']) * 100) 
                            : 0;
                        $listening_progress = $course['total_duration'] > 0 
                            ? round(($course['total_listened'] / $course['total_duration']) * 100)
                            : 0;
                    ?>
                    <div class="course-progress-item">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                            <div>
                                <h3 style="margin: 0 0 5px;"><?php echo htmlspecialchars($course['course_title']); ?></h3>
                                <p style="color: #6b7280; margin: 0; font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($course['subject']); ?> • 
                                    <?php echo $course['completed_chapters']; ?>/<?php echo $course['total_chapters']; ?> chapitres
                                </p>
                            </div>
                            <div style="text-align: right;">
                                <span class="badge badge-primary"><?php echo $progress; ?>%</span>
                                <?php if ($course['avg_quiz_score']): ?>
                                    <span class="badge badge-warning" style="margin-left: 5px;">
                                        Quiz: <?php echo round($course['avg_quiz_score'], 1); ?>/10
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="progress-container">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 0.9rem;">
                                <span>Progression des chapitres</span>
                                <span><?php echo $progress; ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                            </div>
                        </div>
                        
                        <div class="progress-container">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 0.9rem;">
                                <span>Temps écouté</span>
                                <span><?php echo floor(($course['total_listened'] ?? 0) / 60); ?> min / <?php echo floor(($course['total_duration'] ?? 0) / 60); ?> min</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $listening_progress; ?>%; background: var(--gradient-accent);"></div>
                            </div>
                        </div>
                        
                        <?php if ($course['last_activity']): ?>
                        <div style="margin-top: 15px; font-size: 0.85rem; color: #9ca3af;">
                            <i class="far fa-clock"></i> Dernière activité : 
                            <?php echo date('d/m/Y H:i', strtotime($course['last_activity'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Badges obtenus -->
            <section>
                <h2 style="margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-trophy" style="color: #F59E0B;"></i>
                    Badges obtenus
                </h2>
                
                <?php if (empty($student_badges)): ?>
                    <div class="empty-state" style="text-align: center; padding: 30px; background: #f8f9fa; border-radius: 15px;">
                        <i class="fas fa-award" style="font-size: 2rem; color: #d1d5db; margin-bottom: 15px;"></i>
                        <p style="color: #6b7280;">Aucun badge obtenu pour le moment.</p>
                    </div>
                <?php else: ?>
                    <div class="badges-list">
                        <?php foreach ($student_badges as $badge): ?>
                        <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: white; border-radius: 10px; margin-bottom: 10px; border: 1px solid #e5e7eb;">
                            <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: white;">
                                <?php echo htmlspecialchars($badge['icon']); ?>
                            </div>
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 5px;"><?php echo htmlspecialchars($badge['name']); ?></h4>
                                <p style="margin: 0; font-size: 0.9rem; color: #6b7280;">
                                    <?php echo htmlspecialchars($badge['description']); ?>
                                </p>
                            </div>
                            <div style="font-size: 0.85rem; color: #9ca3af;">
                                <?php echo date('d/m/Y', strtotime($badge['unlocked_at'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="../badges.php?user_id=<?php echo $student_id; ?>" class="btn btn-outline">
                            Voir tous les badges
                        </a>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- Activité récente -->
            <section>
                <h2 style="margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-history" style="color: #10B981;"></i>
                    Activité récente
                </h2>
                
                <?php if (empty($recent_activity)): ?>
                    <div class="empty-state" style="text-align: center; padding: 30px; background: #f8f9fa; border-radius: 15px;">
                        <i class="fas fa-history" style="font-size: 2rem; color: #d1d5db; margin-bottom: 15px;"></i>
                        <p style="color: #6b7280;">Aucune activité récente.</p>
                    </div>
                <?php else: ?>
                    <div class="activity-timeline">
                        <?php foreach ($recent_activity as $activity): ?>
                        <div style="display: flex; align-items: flex-start; gap: 15px; padding: 15px 0; border-bottom: 1px solid #e5e7eb;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $activity['completed'] ? '#10B98120' : '#6366F120'; ?>; display: flex; align-items: center; justify-content: center; color: <?php echo $activity['completed'] ? '#10B981' : '#6366F1'; ?>; flex-shrink: 0;">
                                <i class="fas fa-<?php echo $activity['completed'] ? 'check' : 'headphones'; ?>"></i>
                            </div>
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($activity['course_title']); ?></strong>
                                        <span style="color: #6b7280;"> • <?php echo htmlspecialchars($activity['chapter_title']); ?></span>
                                    </div>
                                    <span style="font-size: 0.85rem; color: #9ca3af;"><?php echo $activity['formatted_date']; ?></span>
                                </div>
                                <div style="margin-top: 5px;">
                                    <?php if ($activity['completed']): ?>
                                        <span class="badge badge-success" style="font-size: 0.8rem;">Terminé</span>
                                    <?php elseif ($activity['quiz_score']): ?>
                                        <span class="badge badge-warning" style="font-size: 0.8rem;">
                                            Quiz: <?php echo $activity['quiz_score']; ?>/10
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-primary" style="font-size: 0.8rem;">
                                            En progression
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        
        <!-- Actions -->
        <div style="margin-top: 40px; padding: 30px; background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%); color: white; border-radius: 15px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="margin: 0 0 10px; color: white;">Actions disponibles</h3>
                    <p style="margin: 0; opacity: 0.9;">
                        Vous pouvez continuer à suivre la progression ou basculer vers cet élève.
                    </p>
                </div>
                <div style="display: flex; gap: 15px;">
                    <a href="../switch_account.php?switch_to=student&target_id=<?php echo $student_id; ?>" 
                       class="btn" style="background: white; color: #6366F1;">
                        <i class="fas fa-sign-in-alt"></i> Se connecter comme cet élève
                    </a>
                    <a href="dashboard.php" class="btn btn-outline" style="border-color: white; color: white;">
                        <i class="fas fa-arrow-left"></i> Retour à mes enfants
                    </a>
                </div>
            </div>
        </div>
        
    </main>
    
    <!-- Footer -->
    <footer style="background: var(--gray-900); color: white; padding: 40px 20px; text-align: center;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <p>WaveTalk Édu &copy; <?php echo date('Y'); ?> </p>
        </div>
    </footer>
    
    <script>
        console.log('👁️ Vue parent - Résultats de l\'élève chargés');
    </script>
</body>
</html>
