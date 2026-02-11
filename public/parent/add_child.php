<?php
// Session gérée par db_connection.php
require_once '../../private/db_connection.php';

// Vérifier que c'est bien un parent connecté
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'parent') {
    header('Location: ../login.php');
    exit();
}

$parent_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $grade_level = $_POST['grade_level'] ?? null;
    
    // Validation
    if (empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($grade_level)) {
        $error = "Tous les champs sont obligatoires.";
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
                // Générer username
                $username = strtolower($first_name . '.' . $last_name . rand(100, 999));
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Créer l'utilisateur enfant
                $stmt = $pdo->prepare("
                    INSERT INTO users (email, password, username, first_name, last_name, role, grade_level, is_active, created_at)
                    VALUES (?, ?, ?, ?, ?, 'student', ?, 1, NOW())
                ");
                $stmt->execute([$email, $hashed_password, $username, $first_name, $last_name, $grade_level]);
                
                $child_id = $pdo->lastInsertId();
                
                // Créer la liaison parent-enfant
                $stmt = $pdo->prepare("
                    INSERT INTO parent_children (parent_id, student_id, status, created_at)
                    VALUES (?, ?, 'active', NOW())
                ");
                $stmt->execute([$parent_id, $child_id]);
                
                // Envoyer un email à l'enfant avec ses identifiants
                // (à implémenter selon votre système d'email)
                
                $success = "Votre enfant " . htmlspecialchars($first_name) . " a été inscrit avec succès !";
            }
        } catch (Exception $e) {
            error_log("Add child error: " . $e->getMessage());
            $error = "Erreur lors de l'inscription : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>👶 Ajouter un enfant - WaveTalk Édu</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation avec retour -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../index.php" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-podcast"></i>
                </div>
                <span>WaveTalk Édu</span>
            </a>
            <div class="nav-links">
                <a href="dashboard.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Retour au dashboard
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Formulaire d'ajout -->
    <section class="hero" style="min-height: calc(100vh - 100px); padding: 60px 20px;">
        <div style="max-width: 500px; margin: 0 auto; width: 100%;">
            <div class="card" style="padding: 40px;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                        <i class="fas fa-child" style="font-size: 1.5rem; color: white;"></i>
                    </div>
                    <h2 style="margin-bottom: 10px;">Ajouter votre enfant</h2>
                    <p style="color: #6b7280;">Créez un compte pour votre enfant</p>
                </div>
                
                <?php if ($error): ?>
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #10b981; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    <div style="margin-top: 10px;">
                        <a href="dashboard.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye"></i> Voir mes enfants
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Prénom de l'enfant *</label>
                        <input type="text" class="form-control" name="first_name" required 
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nom de l'enfant *</label>
                        <input type="text" class="form-control" name="last_name" required 
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Adresse email *</label>
                        <input type="email" class="form-control" name="email" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        <small style="color: #6b7280;">L'enfant utilisera cette adresse pour se connecter</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Niveau scolaire *</label>
                        <select class="form-control" name="grade_level" required>
                            <option value="">Sélectionnez le niveau</option>
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
                        <label class="form-label">Mot de passe *</label>
                        <input type="password" class="form-control" name="password" required minlength="6">
                        <small style="color: #6b7280;">Minimum 6 caractères</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Confirmer le mot de passe *</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    
                    <div style="margin: 25px 0;">
                        <label style="display: flex; align-items: flex-start;">
                            <input type="checkbox" name="terms" required style="margin-top: 3px; margin-right: 10px;">
                            <span style="color: #6b7280; font-size: 0.9rem;">
                                Je certifie être le parent/tuteur légal de cet enfant et autorise son inscription.
                            </span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-user-plus"></i> Inscrire mon enfant
                    </button>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="dashboard.php" class="btn btn-outline" style="width: 100%;">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</body>
</html>