<?php
session_start();
require_once '../private/db_connection.php';

// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirection si déjà connecté
if (isset($_SESSION['user_id'])) {
    $redirect = ($_SESSION['user_role'] === 'parent') ? 'parent/dashboard.php' : 'student/dashboard.php';
    header('Location: ' . $redirect);
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $role = $_POST['role'] ?? 'student';
    $grade_level = $_POST['grade_level'] ?? null;
    
    // Générer un nom d'utilisateur automatique
    $username = strtolower($first_name . '.' . $last_name . rand(100, 999));
    
    // Validation
    if (empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = "Tous les champs obligatoires doivent être remplis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = "Cette adresse email est déjà utilisée.";
            } else {
                // Hachage du mot de passe
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Préparer la requête selon le rôle
                if ($role === 'student') {
                    // Pour les élèves, grade_level est requis
                    if (empty($grade_level)) {
                        $error = "Veuillez sélectionner votre niveau scolaire.";
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO users (email, password, username, first_name, last_name, role, grade_level, is_active, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
                        ");
                        $stmt->execute([$email, $hashed_password, $username, $first_name, $last_name, $role, $grade_level]);
                    }
                } else {
                    // Pour les parents, grade_level est NULL
                    $stmt = $pdo->prepare("
                        INSERT INTO users (email, password, username, first_name, last_name, role, is_active, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                    ");
                    $stmt->execute([$email, $hashed_password, $username, $first_name, $last_name, $role]);
                }
                
                if (empty($error)) {
                    $user_id = $pdo->lastInsertId();
                    
                    // LIAISON AUTOMATIQUE PARENT-ENFANT
                    if ($role === 'student') {
                        // Chercher si un parent avec le même domaine email existe
                        $email_parts = explode('@', $email);
                        $email_domain = '@' . $email_parts[1];
                        $parent_email = "parent" . $email_domain;
                        
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'parent'");
                        $stmt->execute([$parent_email]);
                        $parent = $stmt->fetch();
                        
                        if ($parent) {
                            // Créer automatiquement la liaison
                            $stmt = $pdo->prepare("
                                INSERT INTO parent_children (parent_id, student_id, status, created_at)
                                VALUES (?, ?, 'active', NOW())
                            ");
                            $stmt->execute([$parent['id'], $user_id]);
                        }
                    }
                    
                    // Connexion automatique
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_name'] = $first_name;
                    $_SESSION['user_role'] = $role;
                    
                    // Redirection selon le rôle
                    if ($role === 'parent') {
                        header('Location: parent/dashboard.php');
                    } else {
                        header('Location: student/dashboard.php');
                    }
                    exit();
                }
            }
        } catch (Exception $e) {
            $error = "Erreur lors de l'inscription : " . $e->getMessage();
            error_log("Registration error: " . $e->getMessage());
            
            // Message d'erreur plus détaillé pour le débogage
            if (isset($_GET['debug'])) {
                $error .= "<br><small>Debug: username='$username', email='$email', role='$role'</small>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📝 Inscription - WaveTalk Édu</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .role-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .role-option {
            flex: 1;
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .role-option:hover {
            border-color: #6366F1;
        }
        
        .role-option.selected {
            border-color: #6366F1;
            background: rgba(99, 102, 241, 0.1);
        }
        
        .role-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .grade-level-select {
            display: none;
            margin-top: 15px;
        }
        
        .grade-level-select.show {
            display: block;
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            color: #ef4444;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        
        .error-message i {
            margin-right: 8px;
        }
        
        .debug-info {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 0.9rem;
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
                <a href="login.php" class="btn btn-outline">Connexion</a>
            </div>
        </div>
    </nav>
    
    <!-- Section Inscription -->
    <section class="hero" style="min-height: calc(100vh - 100px); padding: 60px 20px; display: flex; align-items: center;">
        <div style="max-width: 500px; margin: 0 auto; width: 100%;">
            <div class="card" style="padding: 40px;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <div style="width: 60px; height: 60px; background: var(--gradient-primary); border-radius: var(--radius); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                        <i class="fas fa-user-plus" style="font-size: 1.5rem; color: white;"></i>
                    </div>
                    <h2 style="margin-bottom: 10px;">Créer un compte</h2>
                    <p style="color: var(--gray-500);">Rejoignez la communauté WaveTalk Édu</p>
                </div>
                
                <?php if (isset($_GET['debug'])): ?>
                <div class="debug-info">
                    <strong>Mode debug activé</strong><br>
                    Cette page affiche des informations de débogage pour résoudre les problèmes d'inscription.
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> 
                    <strong>Erreur :</strong> <?php echo $error; ?>
                    
                    <?php if (isset($_GET['debug'])): ?>
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #ef4444;">
                        <small>
                            <strong>Debug info:</strong><br>
                            Email: <?php echo htmlspecialchars($_POST['email'] ?? ''); ?><br>
                            Rôle: <?php echo htmlspecialchars($_POST['role'] ?? 'student'); ?><br>
                            Prénom: <?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #10b981; padding: 12px; border-radius: var(--radius); margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="registerForm">
                    <input type="hidden" name="debug" value="<?php echo isset($_GET['debug']) ? '1' : '0'; ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="first_name">
                            <i class="fas fa-user"></i> Prénom *
                        </label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               placeholder="Votre prénom" required 
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="last_name">
                            <i class="fas fa-user"></i> Nom *
                        </label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               placeholder="Votre nom" required 
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">
                            <i class="fas fa-envelope"></i> Adresse email *
                        </label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="votre@email.com" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <!-- Sélecteur de rôle -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user-tag"></i> Je suis *
                        </label>
                        <div class="role-selector">
                            <div class="role-option selected" data-role="student">
                                <div class="role-icon">👨‍🎓</div>
                                <div style="font-weight: 600;">Élève</div>
                                <div style="font-size: 0.85rem; color: var(--gray-500);">Je veux apprendre</div>
                                <input type="radio" name="role" value="student" checked style="display: none;">
                            </div>
                            
                            <div class="role-option" data-role="parent">
                                <div class="role-icon">👨‍👦</div>
                                <div style="font-weight: 600;">Parent</div>
                                <div style="font-size: 0.85rem; color: var(--gray-500);">Je veux suivre</div>
                                <input type="radio" name="role" value="parent" style="display: none;">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Niveau scolaire (uniquement pour élèves) -->
                    <div class="form-group grade-level-select show" id="gradeLevelSelect">
                        <label class="form-label" for="grade_level">
                            <i class="fas fa-graduation-cap"></i> Niveau scolaire *
                        </label>
                        <select class="form-control" id="grade_level" name="grade_level" required>
                            <option value="">Sélectionnez votre niveau</option>
                            <option value="6ème" <?php echo (($_POST['grade_level'] ?? '') === '6ème') ? 'selected' : ''; ?>>6ème</option>
                            <option value="5ème" <?php echo (($_POST['grade_level'] ?? '') === '5ème') ? 'selected' : ''; ?>>5ème</option>
                            <option value="4ème" <?php echo (($_POST['grade_level'] ?? '') === '4ème') ? 'selected' : ''; ?>>4ème</option>
                            <option value="3ème" <?php echo (($_POST['grade_level'] ?? '') === '3ème') ? 'selected' : ''; ?>>3ème</option>
                            <option value="Seconde" <?php echo (($_POST['grade_level'] ?? '') === 'Seconde') ? 'selected' : ''; ?>>Seconde</option>
                            <option value="Première" <?php echo (($_POST['grade_level'] ?? '') === 'Première') ? 'selected' : ''; ?>>Première</option>
                            <option value="Terminale" <?php echo (($_POST['grade_level'] ?? '') === 'Terminale') ? 'selected' : ''; ?>>Terminale</option>
                            <option value="Supérieur" <?php echo (($_POST['grade_level'] ?? '') === 'Supérieur') ? 'selected' : ''; ?>>Supérieur</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">
                            <i class="fas fa-lock"></i> Mot de passe *
                        </label>
                        <div style="position: relative;">
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Votre mot de passe" required 
                                   minlength="6">
                            <button type="button" class="toggle-password" 
                                    style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--gray-400); cursor: pointer;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small style="color: var(--gray-500); font-size: 0.85rem; display: block; margin-top: 5px;">
                            Minimum 6 caractères
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">
                            <i class="fas fa-lock"></i> Confirmer le mot de passe *
                        </label>
                        <div style="position: relative;">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Retapez votre mot de passe" required>
                            <button type="button" class="toggle-password" 
                                    style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--gray-400); cursor: pointer;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div style="margin: 25px 0;">
                        <label style="display: flex; align-items: flex-start; cursor: pointer;">
                            <input type="checkbox" name="terms" required style="margin-top: 3px; margin-right: 10px;">
                            <span style="color: var(--gray-600); font-size: 0.9rem; line-height: 1.5;">
                                J'accepte les 
                                <a href="#" style="color: var(--primary);">conditions d'utilisation</a> 
                                et la 
                                <a href="#" style="color: var(--primary);">politique de confidentialité</a> 
                                de WaveTalk Édu. *
                            </span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-user-plus"></i> Créer mon compte
                    </button>
                    
                    <div style="text-align: center; margin-top: 15px; font-size: 0.85rem; color: var(--gray-500);">
                        * Champs obligatoires
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="register.php?debug=1" style="color: #6b7280; font-size: 0.8rem;">
                            <i class="fas fa-bug"></i> Mode débogage
                        </a>
                    </div>
                </form>
                
                <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--gray-200);">
                    <p style="color: var(--gray-500); margin-bottom: 15px;">
                        Vous avez déjà un compte ?
                    </p>
                    <a href="login.php" class="btn btn-outline" style="width: 100%;">
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer style="background: var(--gray-900); color: white; padding: 30px 20px; text-align: center;">
        <p>WaveTalk Édu &copy; <?php echo date('Y'); ?> - Mastercard Foundation EdTech Fellowship</p>
    </footer>
    
    <script>
        // Sélecteur de rôle
        document.querySelectorAll('.role-option').forEach(option => {
            option.addEventListener('click', function() {
                // Désélectionner toutes les options
                document.querySelectorAll('.role-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Sélectionner l'option cliquée
                this.classList.add('selected');
                
                // Mettre à jour le radio bouton caché
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                
                // Afficher/masquer le niveau scolaire
                const role = this.dataset.role;
                const gradeLevelSelect = document.getElementById('gradeLevelSelect');
                const gradeLevelField = document.getElementById('grade_level');
                
                if (role === 'student') {
                    gradeLevelSelect.classList.add('show');
                    gradeLevelField.required = true;
                } else {
                    gradeLevelSelect.classList.remove('show');
                    gradeLevelField.required = false;
                    gradeLevelField.value = ''; // Vider la valeur pour les parents
                }
            });
        });
        
        // Afficher/masquer les mots de passe
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'fas fa-eye-slash';
                } else {
                    input.type = 'password';
                    icon.className = 'fas fa-eye';
                }
            });
        });
        
        // Validation du formulaire côté client
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const role = document.querySelector('input[name="role"]:checked').value;
            const gradeLevel = document.getElementById('grade_level').value;
            
            // Vérifier la correspondance des mots de passe
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('❌ Les mots de passe ne correspondent pas.');
                return false;
            }
            
            // Vérifier la longueur du mot de passe
            if (password.length < 6) {
                e.preventDefault();
                alert('❌ Le mot de passe doit contenir au moins 6 caractères.');
                return false;
            }
            
            // Pour les élèves, vérifier le niveau scolaire
            if (role === 'student' && !gradeLevel) {
                e.preventDefault();
                alert('❌ Veuillez sélectionner votre niveau scolaire.');
                return false;
            }
            
            return true;
        });
        
        // Pré-remplir les champs en cas d'erreur
        document.addEventListener('DOMContentLoaded', function() {
            const role = '<?php echo $_POST["role"] ?? "student"; ?>';
            if (role === 'parent') {
                const parentOption = document.querySelector('.role-option[data-role="parent"]');
                if (parentOption) {
                    parentOption.click();
                }
            }
        });
    </script>
</body>
</html>