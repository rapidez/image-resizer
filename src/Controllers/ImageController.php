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
            $temporaryFile = $this->saveTempFile(config('rapidez.media_url').'/'.$file);

            $image = Image::load($temporaryFile)->optimize();
            @list($width, $height) = explode('x', $size);
            if ($height) {
                $image->fit($request->has('crop') ? MANIPULATIONS::FIT_CROP : MANIPULATIONS::FIT_CONTAIN, $width, $height);
            } else {
                $image->width($width);
            }

            $image = $this->addWatermark($image, $width, $height ?? '400', $size);

            if (!is_dir(storage_path('app/public/'.pathinfo($resizedPath, PATHINFO_DIRNAME)))) {
                mkdir(storage_path('app/public/'.pathinfo($resizedPath, PATHINFO_DIRNAME)), 0755, true);
            }

            $image->save(storage_path('app/public/'.$resizedPath));
        }

        return response()->file(storage_path('app/public/'.$resizedPath));
    }

    public function addWaterMark(Image $image, string $width = '400', string $height = '400', string $size = '400'): Image
    {
        $watermarkSize = Config::getCachedByPath('design/watermark/thumbnail_size');
        if ($watermarkSize == $size || explode('x', $watermarkSize)[0] == $size) {
            $watermark = Config::getCachedByPath('design/watermark/image_image');
            $opacity = Config::getCachedByPath('design/watermark/thumbnail_imageOpacity', 40);
            $position = Config::getCachedByPath('design/watermark/small_image_position', 'center');
            $tempWatermark = $this->saveTempFile(config('rapidez.media_url').'/catalog/product/watermark/'.$watermark);

            $image->watermark($tempWatermark)
                ->watermarkOpacity($opacity)
                ->watermarkPosition(config('imageresizer.watermarks.positions.'.$position))
                ->watermarkHeight($height / 2, Manipulations::UNIT_PIXELS)
                ->watermarkWidth($width / 2, Manipulations::UNIT_PIXELS);
        }

        return $image;
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
