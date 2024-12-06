<?php

namespace Rapidez\ImageResizer\Listeners\Healthcheck;

use \Rapidez\Core\Listeners\Healthcheck\Base;
use Rapidez\Core\Facades\Rapidez;

class ImageGenerationHealthcheck extends Base
{
    public function handle()
    {
        $response = [
            'healthy'  => true,
            'messages' => [],
        ];

        $mediaUrlFormat = Rapidez::config('web/url/catalog_media_url_format', 'hash');
        if ($mediaUrlFormat !== 'image_optimization_parameters') {
            $response['messages'][] = [
                'type'  => 'info',
                'value' => __('Tip: setting the "Catalog media URL format" to "Image optimization based on query parameters" helps greatly reduce disk usage, and Rapidez works with that setting.'),
            ];
        }

        return $response;
    }
}
