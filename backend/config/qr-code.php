<?php

return [
    'format' => 'png',
    'size' => 200,
    'error_correction' => 'L',
    'encoding' => 'UTF-8',

    // 🔥 VERY IMPORTANT (fix your error)
    'image' => \SimpleSoftwareIO\QrCode\Image\GdImage::class,
];
