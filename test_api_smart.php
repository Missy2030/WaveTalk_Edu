<?php
// test_api_smart.php - Place dans public/
// Teste l'API avec de VRAIS IDs de ta base
header('Content-Type: text/html; charset=utf-8');

require_once '../private/db_connection.php';

// Récupérer un user réel
$stmt = $pdo->query("SELECT id, email FROM users LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer un cours réel
$stmt = $pdo->query("SELECT id, title FROM courses LIMIT 1");
$course = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test API Intelligent</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #1a1a1a;
            color: #00ff00;
        }
        button {
            background: #00ff00;
            color: black;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            margin: 10px 0;
            font-size: 16px;
        }
        .result {
            background: #2a2a2a;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #00ff00;
            white-space: pre-wrap;
        }
        .error {
            border-left-color: #ff0000;
            color: #ff0000;
        }
        .success {
            border-left-color: #00ff00;
        }
        .info {
            color: #ffff00;
        }
    </style>
</head>
<body>
    <h1>🔧 Test API avec VRAIS IDs</h1>
    
    <div class="info">
        <h3>📊 IDs trouvés dans la base:</h3>
        <p>👤 User ID: <strong><?= $user['id'] ?></strong> (<?= htmlspecialchars($user['email']) ?>)</p>
        <p>📚 Course ID: <strong><?= $course['id'] ?></strong> (<?= htmlspecialchars($course['title']) ?>)</p>
    </div>
    
    <button onclick="testAPI()">▶ Tester l'API avec ces IDs</button>
    <button onclick="clearResults()">🗑️ Effacer</button>
    
    <div id="results"></div>
    
    <script>
        const userId = <?= $user['id'] ?>;
        const courseId = <?= $course['id'] ?>;
        
        function addResult(message, type = 'info') {
            const div = document.createElement('div');
            div.className = 'result ' + type;
            div.textContent = message;
            document.getElementById('results').prepend(div);
        }
        
        function clearResults() {
            document.getElementById('results').innerHTML = '';
        }
        
        async function testAPI() {
            addResult('🔄 Test avec User ID: ' + userId + ', Course ID: ' + courseId, 'info');
            
            try {
                const response = await fetch('/public/api/track_progress.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        course_id: courseId,
                        listened_time: 30,
                        current_position: 15,
                        is_completed: false
                    })
                });
                
                addResult('📡 Status HTTP: ' + response.status, 
                    response.ok ? 'success' : 'error');
                
                const text = await response.text();
                addResult('📄 Réponse brute:\n' + text, 'info');
                
                try {
                    const json = JSON.parse(text);
                    addResult('✅ JSON valide:\n' + JSON.stringify(json, null, 2), 'success');
                    
                    if (json.success) {
                        addResult('🎉🎉🎉 API FONCTIONNE PARFAITEMENT ! 🎉🎉🎉', 'success');
                        addResult('La progression a été sauvegardée avec succès !', 'success');
                    } else {
                        addResult('⚠️ API répond mais erreur: ' + json.error, 'error');
                        addResult('DÉTAILS: ' + (json.details || 'Pas de détails'), 'error');
                    }
                } catch (e) {
                    addResult('❌ ERREUR: La réponse n\'est pas du JSON !', 'error');
                }
                
            } catch (error) {
                addResult('❌ ERREUR RÉSEAU: ' + error.message, 'error');
            }
        }
    </script>
    
    <hr>
    <h2>🔍 Vérification Base de Données</h2>
    <div class="info">
        <?php
        // Vérifier si une progression existe déjà
        $stmt = $pdo->prepare("SELECT * FROM user_progress WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$user['id'], $course['id']]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($progress) {
            echo "<p>✅ Une progression existe déjà pour ce user/cours:</p>";
            echo "<pre>" . print_r($progress, true) . "</pre>";
        } else {
            echo "<p>ℹ️ Aucune progression existante pour ce user/cours (c'est normal)</p>";
        }
        
        // Vérifier la structure de la table
        echo "<h3>🔧 Structure table user_progress:</h3>";
        $stmt = $pdo->query("DESCRIBE user_progress");
        echo "<pre>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " | " . $row['Type'] . " | " . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
        }
        echo "</pre>";
        ?>
    </div>
</body>
</html>