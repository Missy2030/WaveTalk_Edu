<?php
// test_api.php - Place dans public/ et visite: http://localhost:8000/public/test_api.php
// Teste si l'API track_progress fonctionne

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test API Progression</title>
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
    </style>
</head>
<body>
    <h1>🔧 Test API track_progress.php</h1>
    
    <button onclick="testAPI()">▶ Tester l'API</button>
    <button onclick="testDB()">▶ Tester la BDD</button>
    <button onclick="clearResults()">🗑️ Effacer</button>
    
    <div id="results"></div>
    
    <script>
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
            addResult('🔄 Test de l\'API en cours...', 'info');
            
            try {
                const response = await fetch('/public/api/track_progress.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: 1,
                        course_id: 1,
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
                        addResult('🎉 API FONCTIONNE !', 'success');
                    } else {
                        addResult('⚠️ API répond mais erreur: ' + json.error, 'error');
                    }
                } catch (e) {
                    addResult('❌ ERREUR: La réponse n\'est pas du JSON !\n' + 
                        'C\'est probablement une erreur PHP.', 'error');
                }
                
            } catch (error) {
                addResult('❌ ERREUR RÉSEAU: ' + error.message, 'error');
            }
        }
        
        async function testDB() {
            addResult('🔄 Test de la base de données...', 'info');
            
            try {
                const response = await fetch('/public/test_db_simple.php');
                const text = await response.text();
                
                addResult('📄 Réponse test BDD:\n' + text, 
                    text.includes('✅') ? 'success' : 'error');
                
            } catch (error) {
                addResult('❌ ERREUR: ' + error.message, 'error');
            }
        }
    </script>
    
    <hr>
    <h2>📋 Interprétation des Résultats</h2>
    <div class="result">
<strong>SI tu vois "API FONCTIONNE" :</strong>
→ ✅ L'API marche, le problème vient de course.php

<strong>SI tu vois "La réponse n'est pas du JSON" :</strong>
→ ❌ track_progress.php a une erreur PHP
→ Vérifie db_connection.php

<strong>SI tu vois "ERREUR RÉSEAU" :</strong>
→ ❌ Le chemin est incorrect ou le fichier n'existe pas
→ Vérifie que track_progress.php existe dans public/api/

<strong>SI Status HTTP = 500 :</strong>
→ ❌ Erreur serveur PHP
→ Regarde les logs du terminal où tourne php -S

<strong>SI Status HTTP = 404 :</strong>
→ ❌ Fichier introuvable
→ Chemin incorrect
    </div>
</body>
</html>