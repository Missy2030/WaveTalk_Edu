<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installer WaveTalk Édu</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .install-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%);
        }
        
        .install-card {
            background: white;
            border-radius: var(--radius-xl);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: var(--shadow-2xl);
            text-align: center;
        }
        
        .install-icon {
            width: 120px;
            height: 120px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 3.5rem;
            color: white;
        }
        
        .feature-list {
            text-align: left;
            margin: 30px 0;
            padding: 0 20px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: var(--radius);
            transition: background 0.2s;
        }
        
        .feature-item:hover {
            background: var(--gray-50);
        }
        
        .feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }
        
        .install-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
        }
        
        @media (max-width: 640px) {
            .install-card {
                padding: 30px 20px;
            }
            
            .install-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-card">
            <div class="install-icon">
                <i class="fas fa-podcast"></i>
            </div>
            
            <h1 style="margin-bottom: 10px;">Installer WaveTalk Édu</h1>
            <p style="color: var(--gray-500);">
                Ajoutez l'application à votre écran d'accueil pour un accès rapide et une expérience améliorée.
            </p>
            
            <div class="feature-list">
                <div class="feature-item">
                    <div class="feature-icon" style="background: var(--success);">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div>
                        <strong>Chargement instantané</strong>
                        <p style="margin: 5px 0 0; color: var(--gray-500); font-size: 0.9rem;">
                            Lancez l'app en un clic depuis votre écran d'accueil
                        </p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon" style="background: var(--primary);">
                        <i class="fas fa-wifi-slash"></i>
                    </div>
                    <div>
                        <strong>Mode hors ligne</strong>
                        <p style="margin: 5px 0 0; color: var(--gray-500); font-size: 0.9rem;">
                            Écoutez vos cours sans connexion Internet
                        </p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon" style="background: var(--warning);">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div>
                        <strong>Notifications push</strong>
                        <p style="margin: 5px 0 0; color: var(--gray-500); font-size: 0.9rem;">
                            Recevez des rappels et mises à jour
                        </p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon" style="background: var(--accent);">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div>
                        <strong>Expérience native</strong>
                        <p style="margin: 5px 0 0; color: var(--gray-500); font-size: 0.9rem;">
                            Interface optimisée pour mobile
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="install-actions">
                <button id="installButton" class="btn btn-primary btn-lg">
                    <i class="fas fa-download"></i> Installer l'application
                </button>
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-times"></i> Plus tard
                </a>
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--gray-200);">
                <p style="color: var(--gray-400); font-size: 0.85rem;">
                    <i class="fas fa-info-circle"></i>
                    L'installation est gratuite et ne prend que quelques secondes.
                </p>
            </div>
        </div>
    </div>
    
    <script>
        let deferredPrompt;
        const installButton = document.getElementById('installButton');
        
        // Détecter l'événement beforeinstallprompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            installButton.style.display = 'inline-flex';
            
            installButton.addEventListener('click', async () => {
                if (!deferredPrompt) return;
                
                installButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Installation...';
                installButton.disabled = true;
                
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                
                if (outcome === 'accepted') {
                    installButton.innerHTML = '<i class="fas fa-check"></i> Installé !';
                    installButton.style.background = 'var(--success)';
                    
                    // Redirection après 2 secondes
                    setTimeout(() => {
                        window.location.href = 'student/dashboard.php';
                    }, 2000);
                } else {
                    installButton.innerHTML = '<i class="fas fa-download"></i> Installer l\'application';
                    installButton.disabled = false;
                }
                
                deferredPrompt = null;
            });
        });
        
        // Si l'application est déjà installée
        window.addEventListener('appinstalled', () => {
            console.log('✅ WaveTalk Édu installé avec succès');
            installButton.style.display = 'none';
        });
        
        // Vérifier si l'app est déjà installée
        if (window.matchMedia('(display-mode: standalone)').matches || 
            window.navigator.standalone === true) {
            installButton.style.display = 'none';
            setTimeout(() => {
                window.location.href = 'student/dashboard.php';
            }, 1000);
        }
        
        console.log('📱 WaveTalk Édu - Installation page loaded');
    </script>
</body>
</html>