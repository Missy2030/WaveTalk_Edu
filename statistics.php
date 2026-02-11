<?php
require 'db_connection.php';
session_start();

if (isset($_GET['podcast_id'])) {
    $podcast_id = htmlspecialchars($_GET['podcast_id']); // Sanitize input
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    $stmt = $conn->prepare("INSERT INTO statistics (podcast_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $podcast_id, $user_id);

    if ($stmt->execute()) {
        echo "Statistique enregistrée.";
    } else {
        echo "Erreur : " . $stmt->error;
    }
}
?>