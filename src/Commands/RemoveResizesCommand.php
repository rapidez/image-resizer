<?php

namespace Rapidez\ImageResizer\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;

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

        if(is_a($stores, 'Illuminate\Support\Collection')) {
            foreach($stores as $id => $store) {
                File::deleteDirectory(storage_path('app/public/'.$id.'/'));
            }
        } else {
            File::deleteDirectory(storage_path('app/public/'.$stores.'/'));
        }
        $this->info('Done!');
    }
}
