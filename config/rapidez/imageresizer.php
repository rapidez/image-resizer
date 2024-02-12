<?php

return [
    // Which disk should be used to save the resizes?
    // And where are the non-external files stored?
    // See config/filesystems.php
    'disk' => env('RAPIDEZ_DISK', 'public'),

    // Which driver should be used? Two options:
    // - gd
    // - imagick
    'driver' => env('RAPIDEZ_IMAGE_DRIVER', 'gd'),

    'sizes' => [
        '80x80',   // Thumbs
        '400',     // Product
        '200',     // Category
        '750',     // Homepage blocks
        '1500',    // Homepage blocks
    ],

    'watermarks' => [
        'positions' => [
            'stretch'      => Spatie\Image\Manipulations::FIT_STRETCH,
            'tile'         => Spatie\Image\Manipulations::FIT_CROP,
            'top-left'     => Spatie\Image\Manipulations::POSITION_TOP_LEFT,
            'top-right'    => Spatie\Image\Manipulations::POSITION_TOP_RIGHT,
            'bottom-left'  => Spatie\Image\Manipulations::POSITION_BOTTOM_LEFT,
            'bottom-right' => Spatie\Image\Manipulations::POSITION_BOTTOM_RIGHT,
            'center'       => Spatie\Image\Manipulations::POSITION_CENTER,
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
