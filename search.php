<!-- filepath: /home/serena/Documents/WaveTalk/search.php -->
<?php
require 'db_connection.php';

$query = htmlspecialchars($_GET['query']); // Récupère le mot-clé depuis le formulaire

$sql = "SELECT * FROM podcasts WHERE title LIKE ? OR description LIKE ?";
$stmt = $conn->prepare($sql);
$searchTerm = "%" . $query . "%";
$stmt->bind_param('ss', $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats de recherche</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="bg-primary text-light text-center py-5">
        <h1 class="display-4">Résultats de recherche</h1>
        <p class="lead">Résultats pour "<?php echo $query; ?>"</p>
    </header>

    <div class="container mt-5">
        <div class="row">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="col-md-4 col-sm-6 mb-4">';
                    echo '<div class="card category-card text-center">';
                    echo '<div class="card-body">';
                    echo '<h5 class="card-title">' . $row['title'] . '</h5>';
                    echo '<p class="card-text">' . $row['description'] . '</p>';
                    echo '<iframe src="' . $row['file_path'] . '" width="100%" height="80" frameborder="0" allowtransparency="true" allow="encrypted-media"></iframe>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p class="text-center">Aucun podcast trouvé.</p>';
            }
            ?>
        </div>
    </div>
</body>
</html>