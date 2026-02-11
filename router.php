<?php
// router.php - VERSION AMÉLIORÉE avec redirection automatique
// Place à la RACINE du projet, démarre avec: php -S localhost:8000 router.php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// REDIRECTION AUTOMATIQUE: / → /public/
if ($uri === '/' || $uri === '') {
    header('Location: /public/');
    exit;
}

// Si on accède à /register.php, /login.php, etc. → rediriger vers /public/
$publicPages = ['login.php', 'register.php', 'index.php', 'category.php', 'course.php', 'badges.php'];
foreach ($publicPages as $page) {
    if ($uri === '/' . $page) {
        header('Location: /public/' . $page);
        exit;
    }
}

// MIME types pour tous les fichiers
$extension = strtolower(pathinfo($uri, PATHINFO_EXTENSION));

$mimeTypes = [
    // Audio
    'm4a' => 'audio/mp4',
    'mp3' => 'audio/mpeg',
    'mp4' => 'audio/mp4',
    'wav' => 'audio/wav',
    'ogg' => 'audio/ogg',
    'webm' => 'audio/webm',
    'aac' => 'audio/aac',
    
    // Styles et scripts
    'css' => 'text/css',
    'js' => 'application/javascript',
    'json' => 'application/json',
    
    // Images
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'svg' => 'image/svg+xml',
    'webp' => 'image/webp',
    'ico' => 'image/x-icon',
    
    // Fonts
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf' => 'font/ttf',
    'eot' => 'application/vnd.ms-fontobject',
];

// Servir les fichiers avec le bon MIME type
if (isset($mimeTypes[$extension])) {
    $filePath = __DIR__ . $uri;
    
    // Si le fichier n'existe pas à la racine, chercher dans public/
    if (!file_exists($filePath)) {
        $filePath = __DIR__ . '/public' . $uri;
    }
    
    if (file_exists($filePath) && is_file($filePath)) {
        // Headers CORS pour ngrok
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        // Content-Type correct
        header('Content-Type: ' . $mimeTypes[$extension]);
        
        // Support du streaming audio (Range requests)
        header('Accept-Ranges: bytes');
        
        $fileSize = filesize($filePath);
        
        // Gestion des Range requests (pour skip/seek dans l'audio)
        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
            $range = str_replace('bytes=', '', $range);
            $parts = explode('-', $range);
            $start = intval($parts[0]);
            $end = isset($parts[1]) && $parts[1] !== '' ? intval($parts[1]) : $fileSize - 1;
            
            // Assurer que les valeurs sont dans les limites
            $start = max(0, min($start, $fileSize - 1));
            $end = max($start, min($end, $fileSize - 1));
            $length = $end - $start + 1;
            
            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes $start-$end/$fileSize");
            header("Content-Length: $length");
            
            $fp = fopen($filePath, 'rb');
            fseek($fp, $start);
            $remaining = $length;
            while ($remaining > 0 && !feof($fp)) {
                $chunk = min(8192, $remaining);
                echo fread($fp, $chunk);
                $remaining -= $chunk;
                flush();
            }
            fclose($fp);
        } else {
            // Requête normale sans Range
            header('Content-Length: ' . $fileSize);
            readfile($filePath);
        }
        exit;
    }
}

// Pour les fichiers PHP et autres dans public/
if (file_exists(__DIR__ . $uri)) {
    return false; // Laisser PHP gérer
}

if (file_exists(__DIR__ . '/public' . $uri)) {
    // Changer le répertoire de travail vers public/
    chdir(__DIR__ . '/public');
    
    // Pour les fichiers PHP
    if ($extension === 'php') {
        require __DIR__ . '/public' . $uri;
        exit;
    }
    
    // Pour les autres fichiers
    return false;
}

// Si rien ne correspond, 404
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page non trouvée</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .container {
            text-align: center;
        }
        h1 {
            font-size: 6rem;
            margin: 0;
        }
        p {
            font-size: 1.5rem;
            margin: 20px 0;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            padding: 15px 30px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
        }
        a:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <p>Page non trouvée</p>
        <p style="font-size: 1rem; opacity: 0.8;">URL demandée: <?php echo htmlspecialchars($uri); ?></p>
        <a href="/public/">← Retour à l'accueil</a>
    </div>
</body>
</html>