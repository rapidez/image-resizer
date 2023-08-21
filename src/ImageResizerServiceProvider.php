<?php

namespace Rapidez\ImageResizer;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Rapidez\ImageResizer\Commands\RemoveResizesCommand;
use Rapidez\ImageResizer\Controllers\ImageController;

class ImageResizerServiceProvider extends ServiceProvider
{
    const PATTERNS = [
        'placeholder' => '[^\/]+',
        'file'        => '.*\.((?!webp)[^\.])+',
        'webp'        => '\.webp',
    ];

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/imageresizer.php', 'imageresizer');

        if (config('imageresizer.sku.enabled')) {
            Route::get('storage/{store}/resizes/{size}/sku/{file}', [ImageController::class, 'redirectFromSku'])
                ->name('resized-sku');
        }

        Route::get('storage/{store}/resizes/{size}/{placeholder}/{file}{webp?}', ImageController::class)
            ->where([
                'store' => '[0-9]*',
                ...self::PATTERNS,
            ])
            ->name('resized-image');

        // Backwards compatibility step.
        Route::get('storage/resizes/{size}/{placeholder}/{file}{webp?}', function (string $size, string $placeholder, string $file, string $webp = '') {
            return redirect(route('resized-image', ['store' => config('rapidez.store'), ...compact('size', 'placeholder', 'file', 'webp')]), 301);
        })->where(self::PATTERNS);

        $this->publishes([
            __DIR__.'/../config/imageresizer.php' => config_path('imageresizer.php'),
        ], 'config');

        $this->commands([
            RemoveResizesCommand::class,
        ]);
    }
}
