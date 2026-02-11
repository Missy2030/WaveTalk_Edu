<?php
// create_simple_icons.php
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$dir = __DIR__ . '/../public/assets/icons/';

if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

foreach ($sizes as $size) {
    $image = imagecreatetruecolor($size, $size);
    
    // Fond violet (#6366F1)
    $color = imagecolorallocate($image, 99, 102, 241);
    imagefilledrectangle($image, 0, 0, $size, $size, $color);
    
    // Onde sonore simple (lignes blanches)
    $white = imagecolorallocate($image, 255, 255, 255);
    
    // Dessiner 3 lignes courbes
    for ($i = 1; $i <= 3; $i++) {
        $y = $size / 2 + sin($i * 0.5) * ($size / 8);
        $thickness = max(2, $size / 30);
        
        for ($t = 0; $t < $thickness; $t++) {
            imageline($image, 
                $size/4, 
                $y + $t, 
                $size * 3/4, 
                $y + $t, 
                $white);
        }
    }
    
    // Ajouter "WT" pour les grandes tailles
    if ($size >= 192) {
        $text = "WT";
        $fontsize = $size / 6;
        
        // Positionner le texte
        $bbox = imagettfbbox($fontsize, 0, 5, $text);
        $textwidth = $bbox[2] - $bbox[0];
        $textheight = $bbox[1] - $bbox[7];
        
        $x = ($size - $textwidth) / 2;
        $y = ($size + $textheight) / 2;
        
        imagettftext($image, $fontsize, 0, $x, $y, $white, 5, $text);
    }
    
    imagepng($image, $dir . "icon-{$size}x{$size}.png");
    imagedestroy($image);
    echo "✅ Icône {$size}x{$size} créée\n";
}

echo "🎉 Toutes les icônes ont été créées dans $dir\n";
?>