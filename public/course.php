<?php
// course.php - VERSION FINALE CORRIGÉE AVEC BADGES
require_once '../private/db_connection.php';

// Activer les erreurs pour debug (enlever en production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($course_id === 0) {
    header('Location: category.php');
    exit();
}

// Récupérer le cours avec audio direct
try {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(ch.id) as chapter_count,
               SUM(ch.duration) as total_duration
        FROM courses c
        LEFT JOIN chapters ch ON c.id = ch.course_id
        WHERE c.id = ? AND c.is_active = 1
        GROUP BY c.id
    ");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        die("Cours non trouvé. <a href='category.php'>Retour aux cours</a>");
    }
    
    // Récupérer la progression de l'utilisateur
    $stmt = $pdo->prepare("
        SELECT 
            up.listened_time,
            up.last_position,
            up.completed,
            up.quiz_score
        FROM user_progress up
        WHERE up.course_id = ? AND up.user_id = ?
    ");
    $stmt->execute([$course_id, $user_id]);
    $progress = $stmt->fetch();
    
    // Calculer le pourcentage de progression
    $listened_time = $progress ? intval($progress['listened_time']) : 0;
    $total_duration = intval($course['total_duration'] ?? $course['duration'] ?? 0);
    $progress_percentage = $total_duration > 0 ? min(100, round(($listened_time / $total_duration) * 100)) : 0;
    $is_completed = $progress ? boolval($progress['completed']) : false;
    
} catch (Exception $e) {
    die("Erreur DB: " . $e->getMessage() . " <a href='category.php'>Retour</a>");
}

// Déterminer la source audio - VERSION CORRIGÉE
$audio_src = '';
if (!empty($course['audio_file'])) {
    $file_path = $course['audio_file'];
    
    // Nettoyer les chemins absolus du serveur
    $file_path = str_replace('/home/serena/Téléchargements/SERENA/Wavetalk_Edu/', '', $file_path);
    $file_path = str_replace('/home/serena/Téléchargements/SERENA/Wavetalk_Edu (2)/', '', $file_path);
    
    // S'assurer que le chemin commence correctement
    if (strpos($file_path, 'public/') === 0) {
        $audio_src = '/' . $file_path;
    } elseif (strpos($file_path, 'audio/') === 0) {
        $audio_src = '/public/' . $file_path;
    } elseif (strpos($file_path, '/') === 0) {
        $audio_src = $file_path;
    } else {
        $audio_src = '/public/' . $file_path;
    }
} elseif (!empty($course['audio_url'])) {
    $audio_src = $course['audio_url'];
}

