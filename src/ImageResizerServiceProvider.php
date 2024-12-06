<?php

namespace Rapidez\ImageResizer;

use Illuminate\Support\ServiceProvider;
use Rapidez\ImageResizer\Commands\RemoveResizesCommand;
use Rapidez\ImageResizer\Controllers\ImageController;
use Rapidez\ImageResizer\Listeners\Healthcheck\ImageGenerationHealthcheck;

class ImageResizerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootConfig()
            ->bootRoutes()
            ->bootCommands()
            ->bootListeners();
    }

    public function bootConfig(): self
    {
        $this->mergeConfigFrom(__DIR__.'/../config/rapidez/imageresizer.php', 'rapidez.imageresizer');
        $this->publishes([
            __DIR__.'/../config/rapidez/imageresizer.php' => config_path('rapidez/imageresizer.php'),
        ], 'config');

        return $this;
    }

    public function bootRoutes(): self
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        return $this;
    }

    public function bootCommands(): self
    {
        $this->commands([
            RemoveResizesCommand::class,
        ]);

        return $this;
    }

    public function bootListeners(): self
    {
        ImageGenerationHealthcheck::register();

        return $this;
    }
}
