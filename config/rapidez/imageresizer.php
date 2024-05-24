<?php

return [
    // Which disk should be used to save the resizes?
    // And where are the non-external files stored?
    // See config/filesystems.php
    'disk' => env('RAPIDEZ_DISK', 'public'),

    // Which driver should be used? Two options:
    // - gd
    // - imagick
    'driver' => env('RAPIDEZ_IMAGE_DRIVER', extension_loaded('imagick') ? 'imagick' : 'gd'),

    'sizes' => [
        '80x80',   // Thumbs
        '400',     // Product
        '200',     // Category
        '750',     // Homepage blocks
        '1500',    // Homepage blocks
    ],

    'watermarks' => [
        'positions' => [
            'stretch'      => \Spatie\Image\Enums\Fit::Stretch,
            'tile'         => \Spatie\Image\Enums\Fit::Crop,
            'top-left'     => \Spatie\Image\Enums\AlignPosition::TopLeft,
            'top-right'    => \Spatie\Image\Enums\AlignPosition::TopRight,
            'bottom-left'  => \Spatie\Image\Enums\AlignPosition::BottomLeft,
            'bottom-right' => \Spatie\Image\Enums\AlignPosition::BottomRight,
            'center'       => \Spatie\Image\Enums\AlignPosition::Center,
        ],
    ],

    'external' => [
        'magento' => env('MEDIA_URL', env('MAGENTO_URL').'/media'),
        // 'source-placeholder' => 'https://external-source.com',
    ],

    // Enable image URL's by SKU like:
    // /storage/1/resizes/80x70/SKU
    'sku' => [
        'enabled'  => true,
        'redirect' => [
            'status_code' => 302,
            'max_age'     => 86400, // One day
        ],
    ],
];
