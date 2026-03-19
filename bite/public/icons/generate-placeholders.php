<?php

/**
 * Generate placeholder PWA icon PNGs.
 * Run once: php public/icons/generate-placeholders.php
 * For production, use generate-icons.sh with the SVG source.
 */
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$bgColor = [0xF7, 0xF6, 0xF1];
$fgColor = [0xEC, 0x6D, 0x2E];

foreach ($sizes as $size) {
    $img = imagecreatetruecolor($size, $size);
    $bg = imagecolorallocate($img, $bgColor[0], $bgColor[1], $bgColor[2]);
    $fg = imagecolorallocate($img, $fgColor[0], $fgColor[1], $fgColor[2]);
    imagefilledrectangle($img, 0, 0, $size - 1, $size - 1, $bg);

    $bLeft = (int) ($size * 0.25);
    $bRight = (int) ($size * 0.75);
    $bTop = (int) ($size * 0.15);
    $bBottom = (int) ($size * 0.85);
    $bMid = (int) ($size * 0.5);
    $thickness = (int) ($size * 0.12);
    $radius = (int) ($size * 0.15);

    imagefilledrectangle($img, $bLeft, $bTop, $bLeft + $thickness, $bBottom, $fg);
    imagefilledrectangle($img, $bLeft, $bTop, $bRight - $radius, $bTop + $thickness, $fg);
    imagefilledrectangle($img, $bLeft, $bMid - (int) ($thickness / 2), $bRight - $radius, $bMid + (int) ($thickness / 2), $fg);
    imagefilledrectangle($img, $bLeft, $bBottom - $thickness, $bRight - $radius, $bBottom, $fg);
    imagefilledarc($img, $bRight - $radius, (int) (($bTop + $bMid) / 2), $radius * 2, (int) ($bMid - $bTop), -90, 90, $fg, IMG_ARC_PIE);
    imagefilledarc($img, $bRight - $radius, (int) (($bMid + $bBottom) / 2), $radius * 2, (int) ($bBottom - $bMid), -90, 90, $fg, IMG_ARC_PIE);

    $filename = __DIR__."/icon-{$size}x{$size}.png";
    imagepng($img, $filename);
    imagedestroy($img);
    echo "Created icon-{$size}x{$size}.png\n";
}
echo "Done! Placeholder icons generated.\n";
