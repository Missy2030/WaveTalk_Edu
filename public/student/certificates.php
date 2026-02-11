
<?php
// Session gérée par db_connection.php
require_once '../../private/db_connection.php';
require_once '../../vendor/autoload.php'; // Pour FPDF ou autre librairie PDF

use FPDF\FPDF;

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($course_id <= 0) {
    header('Location: ../student/dashboard.php?error=invalid_course');
    exit();
}

try {
    // Vérifier si le cours est complété
    $stmt = $pdo->prepare("
        SELECT 
            c.title as course_title,
            c.description,
            u.first_name,
            u.last_name,
            u.grade_level,
            COUNT(DISTINCT ch.id) as total_chapters,
            COUNT(DISTINCT CASE WHEN up.completed = 1 THEN up.chapter_id END) as completed_chapters
        FROM courses c
        JOIN chapters ch ON c.id = ch.course_id
        LEFT JOIN user_progress up ON ch.id = up.chapter_id AND up.user_id = ?
        JOIN users u ON u.id = ?
        WHERE c.id = ?
        GROUP BY c.id, u.id
    ");
    $stmt->execute([$user_id, $user_id, $course_id]);
    $data = $stmt->fetch();
    
    if (!$data) {
        header('Location: ../student/dashboard.php?error=course_not_found');
        exit();
    }
    
    if ($data['completed_chapters'] < $data['total_chapters']) {
        header('Location: ../student/dashboard.php?error=certificate_not_available');
        exit();
    }
    
    // Vérifier si le certificat existe déjà
    $stmt = $pdo->prepare("
        SELECT certificate_url, certificate_code 
        FROM certificates 
        WHERE user_id = ? AND course_id = ?
    ");
    $stmt->execute([$user_id, $course_id]);
    $existing = $stmt->fetch();
    
    if ($existing && file_exists('../' . $existing['certificate_url'])) {
        // Mettre à jour le compteur de téléchargements
        $stmt = $pdo->prepare("
            UPDATE certificates 
            SET download_count = download_count + 1,
                last_downloaded = NOW()
            WHERE user_id = ? AND course_id = ?
        ");
        $stmt->execute([$user_id, $course_id]);
        
        // Rediriger vers le certificat existant
        header('Location: ../' . $existing['certificate_url']);
        exit();
    }
    
    // Générer un code unique pour le certificat
    $certificate_code = generateCertificateCode($pdo);
    
    // Générer le nom de fichier
    $certificate_id = uniqid('cert_', true);
    $filename = "certificates/{$certificate_id}.pdf";
    $full_path = "../{$filename}";
    
    // Créer le dossier certificates s'il n'existe pas
    if (!is_dir('../certificates')) {
        mkdir('../certificates', 0777, true);
    }
    
    // Créer le PDF
    $pdf = createCertificatePDF($data, $certificate_code);
    
    // Sauvegarder le PDF
    $pdf->Output('F', $full_path);
    
    // CORRECTION ICI : Colonnes corrigées pour correspondre à la table
    $stmt = $pdo->prepare("
        INSERT INTO certificates 
        (user_id, course_id, certificate_url, certificate_code, issued_at, download_count)
        VALUES (?, ?, ?, ?, NOW(), 1)
    ");
    $stmt->execute([$user_id, $course_id, $filename, $certificate_code]);
    
    // Rediriger vers le PDF
    header('Location: ../' . $filename);
    
} catch (Exception $e) {
    error_log("Certificate generation error: " . $e->getMessage());
    header('Location: ../student/dashboard.php?error=certificate_generation');
}

/**
 * Génère un code de certificat unique
 */
function generateCertificateCode($pdo) {
    $unique = false;
    $max_attempts = 10;
    $attempts = 0;
    
    while (!$unique && $attempts < $max_attempts) {
        $code = 'CERT-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 12));
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM certificates WHERE certificate_code = ?");
        $stmt->execute([$code]);
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            $unique = true;
        }
        
        $attempts++;
    }
    
    if (!$unique) {
        $code = 'CERT-' . uniqid();
    }
    
    return $code;
}

/**
 * Crée le PDF du certificat
 */
function createCertificatePDF($data, $certificate_code) {
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    
    // Couleurs
    $primary_color = [99, 102, 241];   // #6366F1
    $secondary_color = [236, 72, 153]; // #EC4899
    $dark_color = [31, 41, 55];        // #1F2937
    $gray_color = [107, 114, 128];     // #6B7280
    $light_gray = [243, 244, 246];     // #F3F4F6
    
    // Arrière-plan avec dégradé
    for ($i = 0; $i < 210; $i++) {
        $ratio = $i / 210;
        $r = (int)($primary_color[0] + ($secondary_color[0] - $primary_color[0]) * $ratio);
        $g = (int)($primary_color[1] + ($secondary_color[1] - $primary_color[1]) * $ratio);
        $b = (int)($primary_color[2] + ($secondary_color[2] - $primary_color[2]) * $ratio);
        
        $pdf->SetDrawColor($r, $g, $b);
        $pdf->Line(0, $i, 297, $i);
    }
    
    // Rectangle blanc semi-transparent
    $pdf->SetFillColor(255, 255, 255, 50);
    $pdf->Rect(15, 15, 267, 180, 'F');
    
    // Bordure décorative
    $pdf->SetDrawColor($primary_color[0], $primary_color[1], $primary_color[2]);
    $pdf->SetLineWidth(3);
    $pdf->Rect(10, 10, 277, 190);
    
    // Logo/En-tête
    $pdf->SetFont('Arial', 'B', 24);
    $pdf->SetTextColor($primary_color[0], $primary_color[1], $primary_color[2]);
    $pdf->Cell(0, 30, 'WaveTalk Édu', 0, 1, 'C');
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->SetTextColor($gray_color[0], $gray_color[1], $gray_color[2]);
    $pdf->Cell(0, 5, 'Mastercard Foundation EdTech Fellowship', 0, 1, 'C');
    
    // Titre principal
    $pdf->SetFont('Arial', 'B', 36);
    $pdf->SetTextColor($dark_color[0], $dark_color[1], $dark_color[2]);
    $pdf->Cell(0, 40, 'CERTIFICAT DE RÉUSSITE', 0, 1, 'C');
    
    // Sous-titre
    $pdf->SetFont('Arial', 'I', 18);
    $pdf->SetTextColor($gray_color[0], $gray_color[1], $gray_color[2]);
    $pdf->Cell(0, 15, 'Décerné à', 0, 1, 'C');
    
    // Nom de l'élève
    $pdf->SetFont('Arial', 'B', 28);
    $pdf->SetTextColor($primary_color[0], $primary_color[1], $primary_color[2]);
    $pdf->Cell(0, 25, htmlspecialchars($data['first_name'] . ' ' . $data['last_name']), 0, 1, 'C');
    
    // Description
    $pdf->SetFont('Arial', '', 16);
    $pdf->SetTextColor($gray_color[0], $gray_color[1], $gray_color[2]);
    $pdf->Cell(0, 15, 'pour avoir complété avec succès', 0, 1, 'C');
    $pdf->Cell(0, 15, 'le cours intitulé', 0, 1, 'C');
    
    // Titre du cours
    $pdf->SetFont('Arial', 'B', 24);
    $pdf->SetTextColor($secondary_color[0], $secondary_color[1], $secondary_color[2]);
    $pdf->Cell(0, 25, '"' . htmlspecialchars($data['course_title']) . '"', 0, 1, 'C');
    
    // Détails
    $pdf->SetFont('Arial', '', 14);
    $pdf->SetTextColor($dark_color[0], $dark_color[1], $dark_color[2]);
    
    $y = $pdf->GetY() + 15;
    $pdf->SetY($y);
    
    $pdf->Cell(0, 10, 'Niveau: ' . htmlspecialchars($data['grade_level']), 0, 1, 'C');
    $pdf->Cell(0, 10, "Date d'émission: " . date('d/m/Y'), 0, 1, 'C');
    
    // Code unique avec fond
    $pdf->SetY($pdf->GetY() + 20);
    $pdf->SetFillColor($light_gray[0], $light_gray[1], $light_gray[2]);
    $pdf->Rect(75, $pdf->GetY(), 147, 25, 'F');
    
    $pdf->SetFont('Courier', 'B', 12);
    $pdf->SetTextColor($dark_color[0], $dark_color[1], $dark_color[2]);
    $pdf->Cell(0, 10, 'Code de vérification: ' . $certificate_code, 0, 1, 'C');
    
    // URL de vérification
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor($gray_color[0], $gray_color[1], $gray_color[2]);
    $pdf->Cell(0, 10, 'Vérifiez ce certificat sur: wavetalk.edu/verify_certificate.php', 0, 1, 'C');
    
    // Pied de page
    $pdf->SetY(185);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor($gray_color[0], $gray_color[1], $gray_color[2]);
    $pdf->Cell(0, 10, 'WaveTalk Édu - Plateforme d\'apprentissage audio interactive', 0, 1, 'C');
    
    return $pdf;
}
?>
