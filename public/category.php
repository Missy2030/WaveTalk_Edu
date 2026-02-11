<?php

require_once '../private/db_connection.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_grade = null;

// Récupérer la classe de l'utilisateur si connecté
if ($is_logged_in && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student') {
    try {
        $stmt = $pdo->prepare("SELECT grade_level, grade FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        $user_grade = $user['grade'] ?? $user['grade_level'] ?? null;
    } catch (Exception $e) {
        error_log("Error fetching user grade: " . $e->getMessage());
    }
}

// RÉCUPÉRATION DES FILTRES
$subject_filter = isset($_GET['subject']) && $_GET['subject'] !== '' ? $_GET['subject'] : null;
$grade_filter = isset($_GET['grade']) && $_GET['grade'] !== '' ? $_GET['grade'] : null;
$search_query = isset($_GET['search']) && $_GET['search'] !== '' ? trim($_GET['search']) : null;

// Construction de la requête SQL
$sql = "
    SELECT 
        c.id, 
        c.title, 
        c.description, 
        c.subject, 
        c.grade_level,
        c.audio_url, 
        c.audio_file,
        c.duration, 
        c.is_downloadable, 
        c.download_count,
        COUNT(DISTINCT ch.id) as chapter_count
    FROM courses c
    LEFT JOIN chapters ch ON c.id = ch.course_id
    WHERE c.is_active = 1
";

$params = [];

// FILTRE PAR CLASSE
// Si un filtre de classe est spécifié, l'utiliser en priorité
if ($grade_filter) {
    $sql .= " AND (c.grade_level = ? OR c.grade_level = 'Tous niveaux' OR c.grade_level IS NULL OR c.grade_level = '')";
    $params[] = $grade_filter;
}
// Sinon, si l'utilisateur est un étudiant connecté, filtrer automatiquement par sa classe
elseif ($is_logged_in && $_SESSION['user_role'] === 'student' && $user_grade) {
    $sql .= " AND (c.grade_level = ? OR c.grade_level = 'Tous niveaux' OR c.grade_level IS NULL OR c.grade_level = '')";
    $params[] = $user_grade;
}

// FILTRE PAR MATIÈRE
if ($subject_filter) {
    $sql .= " AND c.subject = ?";
    $params[] = $subject_filter;
}

// FILTRE PAR RECHERCHE
if ($search_query) {
    $sql .= " AND (c.title LIKE ? OR c.description LIKE ? OR c.subject LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " GROUP BY c.id ORDER BY c.created_at DESC";

// Exécuter la requête
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $courses = $stmt->fetchAll();
} catch (Exception $e) {
    $courses = [];
    error_log("Error loading courses: " . $e->getMessage());
}

// Liste des matières disponibles avec icônes
$subjects = [
    'français' => ['icon' => '📝', 'name' => 'Français', 'color' => '#6366F1'],
    'maths' => ['icon' => '🔢', 'name' => 'Mathématiques', 'color' => '#10B981'],
    'english' => ['icon' => '🗣️', 'name' => 'Anglais', 'color' => '#F59E0B'],
    'svt' => ['icon' => '🧬', 'name' => 'SVT', 'color' => '#8B5CF6'],
    'physique' => ['icon' => '⚛️', 'name' => 'Physique-Chimie', 'color' => '#EC4899'],
    'philosophy' => ['icon' => '🤔', 'name' => 'Philosophie', 'color' => '#06B6D4'],
    'histoire' => ['icon' => '📚', 'name' => 'Histoire-Géo', 'color' => '#DC2626'],
    'spanish' => ['icon' => '🇪🇸', 'name' => 'Espagnol', 'color' => '#F97316'],
    'pct' => ['icon' => '💻', 'name' => 'Informatique', 'color' => '#64748B'],
    'other' => ['icon' => '✨', 'name' => 'Autres', 'color' => '#6B7280']
];

$grade_levels = ['6ème', '5ème', '4ème', '3ème', '2nde', '1ère', 'Terminale', 'Tous niveaux'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Cours <?php echo $user_grade ? "- $user_grade" : ''; ?> | WaveTalk Édu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        
        /* Header */
        .header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .title {
            font-size: 2.5rem;
            font-weight: 900;
            background: linear-gradient(135deg, #6366F1, #8B5CF6);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #6B7280;
            font-size: 1.1rem;
        }
        .grade-badge {
            display: inline-block;
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            margin-top: 15px;
            font-weight: 700;
        }
        
        /* Search & Filters */
        .search-filter-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .search-box {
            margin-bottom: 20px;
        }
        .search-box input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .filter-section {
            margin-bottom: 20px;
        }
        .filter-label {
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 12px;
            display: block;
            font-size: 0.95rem;
        }
        .filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #E5E7EB;
            border-radius: 50px;
            background: white;
            text-decoration: none;
            color: #374151;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .filter-btn:hover {
            border-color: #667eea;
            background: #F3F4F6;
            transform: translateY(-2px);
        }
        .filter-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: transparent;
        }
        .clear-filters {
            padding: 10px 20px;
            background: #EF4444;
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .clear-filters:hover {
            background: #DC2626;
            transform: translateY(-2px);
        }
        
        /* Results info */
        .results-info {
            background: #F9FAFB;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .results-count {
            font-weight: 700;
            color: #1F2937;
        }
        .active-filters {
            color: #6B7280;
            font-size: 0.9rem;
        }
        
        /* Courses Grid */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .course-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .course-header {
            padding: 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .course-title {
            font-size: 1.3rem;
            font-weight: 800;
            margin-bottom: 10px;
        }
        .course-meta {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            font-size: 0.9rem;
            opacity: 0.95;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .course-body {
            padding: 20px 25px;
        }
        .course-description {
            color: #6B7280;
            line-height: 1.6;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .course-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #E5E7EB;
        }
        .start-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Empty state */
        .empty-state {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .empty-state i {
            font-size: 5rem;
            color: #D1D5DB;
            margin-bottom: 20px;
        }
        .empty-state h3 {
            color: #1F2937;
            margin-bottom: 10px;
            font-size: 1.5rem;
        }
        .empty-state p {
            color: #6B7280;
        }
        
        /* Back button */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            margin-bottom: 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateX(-5px);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($is_logged_in): ?>
        <a href="student/dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Retour au tableau de bord
        </a>
        <?php endif; ?>
        
        <div class="header">
            <h1 class="title">
                <i class="fas fa-book-open"></i>
                Catalogue de cours
            </h1>
            <p class="subtitle">
                Découvrez tous nos cours audio pour progresser à votre rythme
            </p>
            <?php if ($user_grade): ?>
            <span class="grade-badge">
                <i class="fas fa-graduation-cap"></i>
                Votre classe : <?php echo htmlspecialchars($user_grade); ?>
            </span>
            <?php endif; ?>
        </div>
        
        <!-- Section recherche et filtres -->
        <div class="search-filter-section">
            <form method="GET" action="category.php" id="filterForm">
                <!-- Barre de recherche -->
                <div class="search-box">
                    <input type="text" 
                           name="search" 
                           placeholder="🔍 Rechercher un cours par titre, matière ou description..." 
                           value="<?php echo htmlspecialchars($search_query ?? ''); ?>">
                </div>
                
                <!-- Filtre par classe -->
                <div class="filter-section">
                    <label class="filter-label">
                        <i class="fas fa-graduation-cap"></i> Filtrer par classe
                    </label>
                    <div class="filters">
                        <?php foreach ($grade_levels as $grade): ?>
                        <a href="?<?php 
                            $params = $_GET;
                            $params['grade'] = $grade;
                            echo http_build_query($params);
                        ?>" 
                           class="filter-btn <?php echo $grade_filter === $grade ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($grade); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Filtre par matière -->
                <div class="filter-section">
                    <label class="filter-label">
                        <i class="fas fa-book"></i> Filtrer par matière
                    </label>
                    <div class="filters">
                        <?php foreach ($subjects as $key => $subject): ?>
                        <a href="?<?php 
                            $params = $_GET;
                            $params['subject'] = $key;
                            echo http_build_query($params);
                        ?>" 
                           class="filter-btn <?php echo $subject_filter === $key ? 'active' : ''; ?>">
                            <span><?php echo $subject['icon']; ?></span>
                            <?php echo htmlspecialchars($subject['name']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Bouton réinitialiser -->
                <?php if ($subject_filter || $grade_filter || $search_query): ?>
                <div style="margin-top: 15px;">
                    <a href="category.php" class="clear-filters">
                        <i class="fas fa-times-circle"></i>
                        Réinitialiser tous les filtres
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Informations sur les résultats -->
        <div class="results-info">
            <div class="results-count">
                <i class="fas fa-list"></i>
                <?php echo count($courses); ?> cours trouvé<?php echo count($courses) > 1 ? 's' : ''; ?>
            </div>
            <?php if ($subject_filter || $grade_filter || $search_query): ?>
            <div class="active-filters">
                Filtres actifs :
                <?php if ($grade_filter): ?>
                    <strong><?php echo htmlspecialchars($grade_filter); ?></strong>
                <?php endif; ?>
                <?php if ($subject_filter): ?>
                    <strong><?php echo htmlspecialchars($subjects[$subject_filter]['name'] ?? $subject_filter); ?></strong>
                <?php endif; ?>
                <?php if ($search_query): ?>
                    <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Grille de cours -->
        <?php if (!empty($courses)): ?>
        <div class="courses-grid">
            <?php foreach ($courses as $course): ?>
            <a href="course.php?id=<?php echo $course['id']; ?>" class="course-card">
                <div class="course-header">
                    <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                    <div class="course-meta">
                        <div class="meta-item">
                            <i class="fas fa-book-open"></i>
                            <?php echo htmlspecialchars($course['subject']); ?>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-graduation-cap"></i>
                            <?php echo htmlspecialchars($course['grade_level']); ?>
                        </div>
                        <?php if ($course['duration'] > 0): ?>
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <?php echo floor($course['duration'] / 60); ?> min
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="course-body">
                    <?php if (!empty($course['description'])): ?>
                    <p class="course-description">
                        <?php echo htmlspecialchars($course['description']); ?>
                    </p>
                    <?php endif; ?>
                    <div class="course-footer">
                        <div style="color: #6B7280; font-size: 0.9rem;">
                            <?php if ($course['download_count'] > 0): ?>
                            <i class="fas fa-download"></i>
                            <?php echo $course['download_count']; ?> téléchargement<?php echo $course['download_count'] > 1 ? 's' : ''; ?>
                            <?php endif; ?>
                        </div>
                        <span class="start-btn">
                            <i class="fas fa-play"></i>
                            Commencer
                        </span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <h3>Aucun cours trouvé</h3>
            <p>Essayez de modifier vos critères de recherche ou vos filtres.</p>
            <?php if ($subject_filter || $grade_filter || $search_query): ?>
            <a href="category.php" class="clear-filters" style="margin-top: 20px; display: inline-block;">
                <i class="fas fa-times-circle"></i>
                Voir tous les cours
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>