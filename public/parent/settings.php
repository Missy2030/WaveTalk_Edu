<?php
// Session gérée par db_connection.php
require_once '../../private/db_connection.php';

// Vérifier si c'est un parent
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'parent') {
    header('Location: ../login.php');
    exit();
}

$parent_id = $_SESSION['user_id'];
$child_id = isset($_GET['child_id']) ? intval($_GET['child_id']) : 0;

// Récupérer les infos de l'enfant
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND parent_id = ?");
    $stmt->execute([$child_id, $parent_id]);
    $child = $stmt->fetch();
    
    if (!$child) {
        header('Location: dashboard.php');
        exit();
    }
    
    // Récupérer les paramètres parentaux
    $stmt = $pdo->prepare("SELECT * FROM parental_controls WHERE child_id = ?");
    $stmt->execute([$child_id]);
    $controls = $stmt->fetch();
    
    if (!$controls) {
        // Créer des paramètres par défaut
        $controls = [
            'daily_time_limit' => 7200, // 2 heures en secondes
            'weekday_limit' => 3600, // 1 heure en semaine
            'weekend_limit' => 10800, // 3 heures le week-end
            'content_filter' => 'all',
            'notifications' => 1,
            'is_active' => 1
        ];
    }
    
} catch (Exception $e) {
    error_log("Parent settings error: " . $e->getMessage());
    header('Location: dashboard.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $daily_time_limit = intval($_POST['daily_time_limit']) * 60; // Convertir en secondes
    $weekday_limit = intval($_POST['weekday_limit']) * 60;
    $weekend_limit = intval($_POST['weekend_limit']) * 60;
    $content_filter = $_POST['content_filter'];
    $notifications = isset($_POST['notifications']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        // Vérifier si des contrôles existent déjà
        $stmt = $pdo->prepare("SELECT id FROM parental_controls WHERE child_id = ?");
        $stmt->execute([$child_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Mettre à jour
            $stmt = $pdo->prepare("
                UPDATE parental_controls 
                SET daily_time_limit = ?, 
                    weekday_limit = ?,
                    weekend_limit = ?,
                    content_filter = ?,
                    notifications = ?,
                    is_active = ?,
                    updated_at = NOW()
                WHERE child_id = ?
            ");
            $stmt->execute([
                $daily_time_limit,
                $weekday_limit,
                $weekend_limit,
                $content_filter,
                $notifications,
                $is_active,
                $child_id
            ]);
        } else {
            // Insérer
            $stmt = $pdo->prepare("
                INSERT INTO parental_controls 
                (child_id, daily_time_limit, weekday_limit, weekend_limit, content_filter, notifications, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $child_id,
                $daily_time_limit,
                $weekday_limit,
                $weekend_limit,
                $content_filter,
                $notifications,
                $is_active
            ]);
        }
        
        $success = "Paramètres enregistrés avec succès !";
        
    } catch (Exception $e) {
        $error = "Erreur lors de l'enregistrement des paramètres.";
        error_log("Save parental controls error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚙️ Paramètres Parentaux - WaveTalk Édu</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-header {
            background: linear-gradient(135deg, #8B5CF6 0%, #6366F1 100%);
            color: white;
            padding: 40px 20px;
            position: relative;
            overflow: hidden;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            margin-bottom: 20px;
        }
        
        .settings-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .child-info {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
        }
        
        .child-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366F1 0%, #EC4899 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            flex-shrink: 0;
        }
        
        .settings-form {
            background: white;
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: var(--shadow);
        }
        
        .form-section {
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
            color: var(--gray-800);
        }
        
        .time-control {
            margin-bottom: 25px;
        }
        
        .time-slider-container {
            margin: 20px 0;
        }
        
        .slider-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            font-size: 0.85rem;
            color: var(--gray-500);
        }
        
        .time-value {
            text-align: center;
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary);
            margin: 10px 0;
        }
        
        .radio-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .radio-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            border: 2px solid var(--gray-300);
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .radio-label:hover {
            border-color: var(--primary);
        }
        
        .radio-label.selected {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
            margin-left: 10px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--gray-300);
            transition: .4s;
            border-radius: 34px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: var(--primary);
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(30px);
        }
        
        .toggle-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: var(--gray-50);
            border-radius: var(--radius);
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .radio-group {
                grid-template-columns: 1fr;
            }
            
            .child-info {
                flex-direction: column;
                text-align: center;
            }
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
                <a href="dashboard.php" class="nav-link">👨‍👩‍👧‍👦 Retour au tableau de bord</a>
            </div>
        </div>
    </nav>
    
    <!-- Header -->
    <header class="settings-header">
        <div class="header-content">
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Retour au tableau de bord
            </a>
            <h1 style="background: none; -webkit-text-fill-color: white; color: white;">
                Paramètres parentaux
            </h1>
            <p style="color: rgba(255,255,255,0.9);">
                Gérez les restrictions et préférences d'apprentissage
            </p>
        </div>
    </header>
    
    <!-- Contenu principal -->
    <main class="settings-container">
        <!-- Info enfant -->
        <div class="child-info">
            <div class="child-avatar">
                <?php echo strtoupper(substr($child['first_name'], 0, 1)); ?>
            </div>
            <div>
                <h2 style="margin-bottom: 5px; color: var(--gray-800);">
                    <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                </h2>
                <p style="color: var(--gray-500);">
                    <?php echo htmlspecialchars($child['grade_level']); ?> • 
                    <span class="badge badge-primary">Niv. <?php echo $child['level'] ?? 1; ?></span>
                </p>
            </div>
        </div>
        
        <!-- Formulaire -->
        <div class="settings-form">
            <?php if (isset($success)): ?>
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); color: var(--success); padding: 12px; border-radius: var(--radius); margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 12px; border-radius: var(--radius); margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <!-- Limites de temps -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-clock"></i>
                        Limites de temps
                    </h3>
                    
                    <div class="time-control">
                        <label class="form-label">Limite quotidienne maximale</label>
                        <div class="time-slider-container">
                            <input type="range" min="30" max="240" value="<?php echo floor($controls['daily_time_limit'] / 60); ?>" 
                                   class="form-control" id="dailyLimitSlider" step="30" name="daily_time_limit">
                            <div class="time-value" id="dailyLimitValue">
                                <?php echo floor($controls['daily_time_limit'] / 60); ?> minutes
                            </div>
                            <div class="slider-labels">
                                <span>30 min</span>
                                <span>4 heures</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2" style="gap: 20px;">
                        <div class="time-control">
                            <label class="form-label">Limite en semaine</label>
                            <div class="time-slider-container">
                                <input type="range" min="30" max="180" value="<?php echo floor($controls['weekday_limit'] / 60); ?>" 
                                       class="form-control" id="weekdayLimitSlider" step="30" name="weekday_limit">
                                <div class="time-value" id="weekdayLimitValue">
                                    <?php echo floor($controls['weekday_limit'] / 60); ?> minutes
                                </div>
                            </div>
                        </div>
                        
                        <div class="time-control">
                            <label class="form-label">Limite le week-end</label>
                            <div class="time-slider-container">
                                <input type="range" min="60" max="300" value="<?php echo floor($controls['weekend_limit'] / 60); ?>" 
                                       class="form-control" id="weekendLimitSlider" step="30" name="weekend_limit">
                                <div class="time-value" id="weekendLimitValue">
                                    <?php echo floor($controls['weekend_limit'] / 60); ?> minutes
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filtre de contenu -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-filter"></i>
                        Filtre de contenu
                    </h3>
                    
                    <div class="radio-group">
                        <label class="radio-label <?php echo $controls['content_filter'] === 'all' ? 'selected' : ''; ?>">
                            <input type="radio" name="content_filter" value="all" <?php echo $controls['content_filter'] === 'all' ? 'checked' : ''; ?> style="display: none;">
                            <i class="fas fa-globe" style="color: var(--primary);"></i>
                            <div>
                                <div style="font-weight: 600;">Tous les cours</div>
                                <div style="font-size: 0.85rem; color: var(--gray-500);">Accès complet</div>
                            </div>
                        </label>
                        
                        <label class="radio-label <?php echo $controls['content_filter'] === 'age_appropriate' ? 'selected' : ''; ?>">
                            <input type="radio" name="content_filter" value="age_appropriate" <?php echo $controls['content_filter'] === 'age_appropriate' ? 'checked' : ''; ?> style="display: none;">
                            <i class="fas fa-user-check" style="color: var(--success);"></i>
                            <div>
                                <div style="font-weight: 600;">Adapté à l'âge</div>
                                <div style="font-size: 0.85rem; color: var(--gray-500);">Filtre automatique</div>
                            </div>
                        </label>
                        
                        <label class="radio-label <?php echo $controls['content_filter'] === 'selected' ? 'selected' : ''; ?>">
                            <input type="radio" name="content_filter" value="selected" <?php echo $controls['content_filter'] === 'selected' ? 'checked' : ''; ?> style="display: none;">
                            <i class="fas fa-list-check" style="color: var(--warning);"></i>
                            <div>
                                <div style="font-weight: 600;">Sélection manuelle</div>
                                <div style="font-size: 0.85rem; color: var(--gray-500);">Cours approuvés</div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Notifications -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-bell"></i>
                        Notifications
                    </h3>
                    
                    <div class="toggle-container">
                        <div>
                            <div style="font-weight: 600;">Notifications de progression</div>
                            <div style="font-size: 0.85rem; color: var(--gray-500);">
                                Recevoir des alertes sur les progrès de votre enfant
                            </div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="notifications" <?php echo $controls['notifications'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="toggle-container">
                        <div>
                            <div style="font-weight: 600;">Activer les contrôles</div>
                            <div style="font-size: 0.85rem; color: var(--gray-500);">
                                Appliquer les restrictions définies
                            </div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_active" <?php echo $controls['is_active'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                
                <!-- Boutons d'action -->
                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-save"></i> Enregistrer les paramètres
                    </button>
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </main>
    
    <!-- Footer -->
    <footer style="background: var(--gray-900); color: white; padding: 40px 20px; text-align: center;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <p>WaveTalk Édu &copy; <?php echo date('Y'); ?> - Mastercard Foundation EdTech Fellowship</p>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script>
        // Gestion des sliders
        const dailySlider = document.getElementById('dailyLimitSlider');
        const dailyValue = document.getElementById('dailyLimitValue');
        const weekdaySlider = document.getElementById('weekdayLimitSlider');
        const weekdayValue = document.getElementById('weekdayLimitValue');
        const weekendSlider = document.getElementById('weekendLimitSlider');
        const weekendValue = document.getElementById('weekendLimitValue');
        
        // Format minutes en heures/minutes
        function formatTime(minutes) {
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            
            if (hours > 0) {
                return `${hours}h${mins > 0 ? ` ${mins}min` : ''}`;
            }
            return `${minutes}min`;
        }
        
        // Mettre à jour les valeurs des sliders
        function updateSlider(slider, valueElement) {
            const minutes = parseInt(slider.value);
            valueElement.textContent = formatTime(minutes);
        }
        
        // Événements des sliders
        dailySlider.addEventListener('input', () => updateSlider(dailySlider, dailyValue));
        weekdaySlider.addEventListener('input', () => updateSlider(weekdaySlider, weekdayValue));
        weekendSlider.addEventListener('input', () => updateSlider(weekendSlider, weekendValue));
        
        // Initialiser les valeurs
        updateSlider(dailySlider, dailyValue);
        updateSlider(weekdaySlider, weekdayValue);
        updateSlider(weekendSlider, weekendValue);
        
        // Gestion des boutons radio
        document.querySelectorAll('.radio-label').forEach(label => {
            label.addEventListener('click', function() {
                // Désélectionner tous les boutons
                document.querySelectorAll('.radio-label').forEach(l => {
                    l.classList.remove('selected');
                });
                
                // Sélectionner celui cliqué
                this.classList.add('selected');
                
                // Cocher le radio bouton correspondant
                const radioInput = this.querySelector('input[type="radio"]');
                if (radioInput) {
                    radioInput.checked = true;
                }
            });
        });
        
        console.log('⚙️ WaveTalk Édu - Parent settings loaded');
    </script>
</body>
</html>