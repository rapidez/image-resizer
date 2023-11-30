<?php

namespace Rapidez\ImageResizer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class RemoveResizesCommand extends Command
{
    protected $signature = 'rapidez:resizes:delete {store? : Store ID from Magento}';

    protected $description = 'Delete the resized images.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $storeModel = config('rapidez.models.store');
        $stores = $this->argument('store') ? $this->argument('store') : $storeModel::all()->pluck('id');

        if ($stores instanceof Collection) {
            foreach ($stores as $id => $store) {
                $this->storage()->deleteDirectory($id.'/resizes');
            }
        } else {
            $this->storage()->deleteDirectory($id.'/resizes');
        }

        $this->info('Done!');
    }

    protected function storage()
    {
        return Storage::disk(config('rapidez.imageresizer.disk'));
    }
}
