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
        if (!Storage::exists('public/'.config('rapidez.store').'/'.$resizedPath)) {
            $remoteFile = isset($placeholderUrl)
                ? $placeholderUrl.$file
                : config('rapidez.media_url').'/'.$file;
            $temporaryFile = $this->saveTempFile($remoteFile);
            $image = Image::load($temporaryFile)->optimize();
            @list($width, $height) = explode('x', $size);

            // Don't upscale images.
            foreach (['width', 'height'] as $dimension) {
                if (${$dimension} > $image->{'get'.ucfirst($dimension)}()) {
                    ${$dimension} = $image->{'get'.ucfirst($dimension)}();
                }
            }

            if ($height) {
                $image->fit($request->has('crop') ? MANIPULATIONS::FIT_CROP : MANIPULATIONS::FIT_CONTAIN, $width, $height);
            } else {
                $image->width($width);
            }

            $image = isset($placeholderUrl) ? $image : $this->addWatermark($image, $width, $height ?? '400', $size);

            if (!is_dir(storage_path('app/public/'.config('rapidez.store').'/'.pathinfo($resizedPath, PATHINFO_DIRNAME)))) {
                mkdir(storage_path('app/public/'.config('rapidez.store').'/'.pathinfo($resizedPath, PATHINFO_DIRNAME)), 0755, true);
            }

            $image->save(storage_path('app/public/'.config('rapidez.store').'/'.$resizedPath));
        }

        return response()->file(storage_path('app/public/'.$resizedPath));
    }

    public function addWaterMark(Image $image, string $width = '400', string $height = '400', string $size = '400'): Image
    {
        $watermark = $width < 200 ? 'thumbnail' : ($width >= 200 && $width < 600 ? 'small_image' : 'image');
        $waterMarkImage = Config::getCachedByPath('design/watermark/'.$watermark.'_image');
        if (!$waterMarkImage) {
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
