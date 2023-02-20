<?php

return [
    // Which disk should be used to save the resizes?
    // And where are the non-external files stored?
    // See config/filesystems.php
    'disk' => env('RAPIDEZ_DISK', 'public'),

    'sizes' => [
        '80x80',   // Thumbs
        '400',     // Product
        '200',     // Category
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
];
