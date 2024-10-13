<?php
header('Content-Type: image/png');

$im = imagecreatetruecolor(100, 100);
$bgColor = imagecolorallocate($im, 255, 255, 255);
imagefilledrectangle($im, 0, 0, 100, 100, $bgColor);

$textColor = imagecolorallocate($im, 0, 0, 0);
imagestring($im, 5, 10, 10, 'Test', $textColor);

imagepng($im);
imagedestroy($im);
