<?php
/**
 * LISTE TOUS LES FICHIERS AUDIO RÉELS
 * ====================================
 * Ce script affiche simplement la liste de TOUS les fichiers
 * qui existent réellement dans /public/audio/courses/
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>📁 Liste des fichiers audio</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            margin: 0;
        }
        h1 { color: #4ec9b0; }
        .file { 
            background: #2d2d2d; 
            padding: 10px; 
            margin: 5px 0; 
            border-radius: 5px;
            border-left: 3px solid #4ec9b0;
        }
        .count { color: #ce9178; font-weight: bold; }
        .path { color: #9cdcfe; }
        code { background: #3c3c3c; padding: 2px 6px; border-radius: 3px; color: #ce9178; }
    </style>
</head>
<body>
    <h1>📁 Liste des fichiers audio réels dans /public/audio/courses/</h1>

<?php
$audioDir = __DIR__ . '/public/audio/courses/';

if (!is_dir($audioDir)) {
    echo '<p style="color: #f48771;">❌ Le dossier n\'existe pas : ' . $audioDir . '</p>';
    exit;
}

$files = scandir($audioDir);
$audioFiles = [];

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (in_array($ext, ['mp3', 'm4a', 'wav', 'ogg'])) {
        $audioFiles[] = $file;
    }
}

echo '<p class="count">✅ ' . count($audioFiles) . ' fichiers audio trouvés</p>';
echo '<p class="path">📂 Chemin : <code>' . $audioDir . '</code></p>';

echo '<hr style="border-color: #3c3c3c; margin: 20px 0;">';

if (count($audioFiles) === 0) {
    echo '<p style="color: #f48771;">❌ Aucun fichier audio trouvé !</p>';
} else {
    foreach ($audioFiles as $index => $file) {
        $filesize = filesize($audioDir . $file);
        $sizeKB = round($filesize / 1024);
        $sizeMB = round($filesize / 1024 / 1024, 2);
        
        echo '<div class="file">';
        echo '<strong>' . ($index + 1) . '.</strong> ';
        echo '<span style="color: #dcdcaa;">' . htmlspecialchars($file) . '</span>';
        echo ' <span style="color: #858585;">(' . $sizeMB . ' MB)</span>';
        echo '</div>';
    }
}

// Générer le script SQL automatiquement
echo '<hr style="border-color: #3c3c3c; margin: 20px 0;">';
echo '<h2 style="color: #4ec9b0;">🔧 Script SQL généré automatiquement</h2>';
echo '<p>Copiez-collez ce script dans phpMyAdmin pour lier automatiquement vos fichiers aux cours :</p>';

echo '<textarea style="width: 100%; height: 400px; background: #1e1e1e; color: #d4d4d4; border: 1px solid #3c3c3c; padding: 10px; font-family: monospace; font-size: 12px;">';
echo "-- CORRECTION AUTOMATIQUE DES CHEMINS AUDIO\n";
echo "-- Généré le : " . date('Y-m-d H:i:s') . "\n";
echo "-- Nombre de fichiers : " . count($audioFiles) . "\n\n";

// Mapping intelligent
$mappings = [
    'plus-que-parfait' => 31,
    'COD.*COI' => 29,
    'COI.*COD' => 30,
    'relier.*phrases.*anglais' => 57,
    'Complément.*Circonstanciel' => 22,
    'CONJUGUER.*verbes' => 23,
    'adjectif.*fonctions' => 24,
    'pronoms.*personnels' => 25,
    'phrases.*simples.*complexes' => 26,
    'présent.*indicatif' => 27,
    'futur.*simple' => 28,
    'FUTUR.*ANTÉRIEUR' => 32,
    'Figures.*Style' => 33,
    'DIRECT.*INDIRECT' => 34,
    'schéma.*narratif' => 35,
    'analyse.*grammaticale' => 36,
    'Accords.*complexes' => 37,
    'contraction.*texte' => 38,
    'dissertation' => 39,
    'registres.*langue' => 40,
    'Introduction.*dissertation' => 41,
    'commentaire.*texte' => 42,
    'OS.*CORPS.*HUMAIN' => 43,
    'mouvements.*corporels' => 44,
    'respiration' => 45,
    'coeur.*organes.*Coeur' => 46,
    'sang.*6.*minutes' => 47,
    'Reproduction.*plantes' => 48,
    'reproduction.*êtres.*vivants' => 49,
    'système.*nerveux' => 50,
    'NEURONES' => 51,
    'enzymes' => 52,
    'cellule.*6.*minutes' => 53,
    'parallélogrammes' => 54,
    'triangles' => 55,
    'Fractions.*décimales' => 56,
    'Work.*Idioms' => 58,
    'Poser.*Questions.*Anglais' => 59,
    'Telling.*time' => 60,
];

foreach ($audioFiles as $file) {
    foreach ($mappings as $pattern => $courseId) {
        if (preg_match('/' . $pattern . '/i', $file)) {
            $path = '/audio/courses/' . $file;
            echo "-- Cours ID $courseId\n";
            echo "UPDATE courses SET audio_url = '$path' WHERE id = $courseId;\n";
            echo "UPDATE chapters SET audio_url = '$path' WHERE course_id = $courseId;\n\n";
            break;
        }
    }
}

echo "\n-- Activer le téléchargement pour tous\n";
echo "UPDATE courses SET is_downloadable = 1 WHERE id BETWEEN 22 AND 60;\n";

echo '</textarea>';

echo '<p style="margin-top: 20px;"><a href="/public/" style="color: #4ec9b0;">🏠 Retour au site</a></p>';
?>

</body>
</html>