// Encodage des caractères spéciaux pour l'URL
$path_parts = explode('/', $audio_src);
$filename = array_pop($path_parts);
$base_path = implode('/', $path_parts);
$audio_src = $base_path . '/' . rawurlencode(urldecode($filename));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> | WaveTalk Édu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-5px);
        }
        .course-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .course-header {
            border-bottom: 2px solid #F3F4F6;
            padding-bottom: 25px;
            margin-bottom: 30px;
        }
        .course-title {
            font-size: 2rem;
            color: #1F2937;
            margin-bottom: 15px;
        }
        .course-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6B7280;
            font-size: 0.95rem;
        }
        .meta-item i {
            color: #667eea;
        }
        .progress-section {
            background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .progress-label {
            color: #374151;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .progress-percentage {
            color: #667eea;
            font-weight: 700;
            font-size: 1.5rem;
        }
        .progress-bar-container {
            background: white;
            height: 12px;
            border-radius: 10px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        .completion-badge {
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
            font-weight: 600;
        }
        .audio-player {
            background: linear-gradient(135deg, #F9FAFB 0%, #F3F4F6 100%);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .player-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        .control-btn {
            background: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            color: #374151;
        }
        .control-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        .play-btn {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .progress-bar {
            flex: 1;
            height: 8px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            cursor: pointer;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            width: 0%;
            transition: width 0.1s linear;
        }
        .time-display {
            color: #6B7280;
            font-weight: 600;
            font-size: 0.9rem;
            min-width: 100px;
            text-align: center;
        }
        .speed-controls {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .speed-btn {
            background: white;
            border: 2px solid #E5E7EB;
            padding: 8px 16px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            color: #6B7280;
            font-weight: 600;
        }
        .speed-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }
        .speed-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
        .download-section {
            background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
        }
        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #0EA5E9, #0284C7);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            margin-top: 15px;
            transition: all 0.3s;
        }
        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(14, 165, 233, 0.3);
        }
        .description {
            line-height: 1.8;
            color: #4B5563;
            margin-top: 20px;
        }
        
        /* Styles pour les notifications de badges */
        @keyframes slideInBadge {
            from {
                transform: translateX(400px) scale(0.8);
                opacity: 0;
            }
            to {
                transform: translateX(0) scale(1);
                opacity: 1;
            }
        }
        
        @keyframes slideOutBadge {
            from {
                transform: translateX(0) scale(1);
                opacity: 1;
            }
            to {
                transform: translateX(400px) scale(0.8);
                opacity: 0;
            }
        }
        
        .badge-notification {
            position: fixed;
            right: 20px;
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
            z-index: 10000;
            min-width: 300px;
            animation: slideInBadge 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        .badge-notification:hover {
            transform: scale(1.02);
            transition: transform 0.2s;
        }
        
        .badge-notification button:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
            transition: all 0.2s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0.8);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="category.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Retour aux cours
        </a>
        
        <div class="course-card">
            <div class="course-header">
                <h1 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h1>
                <div class="course-meta">
                    <div class="meta-item">
                        <i class="fas fa-book-open"></i>
                        <span><?php echo htmlspecialchars($course['subject']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-graduation-cap"></i>
                        <span><?php echo htmlspecialchars($course['grade_level']); ?></span>
                    </div>
                    <?php if ($total_duration > 0): ?>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo floor($total_duration / 60); ?> min</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="course-content">
                <!-- Section de progression -->
                <div class="progress-section">
                    <div class="progress-header">
                        <span class="progress-label">
                            <i class="fas fa-chart-line"></i> Votre progression
                        </span>
                        <span class="progress-percentage" id="progressPercentage">
                            <?php echo $progress_percentage; ?>%
                        </span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar-fill" id="courseProgressBar" style="width: <?php echo $progress_percentage; ?>%;"></div>
                    </div>
                    <?php if ($is_completed): ?>
                    <div class="completion-badge">
                        <i class="fas fa-check-circle"></i>
                        Cours terminé !
                    </div>
                    <?php endif; ?>
                    <div style="margin-top: 10px; color: #6B7280; font-size: 0.9rem;">
                        Temps d'écoute : <strong><?php echo floor($listened_time / 60); ?> min</strong>
                        sur <?php echo floor($total_duration / 60); ?> min
                    </div>
                </div>
                
                <!-- Lecteur audio -->
                <?php if (!empty($audio_src)): ?>
                <div class="audio-player">
                    <h3 style="margin-bottom: 20px; color: #1F2937;">
                        <i class="fas fa-headphones"></i> Écouter le cours
                    </h3>
                    
                    <div class="player-controls">
                        <button class="control-btn" id="rewindBtn">
                            <i class="fas fa-backward"></i>
                        </button>
                        <button class="control-btn play-btn" id="playPauseBtn">
                            <i class="fas fa-play" id="playIcon"></i>
                        </button>
                        <button class="control-btn" id="forwardBtn">
                            <i class="fas fa-forward"></i>
                        </button>
                        
                        <div class="progress-bar" id="audioProgressBar">
                            <div class="progress-fill" id="audioProgressFill"></div>
                        </div>
                        
                        <span class="time-display">
                            <span id="currentTime">0:00</span> / 
                            <span id="totalTime">0:00</span>
                        </span>
                    </div>
                    
                    <div class="speed-controls">
                        <span style="color: #6B7280; font-weight: 600; margin-right: 10px;">Vitesse :</span>
                        <button class="speed-btn" data-speed="0.75">0.75x</button>
                        <button class="speed-btn active" data-speed="1">1x</button>
                        <button class="speed-btn" data-speed="1.25">1.25x</button>
                        <button class="speed-btn" data-speed="1.5">1.5x</button>
                        <button class="speed-btn" data-speed="2">2x</button>
                    </div>
                    
                    <audio id="audioElement" preload="metadata">
                        <source src="<?php echo htmlspecialchars($audio_src); ?>" type="audio/mpeg">
                        Votre navigateur ne supporte pas la lecture audio.
                    </audio>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 60px 20px; color: #9CA3AF;">
                    <i class="fas fa-headphones" style="font-size: 4rem; margin-bottom: 20px; opacity: 0.3;"></i>
                    <h3 style="margin-bottom: 10px; color: #6B7280;">Aucun audio disponible</h3>
                    <p>Ce cours n'a pas encore d'enregistrement audio.</p>
                </div>
                <?php endif; ?>
                
                <!-- Section téléchargement -->
                <?php if ($course['is_downloadable']): ?>
                <div class="download-section">
                    <h3 style="color: #0369A1; margin-bottom: 10px;">
                        <i class="fas fa-download"></i> Télécharger ce cours
                    </h3>
                    <p style="color: #475569; margin-bottom: 15px;">
                        Écoutez ce cours hors ligne à tout moment
                    </p>
                    <a href="<?php echo htmlspecialchars($audio_src); ?>" 
                       download="<?php echo htmlspecialchars($course['title']); ?>.m4a" 
                       class="download-btn" 
                       id="downloadBtn">
                        <i class="fas fa-download"></i>
                        Télécharger l'audio
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Description -->
                <?php if (!empty($course['description'])): ?>
                <div class="description">
                    <h3 style="color: #1F2937; margin-bottom: 15px;">
                        <i class="fas fa-info-circle"></i> Description
                    </h3>
                    <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Configuration
        const userId = <?php echo $user_id; ?>;
        const courseId = <?php echo $course_id; ?>;
        const totalDuration = <?php echo $total_duration; ?>;
        
        // Éléments DOM
        const audio = document.getElementById('audioElement');
        const playPauseBtn = document.getElementById('playPauseBtn');
        const playIcon = document.getElementById('playIcon');
        const audioProgressBar = document.getElementById('audioProgressBar');
        const audioProgressFill = document.getElementById('audioProgressFill');
        const currentTimeEl = document.getElementById('currentTime');
        const totalTimeEl = document.getElementById('totalTime');
        const courseProgressBar = document.getElementById('courseProgressBar');
        const progressPercentageEl = document.getElementById('progressPercentage');
        
        // Variables de suivi
        let lastSaveTime = 0;
        let totalListenedTime = <?php echo $listened_time; ?>;
        const SAVE_INTERVAL = 5000; // Sauvegarder toutes les 5 secondes
        
        // Charger la durée totale
        audio.addEventListener('loadedmetadata', () => {
            totalTimeEl.textContent = formatTime(audio.duration);
        });
        
        // Play/Pause
        playPauseBtn.addEventListener('click', () => {
            if (audio.paused) {
                audio.play();
                playIcon.className = 'fas fa-pause';
            } else {
                audio.pause();
                playIcon.className = 'fas fa-play';
            }
        });
        
        // Mise à jour de la progression audio
        audio.addEventListener('timeupdate', () => {
            // Mettre à jour la barre de progression audio
            const percent = (audio.currentTime / audio.duration) * 100;
            audioProgressFill.style.width = percent + '%';
            currentTimeEl.textContent = formatTime(audio.currentTime);
            
            // Sauvegarder la progression périodiquement
            const now = Date.now();
            if (now - lastSaveTime > SAVE_INTERVAL) {
                saveProgress();
                lastSaveTime = now;
            }
        });
        
        // Clic sur la barre de progression
        audioProgressBar.addEventListener('click', (e) => {
            const rect = audioProgressBar.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            audio.currentTime = percent * audio.duration;
        });
        
        // Reculer de 15 secondes
        document.getElementById('rewindBtn').addEventListener('click', () => {
            audio.currentTime = Math.max(0, audio.currentTime - 15);
        });
        
        // Avancer de 15 secondes
        document.getElementById('forwardBtn').addEventListener('click', () => {
            audio.currentTime = Math.min(audio.duration, audio.currentTime + 15);
        });
        
        // Contrôle de vitesse
        document.querySelectorAll('.speed-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const speed = parseFloat(this.dataset.speed);
                audio.playbackRate = speed;
                document.querySelectorAll('.speed-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Sauvegarder quand l'utilisateur quitte la page
        window.addEventListener('beforeunload', () => {
            saveProgress();
        });
        
        // Sauvegarder quand l'audio se termine
        audio.addEventListener('ended', () => {
            totalListenedTime = totalDuration;
            saveProgress(true); // Marquer comme terminé
        });
        
        // ============================================================
        // FONCTION DE SAUVEGARDE DE PROGRESSION - VERSION AMÉLIORÉE
        // ============================================================
        function saveProgress(completed = false) {
            const currentTime = audio.currentTime || 0;
            const listenedSeconds = Math.floor(currentTime);
            
            // Déterminer si le cours est complété (>90% écouté)
            const progressPercent = (listenedSeconds / totalDuration) * 100;
            const isCompleted = completed || progressPercent >= 90;
            
            const data = {
                user_id: userId,
                course_id: courseId,
                listened_time: listenedSeconds,
                current_position: currentTime,
                is_completed: isCompleted
            };
            
            fetch('/public/api/track_progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
                keepalive: true
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Mettre à jour l'affichage de la progression
                    updateCourseProgress(progressPercent);
                    console.log('✅ Progression sauvegardée:', progressPercent.toFixed(1) + '%');
                    
                    // Afficher les nouveaux badges débloqués
                    if (result.new_badges && result.new_badges.length > 0) {
                        console.log('🏆 Nouveaux badges:', result.new_badges);
                        showBadgeNotification(result.new_badges);
                    }
                    
                    // Si le cours est terminé, afficher le message de félicitations
                    if (result.data && result.data.is_completed && !completed) {
                        setTimeout(() => {
                            showCompletionMessage();
                        }, 1000);
                    }
                } else {
                    console.error('❌ Erreur sauvegarde:', result.error);
                }
            })
            .catch(error => {
                console.error('❌ Erreur réseau:', error);
            });
        }
        
        // ============================================================
        // FONCTION DE NOTIFICATION DE BADGE
        // ============================================================
        function showBadgeNotification(badges) {
            if (typeof badges === 'string') {
                badges = [badges];
            }
            
            badges.forEach((badgeName, index) => {
                setTimeout(() => {
                    const notification = document.createElement('div');
                    notification.className = 'badge-notification';
                    notification.style.top = `${20 + (index * 100)}px`;
                    
                    notification.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="font-size: 3rem; line-height: 1;">🏆</div>
                            <div style="flex: 1;">
                                <div style="font-weight: 700; font-size: 1.2rem; margin-bottom: 5px;">
                                    🎉 Nouveau badge débloqué!
                                </div>
                                <div style="font-size: 1rem; opacity: 0.95;">
                                    ${badgeName}
                                </div>
                            </div>
                            <button onclick="this.parentElement.parentElement.remove()" 
                                    style="background: rgba(255,255,255,0.2); border: none; 
                                           color: white; width: 30px; height: 30px; 
                                           border-radius: 50%; cursor: pointer; 
                                           font-size: 1.2rem; display: flex; 
                                           align-items: center; justify-content: center;">
                                ×
                            </button>
                        </div>
                    `;
                    
                    document.body.appendChild(notification);
                    
                    // Son de notification
                    playBadgeSound();
                    
                    // Animation de disparition après 6 secondes
                    setTimeout(() => {
                        notification.style.animation = 'slideOutBadge 0.5s ease-in forwards';
                        setTimeout(() => {
                            notification.remove();
                        }, 500);
                    }, 6000);
                    
                }, index * 200);
            });
        }
        
        // ============================================================
        // SON DE NOTIFICATION
        // ============================================================
        function playBadgeSound() {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.5);
            } catch (e) {
                console.log('Son de notification non disponible');
            }
        }
        
        // ============================================================
        // MESSAGE DE FÉLICITATIONS
        // ============================================================
        function showCompletionMessage() {
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                animation: fadeIn 0.3s;
            `;
            
            overlay.innerHTML = `
                <div style="background: white; padding: 40px; border-radius: 20px; 
                            max-width: 500px; text-align: center; animation: scaleIn 0.5s;">
                    <div style="font-size: 5rem; margin-bottom: 20px;">🎉</div>
                    <h2 style="color: #1F2937; margin-bottom: 15px; font-size: 2rem;">
                        Félicitations !
                    </h2>
                    <p style="color: #6B7280; font-size: 1.1rem; margin-bottom: 30px;">
                        Vous avez terminé ce cours avec succès !
                    </p>
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <a href="category.php" 
                           style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                  color: white; padding: 15px 30px; border-radius: 50px;
                                  text-decoration: none; font-weight: 600;">
                            Découvrir d'autres cours
                        </a>
                        <button onclick="this.parentElement.parentElement.parentElement.remove()"
                                style="background: #E5E7EB; color: #374151; padding: 15px 30px;
                                       border-radius: 50px; border: none; cursor: pointer;
                                       font-weight: 600;">
                            Fermer
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(overlay);
            
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.remove();
                }
            });
        }
        
        // Mettre à jour l'affichage de la progression du cours
        function updateCourseProgress(percent) {
            const roundedPercent = Math.min(100, Math.round(percent));
            courseProgressBar.style.width = roundedPercent + '%';
            progressPercentageEl.textContent = roundedPercent + '%';
        }
        
        // Formater le temps
        function formatTime(seconds) {
            if (isNaN(seconds)) return '0:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }
        
        // Gérer le téléchargement
        document.getElementById('downloadBtn')?.addEventListener('click', function(e) {
            e.preventDefault();
            
            const link = document.createElement('a');
            link.href = '<?php echo htmlspecialchars($audio_src); ?>';
            link.download = '<?php echo htmlspecialchars($course['title']); ?>.m4a';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            console.log('Téléchargement démarré');
        });
    </script>
</body>
</html>