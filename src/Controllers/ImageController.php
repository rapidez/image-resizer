<?php

namespace Rapidez\ImageResizer\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rapidez\Core\Models\Config;
use Rapidez\ImageResizer\Exceptions\UnreachableUrl;
use Spatie\Image\Image;
use Spatie\Image\Manipulations;

class ImageController extends Controller
{
    protected $key;
    protected $loopCount = 0;
    protected $configKey = [
        'thumbnail',
        'small_image',
        'image'
    ];

    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, string $size, string $file, string $webp = '')
    {
        abort_unless(in_array($size, config('imageresizer.sizes')), 400, 'The requested size is not whitelisted.');

        foreach (config('imageresizer.external') as $placeholder => $url) {
            if (Str::startsWith($file, $placeholder)) {
                $file = str_replace($placeholder, '', $file);
                $placeholderUrl = $url;
                break;
            }
        }

        $resizedPath = 'resizes/'.$size.'/'.$file.$webp;
        if (!Storage::exists('public/'.$resizedPath)) {
            $remoteFile = isset($placeholderUrl)
                ? $placeholderUrl.$file
                : config('rapidez.media_url').'/'.$file;
            $temporaryFile = $this->saveTempFile($remoteFile);
            $image = Image::load($temporaryFile)->optimize();
            @list($width, $height) = explode('x', $size);

            if ($height) {
                $image->fit($request->has('crop') ? MANIPULATIONS::FIT_CROP : MANIPULATIONS::FIT_CONTAIN, $width, $height);
            } else {
                $image->width($width);
            }

            $image = isset($placeholderUrl) ? $image : $this->addWatermark($image, $width, $height ?? '400', $size);

            if (!is_dir(storage_path('app/public/'.pathinfo($resizedPath, PATHINFO_DIRNAME)))) {
                mkdir(storage_path('app/public/'.pathinfo($resizedPath, PATHINFO_DIRNAME)), 0755, true);
            }

            $image->save(storage_path('app/public/'.$resizedPath));
        }

        return response()->file(storage_path('app/public/'.$resizedPath));
    }

    public function addWaterMark(Image $image, string $width = '400', string $height = '400', string $size = '400'): Image
    {
        $this->key = $width <= 200 ? 0 : ($width > 200 && $width < 600 ? 1 : 2);
        $watermark = $this->getWaterMark($this->configKey[$this->key]);

        if (!$watermark) {
            return $image;
        }

        $position = Config::getCachedByPath('design/watermark/'.$watermark.'_position', 'center');
        $size = Config::getCachedByPath('design/watermark/'.$watermark.'_size', '100x100');
        $tempWatermark = $this->saveTempFile(config('rapidez.media_url').'/catalog/product/watermark/'.Config::getCachedByPath('design/watermark/'.$watermark.'_image'));

        $image->watermark($tempWatermark)
            ->watermarkOpacity(Config::getCachedByPath('design/watermark/'.$watermark.'_imageOpacity', 100))
            ->watermarkPosition(config('imageresizer.watermarks.positions.'.Config::getCachedByPath('design/watermark/'.$watermark.'_position', 'center')))
            ->watermarkHeight($height, Manipulations::UNIT_PIXELS)
            ->watermarkWidth($width, Manipulations::UNIT_PIXELS)
            ->watermarkHeight(explode('x', $size)[1])
            ->watermarkWidth(explode('x', $size)[0], Manipulations::UNIT_PIXELS);

        return $image;
    }

    public function getWaterMark($key)
    {
        $this->loopCount++;
        $watermark = Config::getCachedByPath('design/watermark/'.$key.'_image');
        if (empty($watermark) && $this->loopCount < 3) {
            return $this->getWaterMark($key < 2 ? $key++ : 0);
        }

        return  $this->loopCount < 3 ? $key : null;
    }

    public function saveTempFile($path)
    {
        if (!$stream = @fopen($path, 'r')) {
            throw UnreachableUrl::create($path);
        }

        $temp = tempnam(sys_get_temp_dir(), 'rapidez');
        file_put_contents($temp, $stream);

        return $temp;
    }
}
