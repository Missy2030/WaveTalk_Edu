<?php
/**
 * Script de diagnostic pour identifier le problème des chemins audio
 * À placer dans le dossier /public/ et exécuter via le navigateur
 */

require_once '../private/db_connection.php';

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Diagnostic Audio - WaveTalk Edu</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
<h1>🔍 Diagnostic Audio - WaveTalk Edu</h1>";

// 1. Vérifier la connexion à la base de données
echo "<div class='section'>";
echo "<h2>1. Connexion à la base de données</h2>";
try {
    $test = $pdo->query("SELECT 1")->fetch();
    echo "<p class='ok'>✓ Connexion réussie</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Erreur de connexion: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 2. Vérifier les cours dans la base de données
echo "<div class='section'>";
echo "<h2>2. Analyse des cours (5 premiers)</h2>";
try {
    $stmt = $pdo->query("SELECT id, title, audio_file, audio_url FROM courses LIMIT 5");
    $courses = $stmt->fetchAll();
    
    echo "<p>Nombre de cours trouvés: <strong>" . count($courses) . "</strong></p>";
    
    if (count($courses) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Titre</th><th>Chemin BDD (audio_file)</th><th>URL (audio_url)</th></tr>";
        foreach ($courses as $course) {
            echo "<tr>";
            echo "<td>" . $course['id'] . "</td>";
            echo "<td>" . htmlspecialchars(substr($course['title'], 0, 50)) . "...</td>";
            echo "<td><code>" . htmlspecialchars($course['audio_file'] ?? 'NULL') . "</code></td>";
            echo "<td>" . htmlspecialchars($course['audio_url'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Erreur: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 3. Vérifier le système de fichiers
echo "<div class='section'>";
echo "<h2>3. Vérification du système de fichiers</h2>";

$audio_dir = __DIR__ . '/audio/courses';
echo "<p>Dossier recherché: <code>$audio_dir</code></p>";

if (is_dir($audio_dir)) {
    echo "<p class='ok'>✓ Le dossier existe</p>";
    
    $files = scandir($audio_dir);
    $audio_files = array_filter($files, function($file) {
        return !is_dir($file) && preg_match('/\.(m4a|mp3|wav)$/i', $file);
    });
    
    echo "<p>Nombre de fichiers audio trouvés: <strong>" . count($audio_files) . "</strong></p>";
    
    if (count($audio_files) > 0) {
        echo "<p>Premiers fichiers:</p><ul>";
        $count = 0;
        foreach ($audio_files as $file) {
            if ($count >= 5) break;
            echo "<li><code>" . htmlspecialchars($file) . "</code></li>";
            $count++;
        }
        echo "</ul>";
    }
} else {
    echo "<p class='error'>✗ Le dossier n'existe pas</p>";
    echo "<p>Dossiers existants dans /public/ :</p><ul>";
    if (is_dir(__DIR__)) {
        $dirs = scandir(__DIR__);
        foreach ($dirs as $dir) {
            if ($dir != '.' && $dir != '..' && is_dir(__DIR__ . '/' . $dir)) {
                echo "<li>" . htmlspecialchars($dir) . "</li>";
            }
        }
    }
    echo "</ul>";
}
echo "</div>";

// 4. Test de génération de chemin
echo "<div class='section'>";
echo "<h2>4. Test de génération de chemins</h2>";

if (isset($courses) && count($courses) > 0) {
    $test_course = $courses[0];
    echo "<p>Test avec le premier cours: <strong>" . htmlspecialchars($test_course['title']) . "</strong></p>";
    
    $original_path = $test_course['audio_file'];
    echo "<p>Chemin original (BDD): <code>" . htmlspecialchars($original_path) . "</code></p>";
    
    // Fonction de correction
    function fix_audio_path($audio_file) {
        if (empty($audio_file)) return '';
        
        $file_path = $audio_file;
        
        // Nettoyer les chemins absolus
        $file_path = str_replace('/home/serena/Téléchargements/SERENA/Wavetalk_Edu/', '', $file_path);
        $file_path = str_replace('/home/serena/Téléchargements/SERENA/Wavetalk_Edu (2)/', '', $file_path);
        
        // S'assurer que le chemin commence correctement
        if (strpos($file_path, 'public/') === 0) {
            return '/' . $file_path;
        } elseif (strpos($file_path, 'audio/') === 0) {
            return '/public/' . $file_path;
        } elseif (strpos($file_path, '/') === 0) {
            return $file_path;
        } else {
            return '/public/' . $file_path;
        }
    }
    
    $fixed_path = fix_audio_path($original_path);
    echo "<p>Chemin corrigé: <code>" . htmlspecialchars($fixed_path) . "</code></p>";
    
    // Vérifier si le fichier existe
    $server_path = __DIR__ . $fixed_path;
    echo "<p>Chemin serveur: <code>" . htmlspecialchars($server_path) . "</code></p>";
    
    if (file_exists($server_path)) {
        echo "<p class='ok'>✓ Le fichier existe sur le serveur</p>";
        echo "<p>Taille: " . round(filesize($server_path) / 1024 / 1024, 2) . " MB</p>";
    } else {
        echo "<p class='error'>✗ Le fichier n'existe PAS sur le serveur</p>";
    }
    
    // URL de test
    $test_url = "http://" . $_SERVER['HTTP_HOST'] . $fixed_path;
    echo "<p>URL de test: <a href='" . htmlspecialchars($test_url) . "' target='_blank'>" . htmlspecialchars($test_url) . "</a></p>";
}
echo "</div>";

// 5. Recommandations
echo "<div class='section'>";
echo "<h2>5. Recommandations</h2>";
echo "<ol>";
echo "<li>Vérifier que tous les fichiers audio sont dans <code>/public/audio/courses/</code></li>";
echo "<li>Mettre à jour les chemins dans la base de données pour qu'ils pointent vers <code>public/audio/courses/nom_fichier.m4a</code></li>";
echo "<li>Appliquer le patch de correction du chemin dans <code>course.php</code></li>";
echo "<li>Tester avec un cours spécifique</li>";
echo "</ol>";

echo "<h3>Script SQL de correction (à exécuter si nécessaire):</h3>";
echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
echo "-- Afficher les chemins actuels\n";
echo "SELECT id, title, audio_file FROM courses WHERE audio_file IS NOT NULL LIMIT 10;\n\n";
echo "-- Corriger les chemins qui commencent par 'audio/courses/'\n";
echo "UPDATE courses \n";
echo "SET audio_file = CONCAT('public/', audio_file)\n";
echo "WHERE audio_file LIKE 'audio/courses/%';\n\n";
echo "-- Corriger les chemins absolus\n";
echo "UPDATE courses \n";
echo "SET audio_file = REPLACE(audio_file, '/home/serena/Téléchargements/SERENA/Wavetalk_Edu/', '')\n";
echo "WHERE audio_file LIKE '/home/%';\n";
echo "</pre>";
echo "</div>";

echo "<div class='section'>";
echo "<p style='text-align: center; color: #666;'>Script de diagnostic terminé - " . date('Y-m-d H:i:s') . "</p>";
echo "</div>";

echo "</body></html>";
?>