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
        Route::get('storage/resizes/{size}/{file}{webp?}', ImageController::class)
            ->where(['file' => '.*\.((?!webp)[^\.])+', 'webp' => '\.webp']);

        $this->mergeConfigFrom(__DIR__.'/../config/imageresizer.php', 'imageresizer');

        $this->publishes([
            __DIR__.'/../config/imageresizer.php' => config_path('imageresizer.php'),
        ], 'config');

        $this->commands([
            RemoveResizesCommand::class,
        ]);
    }
}
