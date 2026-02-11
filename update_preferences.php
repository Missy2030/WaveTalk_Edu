<?php


session_start();
require 'db_connection.php'; 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id']; // ID de l'utilisateur connecté
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;
    $notifications = isset($_POST['notifications']) ? 1 : 0;

    // Mise à jour des préférences dans la base de données
    $query = "UPDATE users SET newsletter = ?, notifications = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iii', $newsletter, $notifications, $userId);

    if ($stmt->execute()) {
        echo "Vos préférences ont été mises à jour avec succès.";
    } else {
        echo "Erreur lors de la mise à jour : " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>




      
        <!-- Bouton burger déplacé plus à gauche -->
        <button class="navbar-toggler position-relative" 
    style="right: -75px;"  
    type="button" 
    data-bs-toggle="collapse" 
    data-bs-target="#navbarNav" 
    aria-controls="navbarNav" 
    aria-expanded="false" 
    aria-label="Toggle navigation">
<span class="navbar-toggler-icon"></span>
</button>
      </div>