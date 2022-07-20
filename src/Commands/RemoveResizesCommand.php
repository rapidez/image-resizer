<?php

namespace Rapidez\ImageResizer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

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
                File::deleteDirectory(storage_path('app/public/'.$id.'/'));
            }
        } else {
            File::deleteDirectory(storage_path('app/public/'.$stores.'/'));
        }
        $this->info('Done!');
    }
}
