<?php
/**
 * Page de vérification des certificats
 * @author WaveTalk Team
 * @version 1.0
 */

session_start();
require_once '../private/db_connection.php';

$certificate_code = isset($_GET['code']) ? trim($_GET['code']) : '';
$verified = false;
$certificate_data = null;

if (!empty($certificate_code)) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                cert.*,
                c.title as course_title,
                c.description,
                c.subject,
                u.first_name,
                u.last_name,
                u.grade_level,
                u.email,
                DATE_FORMAT(cert.issued_at, '%d/%m/%Y à %H:%i') as formatted_date
            FROM certificates cert
            JOIN courses c ON cert.course_id = c.id
            JOIN users u ON cert.user_id = u.id
            WHERE cert.certificate_code = ?
        ");
        $stmt->execute([$certificate_code]);
        $certificate_data = $stmt->fetch();
        
        $verified = ($certificate_data !== false);
        
    } catch (Exception $e) {
        error_log("Verify certificate error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Vérifier un certificat - WaveTalk Édu</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div style="max-width: 800px; margin: 60px auto; padding: 0 20px;">
        <div style="text-align: center; margin-bottom: 40px;">
            <a href="index.php" class="logo" style="display: inline-flex; align-items: center; gap: 10px; text-decoration: none; color: var(--primary); font-size: 1.5rem; margin-bottom: 20px;">
                <div class="logo-icon">
                    <i class="fas fa-podcast"></i>
                </div>
                <span>WaveTalk Édu</span>
            </a>
            <h1 style="color: var(--dark); margin-bottom: 15px;">🔍 Vérification de certificat</h1>
            <p style="color: var(--gray-500);">Vérifiez l'authenticité d'un certificat WaveTalk Édu</p>
        </div>
        
        <div style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="max-width: 500px; margin: 0 auto;">
                <div style="margin-bottom: 25px;">
                    <label style="display: block; margin-bottom: 8px; color: var(--dark); font-weight: 600;">
                        <i class="fas fa-key"></i> Code du certificat
                    </label>
                    <input type="text" 
                           name="code" 
                           value="<?php echo htmlspecialchars($certificate_code); ?>" 
                           placeholder="Ex: CERT-ABC123-XYZ789"
                           style="width: 100%; padding: 15px; border: 2px solid var(--gray-300); border-radius: 12px; font-size: 1rem;"
                           required>
                </div>
                <button type="submit" 
                        style="background: var(--primary); color: white; border: none; padding: 15px 30px; border-radius: 12px; font-size: 1rem; font-weight: 600; cursor: pointer; width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <i class="fas fa-search"></i> Vérifier le certificat
                </button>
            </form>
        </div>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($certificate_code)): ?>
            <?php if ($verified && $certificate_data): ?>
                <!-- Certificat valide -->
                <div style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: white; border-radius: 20px; padding: 40px; text-align: center; margin-bottom: 30px;">
                    <div style="font-size: 4rem; margin-bottom: 20px;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 style="margin-bottom: 15px;">Certificat Valide ✅</h2>
                    <p>Ce certificat a été vérifié et est authentique.</p>
                </div>
                
                <!-- Détails du certificat -->
                <div style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                    <h3 style="color: var(--dark); margin-bottom: 30px; border-bottom: 2px solid var(--gray-200); padding-bottom: 15px;">
                        <i class="fas fa-info-circle"></i> Détails du certificat
                    </h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px;">
                        <div>
                            <h4 style="color: var(--gray-500); font-size: 0.9rem; margin-bottom: 8px;">Élève</h4>
                            <p style="font-size: 1.2rem; color: var(--dark); font-weight: 600;">
                                <?php echo htmlspecialchars($certificate_data['first_name'] . ' ' . $certificate_data['last_name']); ?>
                            </p>
                        </div>
                        
                        <div>
                            <h4 style="color: var(--gray-500); font-size: 0.9rem; margin-bottom: 8px;">Cours</h4>
                            <p style="font-size: 1.2rem; color: var(--dark); font-weight: 600;">
                                <?php echo htmlspecialchars($certificate_data['course_title']); ?>
                            </p>
                        </div>
                        
                        <div>
                            <h4 style="color: var(--gray-500); font-size: 0.9rem; margin-bottom: 8px;">Matière</h4>
                            <p style="font-size: 1.2rem; color: var(--dark); font-weight: 600;">
                                <?php echo htmlspecialchars($certificate_data['subject']); ?>
                            </p>
                        </div>
                        
                        <div>
                            <h4 style="color: var(--gray-500); font-size: 0.9rem; margin-bottom: 8px;">Date d'émission</h4>
                            <p style="font-size: 1.2rem; color: var(--dark); font-weight: 600;">
                                <?php echo htmlspecialchars($certificate_data['formatted_date']); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid var(--gray-200);">
                        <h4 style="color: var(--dark); margin-bottom: 15px;">Code de vérification</h4>
                        <div style="background: #f3f4f6; padding: 15px; border-radius: 12px; font-family: 'Courier New', monospace; font-size: 1.1rem; color: var(--dark); text-align: center;">
                            <?php echo htmlspecialchars($certificate_data['certificate_code']); ?>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Certificat invalide -->
                <div style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); color: white; border-radius: 20px; padding: 40px; text-align: center;">
                    <div style="font-size: 4rem; margin-bottom: 20px;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h2 style="margin-bottom: 15px;">Certificat Non Valide ❌</h2>
                    <p>Le code fourni ne correspond à aucun certificat valide dans notre système.</p>
                    <div style="margin-top: 20px; font-size: 0.9rem; opacity: 0.9;">
                        Code entré : <strong><?php echo htmlspecialchars($certificate_code); ?></strong>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 40px; color: var(--gray-500);">
            <p>WaveTalk Édu &copy; <?php echo date('Y'); ?> - Mastercard Foundation EdTech Fellowship</p>
        </div>
    </div>
</body>
</html>