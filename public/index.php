<?php
// Le session_start est maintenant géré dans db_connection.php
require_once '../private/db_connection.php';
?>



<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WaveTalk Édu - Plateforme éducative audio</title>

    <!-- PWA Configuration -->
    <meta name="theme-color" content="#6366F1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="WaveTalk Édu">

    <!-- Manifest -->
    <link rel="manifest" href="manifest.json">

    <!-- Icônes -->
    <link rel="icon" href="assets/icons/icon-512x512.png" type="image/png">
    <link rel="apple-touch-icon" href="assets/icons/icon-192x192.png">

    <!-- Styles -->
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


    <!-- Service Worker Registration -->
    <script>

        // Désactiver sur ngrok
const isNgrok = window.location.hostname.includes('ngrok');
if ('serviceWorker' in navigator && !isNgrok) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js')
                    .then(reg => {
                        console.log('✅ Service Worker enregistré:', reg);

                        // Vérifier les mises à jour
                        reg.addEventListener('updatefound', () => {
                            const newWorker = reg.installing;
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    console.log('🔄 Nouvelle version disponible !');
                                    // Afficher une notification de mise à jour
                                    if (confirm('Une nouvelle version est disponible. Recharger ?')) {
                                        window.location.reload();
                                    }
                                }
                            });
                        });
                    })
                    .catch(err => console.log('❌ Erreur Service Worker:', err));
            });
        }

        // Détection de l'installation PWA
        window.addEventListener('appinstalled', () => {
            console.log('📱 WaveTalk Édu installé avec succès');
            // Rediriger vers le dashboard si connecté
            <?php if (isset($_SESSION['user_id'])): ?>
                window.location.href = 'student/dashboard.php';
            <?php endif; ?>
        });


        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/public/sw.js')
                    .then(reg => console.log('✅ Service Worker enregistré:', reg))
                    .catch(err => console.log('❌ Erreur Service Worker:', err));
            });
        }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WaveTalk Édu - Plateforme éducative audio</title>
    <link rel="stylesheet" href="css/style.css">



    <style>
        /* Styles temporaires pour l'accueil */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f7fa;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5em;
            font-weight: bold;
            color: #680980ff;
            text-decoration: none;
        }

        .logo i {
            font-size: 1.8em;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-links a {
            color: #555;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #4CAF50;
        }

        .btn {
            padding: 10px 25px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #4CAF50;
            color: white;
        }

        .btn-primary:hover {
            background: #3d8b40;
        }

        .btn-outline {
            border: 2px solid #4CAF50;
            color: #4CAF50;
            background: transparent;
        }

        .btn-outline:hover {
            background: #4CAF50;
            color: white;
        }

        .hero {
            background: linear-gradient(135deg, #1f80c0ff 0%, #6e0588ff 100%);
            color: white;
            padding: 80px 5%;
            text-align: center;
        }

        .hero h1 {
            font-size: 3em;
            margin-bottom: 20px;
            animation: fadeIn 1s ease;
        }

        .hero p {
            font-size: 1.2em;
            max-width: 700px;
            margin: 0 auto 30px;
            opacity: 0.9;
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .features {
            padding: 80px 5%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .features h2 {
            text-align: center;
            margin-bottom: 50px;
            color: #333;
            font-size: 2.5em;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: rgba(76, 175, 80, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: #4CAF50;
            font-size: 30px;
        }

        .feature-card h3 {
            margin: 15px 0;
            color: #333;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
        }

        .courses-preview {
            background: #f8f9fa;
            padding: 80px 5%;
        }

        .courses-preview h2 {
            text-align: center;
            margin-bottom: 50px;
            color: #333;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .course-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }

        feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.15);
        }

        .feature-card:active {
            transform: translateY(-2px);
        }

        .course-card:hover {
            transform: translateY(-5px);
        }

        .course-image {
            height: 150px;
            background: linear-gradient(45deg, #1f80c0ff 0%, #6e0588ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
        }

        .course-content {
            padding: 20px;
        }

        .course-content h3 {
            margin-bottom: 10px;
            color: #333;
        }

        .course-content p {
            color: #666;
            font-size: 0.95em;
            margin-bottom: 15px;
        }

        .course-meta {
            display: flex;
            justify-content: space-between;
            color: #888;
            font-size: 0.85em;
            margin-bottom: 15px;
        }

        .btn-course {
            display: inline-block;
            background: #4CAF50;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9em;
            transition: background 0.3s;
        }

        .btn-course:hover {
            background: #3d8b40;
        }

        footer {
            background: #333;
            color: white;
            padding: 40px 5%;
            text-align: center;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .hero h1 {
                font-size: 2em;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .hero-buttons .btn {
                width: 100%;
                max-width: 300px;
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar">
        <a href="index.php" class="logo">
            <i class="fas fa-podcast"></i>
            <span>WaveTalk Édu</span>
        </a>

        <div class="nav-links">
            <a href="index.php">Accueil</a>
            <a href="category.php">Cours</a>
            <a href="#features">Fonctionnalités</a>
            <a href="#about">À propos</a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="student/dashboard.php" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt"></i> Mon tableau de bord
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline">Connexion</a>
                <a href="register.php" class="btn btn-primary">S'inscrire gratuitement</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <h1>Apprendre avec l'audio</h1>
        <p>Découvrez une nouvelle façon d'apprendre grâce à des cours audio interactifs,
            des quiz et un suivi personnalisé de votre progression.</p>

        <div class="hero-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="student/dashboard.php" class="btn btn-primary" style="background: white; color: #4CAF50;">
                    <i class="fas fa-headphones"></i> Continuer mon apprentissage
                </a>
            <?php else: ?>
                <a href="register.php" class="btn btn-primary" style="background: white; color: #4CAF50;">
                    <i class="fas fa-user-plus"></i> Commencer gratuitement
                </a>
                <a href="category.php" class="btn btn-outline" style="border-color: white; color: white;">
                    <i class="fas fa-book-open"></i> Explorer les cours
                </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Features -->

    <!-- Features -->
    <section id="features" class="features">
        <h2>Pourquoi WaveTalk Édu ?</h2>

        <div class="features-grid">
            <!-- Cours audio interactifs -->
            <a href="<?php echo isset($_SESSION['user_id']) ? 'category.php' : 'login.php'; ?>"
                class="feature-card"
                style="text-decoration: none; color: inherit; transition: transform 0.3s;">
                <div class="feature-icon">
                    <i class="fas fa-headphones"></i>
                </div>
                <h3>Cours audio interactifs</h3>
                <p>Écoutez des cours conçus par des experts, avec des explications claires et des exercices pratiques.</p>
                <div style="margin-top: 15px; color: #6366F1; font-weight: 600;">
                    <i class="fas fa-arrow-right"></i> Accéder aux cours
                </div>
            </a>

            <!-- Suivi de progression -->
            <a href="<?php echo isset($_SESSION['user_id']) ? 'student/dashboard.php' : 'login.php'; ?>"
                class="feature-card"
                style="text-decoration: none; color: inherit; transition: transform 0.3s;">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Suivi de progression</h3>
                <p>Visualisez vos progrès avec des statistiques détaillées et des recommandations personnalisées.</p>
                <div style="margin-top: 15px; color: #6366F1; font-weight: 600;">
                    <i class="fas fa-arrow-right"></i> Voir mon dashboard
                </div>
            </a>

            <!-- Quiz et évaluations -->
            <a href="<?php echo isset($_SESSION['user_id']) ? 'category.php' : 'login.php'; ?>"
                class="feature-card"
                style="text-decoration: none; color: inherit; transition: transform 0.3s;">
                <div class="feature-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h3>Quiz et évaluations</h3>
                <p>Testez vos connaissances avec des quiz interactifs à la fin de chaque chapitre.</p>
                <div style="margin-top: 15px; color: #6366F1; font-weight: 600;">
                    <i class="fas fa-arrow-right"></i> Commencer un quiz
                </div>
            </a>
        </div>
    </section>

    <!-- Courses Preview -->
    <section class="courses-preview">
        <h2>Cours populaires</h2>

        <div class="courses-grid">
            <?php
            try {
                $stmt = $pdo->query("SELECT * FROM courses WHERE is_active = 1 LIMIT 3");
                $courses = $stmt->fetchAll();

                if (empty($courses)) {
                    echo '<p style="text-align:center;grid-column:1/-1;">Aucun cours disponible pour le moment.</p>';
                } else {
                    foreach ($courses as $course):
            ?>
                        <div class="course-card">
                            <div class="course-image">
                                <i class="fas fa-<?php echo $course['subject'] === 'maths' ? 'calculator' : 'language'; ?>"></i>
                            </div>
                            <div class="course-content">
                                <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                <p><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></p>

                                <div class="course-meta">
                                    <span><i class="fas fa-book"></i> <?php echo htmlspecialchars($course['subject']); ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo floor(($course['duration'] ?? 0) / 60); ?> min</span>
                                </div>

                                <a href="course.php?id=<?php echo $course['id']; ?>" class="btn-course">
                                    Découvrir le cours
                                </a>
                            </div>
                        </div>
            <?php
                    endforeach;
                }
            } catch (Exception $e) {
                echo '<p style="text-align:center;grid-column:1/-1;">Chargement des cours...</p>';
            }
            ?>
        </div>

        <div style="text-align: center; margin-top: 40px;">
            <a href="category.php" class="btn btn-primary">
                <i class="fas fa-book-open"></i> Voir tous les cours
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>WaveTalk Édu &copy; <?php echo date('Y'); ?> - Plateforme éducative audio</p>

    </footer>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        // Simple script pour le menu responsive
        document.addEventListener('DOMContentLoaded', function() {
            console.log('WaveTalk Édu - Page d\'accueil chargée');
        });
    </script>






</body>

</html>