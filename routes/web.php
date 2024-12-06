<?php

use Illuminate\Support\Facades\Route;
use Rapidez\ImageResizer\Controllers\ImageController;

const PATTERNS = [
    'placeholder' => '[^\/]+',
    'file'        => '.*\.((?!webp)[^\.])+',
    'webp'        => '\.webp',
];

if (config('rapidez.imageresizer.sku.enabled')) {
    Route::get('storage/{store}/resizes/{size}/sku/{file}', [ImageController::class, 'redirectFromSku'])
        ->name('resized-sku');
}

Route::get('storage/{store}/resizes/{size}/{placeholder}/{file}{webp?}', ImageController::class)
    ->where([
        'store' => '[0-9]*',
        ...PATTERNS,
    ])
    ->name('resized-image');

// Backwards compatibility step.
Route::get('storage/resizes/{size}/{placeholder}/{file}{webp?}', function (string $size, string $placeholder, string $file, string $webp = '') {
    return redirect(route('resized-image', ['store' => config('rapidez.store'), ...compact('size', 'placeholder', 'file', 'webp')]), 301);
})->where(PATTERNS);
