<?php
// Début du fichier account_switcher.php - CORRIGÉ

// Gestion de session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    return;
}

// Déterminer dynamiquement la racine
function getBasePath() {
    $script = $_SERVER['SCRIPT_NAME'];
    $request = $_SERVER['REQUEST_URI'];
    
    // Si on est dans /public/parent/
    if (strpos($request, '/parent/') !== false) {
        return '../';
    }
    // Si on est dans /public/student/
    elseif (strpos($request, '/student/') !== false) {
        return '../';
    }
    // Si on est dans /public/
    elseif (strpos($request, '/public/') !== false || strpos($script, '/public/') !== false) {
        return '';
    }
    // Par défaut
    else {
        return '';
    }
}

$base_path = getBasePath();

// Connexion DB et récupération des enfants
$children = [];
if ($_SESSION['user_role'] === 'parent') {
    try {
        // Inclure la connexion DB
        $db_file = __DIR__ . '/../private/db_connection.php';
        if (file_exists($db_file)) {
            require_once $db_file;
            
            $stmt = $pdo->prepare("
                SELECT u.id, u.first_name, u.last_name, u.grade_level, u.email
                FROM parent_children pc
                JOIN users u ON pc.student_id = u.id
                WHERE pc.parent_id = ? AND pc.status = 'active'
                ORDER BY u.first_name
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $children = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        error_log("Account switcher DB error: " . $e->getMessage());
    }
}
?>

<!-- Widget de bascule de compte -->
<?php if ($_SESSION['user_role'] === 'parent'): ?>
<div class="account-switcher-widget" style="position: relative; display: inline-block;">
    <button class="account-switcher-toggle" style="
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 25px;
        padding: 8px 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 500;
    ">
        <i class="fas fa-users" style="color: #6366F1;"></i>
        <span>Mes enfants (<?php echo count($children); ?>)</span>
        <i class="fas fa-chevron-down" style="font-size: 0.8rem;"></i>
    </button>
    
    <div class="account-switcher-dropdown" style="
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        min-width: 250px;
        z-index: 1000;
        display: none;
        margin-top: 10px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
    ">
        <div style="padding: 15px; background: #f8f9fa; border-bottom: 1px solid #e5e7eb;">
            <div style="font-weight: 600; color: #374151;">Vos enfants</div>
            <div style="font-size: 0.9rem; color: #6b7280; margin-top: 5px;">
                <?php echo count($children); ?> enfant(s)
            </div>
        </div>
        
        <?php if (!empty($children)): ?>
        <div style="max-height: 300px; overflow-y: auto;">
            <?php foreach ($children as $child): ?>
            <div class="child-option" style="padding: 15px; border-bottom: 1px solid #f3f4f6;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <div style="width: 35px; height: 35px; border-radius: 50%; background: #6366F1; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                        <?php echo strtoupper(substr($child['first_name'], 0, 1)); ?>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?></div>
                        <div style="font-size: 0.8rem; color: #6b7280;"><?php echo htmlspecialchars($child['grade_level']); ?></div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <!-- MODE "VIEW" : Voir résultats -->
                    <a href="<?php echo $base_path; ?>switch_account.php?switch_to=student&target_id=<?php echo $child['id']; ?>&mode=view" 
                       style="
                            flex: 1;
                            background: #10B981;
                            color: white;
                            padding: 8px 12px;
                            border-radius: 6px;
                            text-decoration: none;
                            font-size: 0.85rem;
                            text-align: center;
                        ">
                        <i class="fas fa-eye" style="margin-right: 5px;"></i>
                        Voir résultats
                    </a>
                    
                    <!-- MODE "SWITCH" : Se connecter comme -->
                    <a href="<?php echo $base_path; ?>switch_account.php?switch_to=student&target_id=<?php echo $child['id']; ?>" 
                       style="
                            flex: 1;
                            background: #6366F1;
                            color: white;
                            padding: 8px 12px;
                            border-radius: 6px;
                            text-decoration: none;
                            font-size: 0.85rem;
                            text-align: center;
                        ">
                        <i class="fas fa-sign-in-alt" style="margin-right: 5px;"></i>
                        Se connecter
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="padding: 20px; text-align: center;">
            <i class="fas fa-child" style="font-size: 2rem; color: #d1d5db; margin-bottom: 10px;"></i>
            <p style="color: #6b7280; margin-bottom: 15px; font-size: 0.9rem;">
                Aucun enfant n'est associé à votre compte.
            </p>
        </div>
        <?php endif; ?>
        
        <!-- Bouton pour ajouter un enfant -->
        <div style="padding: 15px; border-top: 1px solid #e5e7eb;">
            <a href="<?php echo $base_path; ?>parent/add_child.php" 
               style="
                    display: block;
                    background: #F59E0B;
                    color: white;
                    padding: 10px;
                    border-radius: 6px;
                    text-decoration: none;
                    text-align: center;
                    font-weight: 600;
               ">
                <i class="fas fa-user-plus" style="margin-right: 5px;"></i>
                Ajouter un enfant
            </a>
        </div>
        
        <?php if (isset($_SESSION['is_switched']) && $_SESSION['is_switched']): ?>
        <div style="padding: 15px; background: #fef3c7; border-top: 1px solid #fde68a;">
            <a href="<?php echo $base_path; ?>switch_account.php?switch_back=1" 
               style="
                    display: block;
                    background: #F59E0B;
                    color: white;
                    padding: 10px;
                    border-radius: 6px;
                    text-decoration: none;
                    text-align: center;
                    font-weight: 600;
               ">
                <i class="fas fa-arrow-left" style="margin-right: 5px;"></i>
                Revenir à mon compte
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Gestion du menu déroulant
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.querySelector('.account-switcher-toggle');
        const dropdown = document.querySelector('.account-switcher-dropdown');
        
        if (toggle && dropdown) {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                const isVisible = dropdown.style.display === 'block';
                dropdown.style.display = isVisible ? 'none' : 'block';
            });
            
            // Fermer le menu si on clique ailleurs
            document.addEventListener('click', function() {
                dropdown.style.display = 'none';
            });
            
            dropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });
</script>
<?php endif; ?>