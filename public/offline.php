<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WaveTalk Édu - Mode hors ligne</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .offline-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            text-align: center;
        }
        
        .offline-content {
            max-width: 500px;
            background: white;
            padding: 40px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
        }
        
        .offline-icon {
            width: 100px;
            height: 100px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 3rem;
            color: white;
        }
        
        .offline-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-content">
            <div class="offline-icon">
                <i class="fas fa-wifi-slash"></i>
            </div>
            
            <h1 style="margin-bottom: 15px;">Mode hors ligne</h1>
            <p style="color: var(--gray-500); margin-bottom: 10px;">
                Vous êtes actuellement hors ligne. Certaines fonctionnalités peuvent être limitées.
            </p>
            <p style="color: var(--gray-400); font-size: 0.9rem;">
                Vos progrès seront synchronisés dès que vous serez reconnecté.
            </p>
            
            <div class="offline-actions">
                <button onclick="location.reload()" class="btn btn-primary">
                    <i class="fas fa-redo"></i> Réessayer
                </button>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                <button onclick="window.location.href = '/student/dashboard.php'" class="btn btn-outline">
                    <i class="fas fa-home"></i> Dashboard
                </button>
                <?php else: ?>
                <button onclick="window.location.href = '/'" class="btn btn-outline">
                    <i class="fas fa-home"></i> Accueil
                </button>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--gray-200);">
                <p style="color: var(--gray-400); font-size: 0.85rem;">
                    <i class="fas fa-info-circle"></i>
                    Fonctionnalités disponibles hors ligne :
                </p>
                <ul style="text-align: left; color: var(--gray-500); font-size: 0.9rem; margin-top: 10px;">
                    <li>Consultation des cours téléchargés</li>
                    <li>Quiz déjà chargés</li>
                    <li>Suivi de progression local</li>
                    <li>Badges débloqués</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>