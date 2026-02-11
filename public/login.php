<?php
session_start();
require_once '../private/db_connection.php';

// Redirection si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: student/dashboard.php');
    exit();
}

$error = '';
$success = '';



// Dans la section de connexion (vers la ligne 30-50), remplacez :

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Vérifier si la colonne is_active existe
            $is_active = isset($user['is_active']) ? $user['is_active'] : 1;
            
            if ($is_active) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] ?? $user['email'];
                $_SESSION['user_role'] = $user['role'] ?? 'student';
                
                // Redirection selon le rôle
                if (isset($user['role']) && $user['role'] === 'parent') {
                    header('Location: parent/dashboard.php');
                } else {
                    header('Location: student/dashboard.php');
                }
                exit();
            } else {
                $error = "Votre compte est désactivé. Contactez l'administrateur.";
            }
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    } catch (Exception $e) {
        $error = "Erreur de connexion. Veuillez réessayer.";
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔐 Connexion - WaveTalk Édu</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
                <a href="register.php" class="btn btn-outline">S'inscrire</a>
            </div>
        </div>
    </nav>
    
    <!-- Section Connexion -->
    <section class="hero" style="min-height: calc(100vh - 100px); padding: 60px 20px; display: flex; align-items: center;">
        <div style="max-width: 1200px; margin: 0 auto; width: 100%;">
            <div class="grid grid-cols-2" style="gap: 60px; align-items: center;">
                <!-- Illustration -->
                <div class="slide-in">
                    <div style="text-align: center;">
                        <div style="width: 300px; height: 300px; margin: 0 auto 30px; position: relative;">
                            <div style="position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 200px; height: 200px; background: var(--gradient-primary); border-radius: 50%; opacity: 0.1;"></div>
                            <div style="position: absolute; top: 50px; left: 50%; transform: translateX(-50%); width: 250px; height: 250px; background: var(--gradient-accent); border-radius: 50%; opacity: 0.05;"></div>
                            <div style="position: relative; z-index: 2; background: white; padding: 40px; border-radius: var(--radius-xl); box-shadow: var(--shadow-xl); border: 1px solid var(--gray-200); margin-top: 50px;">
                                <div style="width: 80px; height: 80px; background: var(--gradient-primary); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                                    <i class="fas fa-headphones" style="font-size: 2rem; color: white;"></i>
                                </div>
                                <h3 style="margin-bottom: 10px;">Bienvenue à nouveau !</h3>
                                <p style="color: var(--gray-500);">Reprenez votre apprentissage audio là où vous l'avez laissé.</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-3" style="gap: 15px; max-width: 400px; margin: 40px auto 0;">
                            <div class="card" style="text-align: center; padding: 15px;">
                                <div class="badge badge-primary" style="margin-bottom: 10px;">🎧</div>
                                <p style="font-size: 0.9rem; margin: 0;">Cours audio</p>
                            </div>
                            <div class="card" style="text-align: center; padding: 15px;">
                                <div class="badge badge-success" style="margin-bottom: 10px;">📈</div>
                                <p style="font-size: 0.9rem; margin: 0;">Progression</p>
                            </div>
                            <div class="card" style="text-align: center; padding: 15px;">
                                <div class="badge badge-warning" style="margin-bottom: 10px;">🏆</div>
                                <p style="font-size: 0.9rem; margin: 0;">Badges</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Formulaire -->
                <div class="card" style="padding: 40px;">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <div style="width: 60px; height: 60px; background: var(--gradient-primary); border-radius: var(--radius); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                            <i class="fas fa-sign-in-alt" style="font-size: 1.5rem; color: white;"></i>
                        </div>
                        <h2 style="margin-bottom: 10px;">Connexion</h2>
                        <p style="color: var(--gray-500);">Accédez à votre espace personnel</p>
                    </div>
                    
                    <?php if ($error): ?>
                    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 12px; border-radius: var(--radius); margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); color: var(--success); padding: 12px; border-radius: var(--radius); margin-bottom: 20px;">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label class="form-label" for="email">
                                <i class="fas fa-envelope"></i> Adresse email
                            </label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="votre@email.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="password">
                                <i class="fas fa-lock"></i> Mot de passe
                            </label>
                            <div style="position: relative;">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Votre mot de passe" required>
                                <button type="button" id="togglePassword" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--gray-400); cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="checkbox" name="remember" style="margin-right: 8px;">
                                <span style="color: var(--gray-600); font-size: 0.9rem;">Se souvenir de moi</span>
                            </label>
                            
                            <a href="forgot-password.php" style="color: var(--primary); font-size: 0.9rem;">
                                Mot de passe oublié ?
                            </a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-sign-in-alt"></i> Se connecter
                        </button>
                    </form>
                    
                    <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--gray-200);">
                        <p style="color: var(--gray-500); margin-bottom: 15px;">
                            Nouveau sur WaveTalk Édu ?
                        </p>
                        <a href="register.php" class="btn btn-outline" style="width: 100%;">
                            <i class="fas fa-user-plus"></i> Créer un compte
                        </a>
                    </div>
                    
                    <div style="margin-top: 25px; text-align: center;">
                        <p style="color: var(--gray-400); font-size: 0.9rem;">
                            En vous connectant, vous acceptez nos 
                            <a href="#" style="color: var(--primary);">conditions d'utilisation</a> 
                            et notre 
                            <a href="#" style="color: var(--primary);">politique de confidentialité</a>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer style="background: var(--gray-900); color: white; padding: 30px 20px; text-align: center;">
        <p>WaveTalk Édu &copy; <?php echo date('Y'); ?> - Mastercard Foundation EdTech Fellowship</p>
    </footer>
    
    <script>
        // Afficher/masquer le mot de passe
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
        
        // Animation d'entrée
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.slide-in').forEach((el, index) => {
                el.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>