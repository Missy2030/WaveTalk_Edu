<!-- filepath: /home/serena/Documents/WaveTalk/add_favorite.php -->
<?php
session_start();
require 'db_connection.php';

$userId = $_SESSION['user_id']; // Récupère l'ID de l'utilisateur connecté
$podcastId = htmlspecialchars($_POST['podcast_id']);

$query = "INSERT INTO favorites (user_id, podcast_id) VALUES (?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $userId, $podcastId);

if ($stmt->execute()) {
    echo "Podcast ajouté aux favoris.";
} else {
    echo "Erreur : " . $stmt->error;
}

$stmt->close();
$conn->close();
?>