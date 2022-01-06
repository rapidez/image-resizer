<?php

return [
    'sizes' => [
        '80x80',   // Thumbs
        '400',     // Product
        '200',     // Category
    ],

    'watermarks' => [
        'positions' => [
            'stretch' => Spatie\Image\Manipulations::FIT_STRETCH,
            'tile' => Spatie\Image\Manipulations::FIT_CROP,
            'top-left' => Spatie\Image\Manipulations::POSITION_TOP_LEFT,
            'top-right' => Spatie\Image\Manipulations::POSITION_TOP_RIGHT,
            'bottom-left' => Spatie\Image\Manipulations::POSITION_BOTTOM_LEFT,
            'bottom-right' => Spatie\Image\Manipulations::POSITION_BOTTOM_RIGHT,
            'center' => Spatie\Image\Manipulations::POSITION_CENTER
        ]
    ],

    'external' => [
        // 'source-placeholder' => 'https://external-source.com',
    ],
];
