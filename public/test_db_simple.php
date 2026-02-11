<?php
// test_db_simple.php - Place dans public/
// Visite: http://localhost:8000/public/test_db_simple.php

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Base de Données</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f0f0f0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            margin: 10px 0;
            border-left: 5px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            margin: 10px 0;
            border-left: 5px solid #dc3545;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            margin: 10px 0;
            border-left: 5px solid #17a2b8;
        }
    </style>
</head>
<body>
    <h1>🔧 Test Connexion Base de Données</h1>
    
    <?php
    try {
        echo "<div class='info'>📡 Tentative de connexion...</div>";
        
        require_once '../private/db_connection.php';
        
        echo "<div class='success'>✅ Connexion à la base de données réussie !</div>";
        
        // Test table users
        echo "<div class='info'>🔍 Test table 'users'...</div>";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $result = $stmt->fetch();
        echo "<div class='success'>✅ Table 'users' : " . $result['total'] . " utilisateur(s)</div>";
        
        // Test table courses
        echo "<div class='info'>🔍 Test table 'courses'...</div>";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM courses");
        $result = $stmt->fetch();
        echo "<div class='success'>✅ Table 'courses' : " . $result['total'] . " cours</div>";
        
        // Test table user_progress
        echo "<div class='info'>🔍 Test table 'user_progress'...</div>";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM user_progress");
        $result = $stmt->fetch();
        echo "<div class='success'>✅ Table 'user_progress' : " . $result['total'] . " progression(s)</div>";
        
        // Test INSERT
        echo "<div class='info'>🔍 Test d'écriture (INSERT)...</div>";
        $stmt = $pdo->prepare("
            INSERT INTO user_progress 
            (user_id, course_id, listened_time, last_position, created_at, updated_at)
            VALUES (1, 1, 10, 5, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
            listened_time = VALUES(listened_time),
            updated_at = NOW()
        ");
        $stmt->execute();
        echo "<div class='success'>✅ Écriture dans la base OK !</div>";
        
        echo "<hr>";
        echo "<div class='success'><h2>🎉 TOUT FONCTIONNE !</h2>
        <p>La base de données est opérationnelle.</p>
        <p>Si l'API ne marche pas, le problème vient de track_progress.php, pas de la BDD.</p>
        </div>";
        
    } catch (PDOException $e) {
        echo "<div class='error'>❌ <strong>ERREUR DE CONNEXION</strong><br>";
        echo "Message: " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "<br><strong>Solutions possibles:</strong><br>";
        echo "1. Vérifie que MySQL/MariaDB est démarré<br>";
        echo "2. Vérifie les identifiants dans private/db_connection.php<br>";
        echo "3. Vérifie que la base de données existe<br>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ <strong>ERREUR INATTENDUE</strong><br>";
        echo "Message: " . htmlspecialchars($e->getMessage());
        echo "</div>";
    }
    ?>
    
    <hr>
    <p><a href="test_api.php">→ Tester l'API track_progress</a></p>
    <p><a href="/public/">→ Retour à l'accueil</a></p>
</body>
</html>