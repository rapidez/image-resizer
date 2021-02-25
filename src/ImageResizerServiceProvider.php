<?php

namespace Rapidez\ImageResizer;

use Rapidez\ImageResizer\Controllers\ImageController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ImageResizerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Route::get('storage/resizes/{size}/{file}', ImageController::class)->where('file', '.*');

        $this->mergeConfigFrom(__DIR__.'/config/imageresizer.php', 'imageresizer');

        $this->publishes([
            __DIR__.'/config/imageresizer.php' => config_path('imageresizer.php'),
        ], 'config');
    }
}
