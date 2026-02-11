<?php


session_start();
require 'db_connection.php'; 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id']; // ID de l'utilisateur connecté
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

    // Validation des champs
    if (empty($username) || empty($email)) {
        die('Tous les champs obligatoires doivent être remplis.');
    }

    // Mise à jour des informations dans la base de données
    $query = "UPDATE users SET username = ?, email = ?" . ($password ? ", password = ?" : "") . " WHERE id = ?";
    $stmt = $conn->prepare($query);

    if ($password) {
        $stmt->bind_param('sssi', $username, $email, $password, $userId);
    } else {
        $stmt->bind_param('ssi', $username, $email, $userId);
    }

    if ($stmt->execute()) {
        echo "Votre compte a été mis à jour avec succès.";
    } else {
        echo "Erreur lors de la mise à jour : " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>