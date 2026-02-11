<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize & validate inputs
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    if (!$name || !$email || !$message) {
        echo "Veuillez remplir tous les champs correctement.";
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'webstudiomissy@gmail.com'; // Remplacez par votre email Gmail
        $mail->Password = ''; // Utilisez un App Password si Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Expéditeur et destinataire
        $mail->setFrom('webstudiomissy@gmail.com', 'WaveTalk');
        $mail->addReplyTo($email, $name);
        $mail->addAddress('webstudiomissy@gmail.com');

        // Contenu de l'email
        $mail->isHTML(true);
        $mail->Subject = "Nouveau message de $name via WaveTalk";
        $mail->Body = "
            <h3>Nouveau message reçu</h3>
            <p><strong>Nom :</strong> $name</p>
            <p><strong>Email :</strong> $email</p>
            <p><strong>Message :</strong><br>" . nl2br($message) . "</p>
        ";

        $mail->send();
        echo "Votre message a été envoyé avec succès.";
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo); // Ne jamais exposer publiquement l'erreur exacte
        echo "Une erreur s'est produite lors de l'envoi du message. Veuillez réessayer plus tard.";
    }
} else {
    http_response_code(405);
    echo "Méthode non autorisée.";
}
?>
