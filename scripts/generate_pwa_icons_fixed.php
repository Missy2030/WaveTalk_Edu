<?php
// scripts/generate_pwa_icons_fixed.php
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$output_dir = __DIR__ . '/../public/assets/icons/';

if (!is_dir($output_dir)) {
    mkdir($output_dir, 0777, true);
}

foreach ($sizes as $size) {
    $image = imagecreatetruecolor($size, $size);
    
    // Fond violet-rose
    $color1 = imagecolorallocate($image, 99, 102, 241);   // #6366F1
    $color2 = imagecolorallocate($image, 236, 72, 153);   // #EC4899
    
    for ($i = 0; $i < $size; $i++) {
        $ratio = $i / $size;
        $r = 99 + (236 - 99) * $ratio;
        $g = 102 + (72 - 102) * $ratio;
        $b = 241 + (153 - 241) * $ratio;
        $color = imagecolorallocate($image, $r, $g, $b);
        imageline($image, 0, $i, $size, $i, $color);
    }
    
    // Sauvegarder
    imagepng($image, $output_dir . "icon-{$size}x{$size}.png");
    imagedestroy($image);
    echo "Icône {$size}x{$size} générée\n";
}
?>