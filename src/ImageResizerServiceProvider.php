<?php

namespace Rapidez\ImageResizer;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Rapidez\ImageResizer\Commands\RemoveResizesCommand;
use Rapidez\ImageResizer\Controllers\ImageController;

class ImageResizerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('imageresizer.allow_sku')) {
            Route::get('storage/resizes/{size}/sku/{sku}', [ImageController::class, 'redirectFromSku'])
                ->name('resized-sku');
        }

        Route::get('storage/resizes/{size}/{file}{webp?}', ImageController::class)
            ->where(['file' => '.*\.((?!webp)[^\.])+', 'webp' => '\.webp'])
            ->name('resized-image');

        $this->mergeConfigFrom(__DIR__.'/../config/imageresizer.php', 'imageresizer');

        $this->publishes([
            __DIR__.'/../config/imageresizer.php' => config_path('imageresizer.php'),
        ], 'config');

        $this->commands([
            RemoveResizesCommand::class,
        ]);
    }
}
