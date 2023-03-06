<?php

namespace Rapidez\ImageResizer\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rapidez\Core\Models\Config;
use Spatie\Image\Image;
use Spatie\Image\Manipulations;

class ImageController extends Controller
{
    protected array $tmpPaths = [];

    public function __invoke(Request $request, string $size, string $file, string $webp = '', string|bool $customFilename = false)
    {
        abort_unless(in_array($size, config('imageresizer.sizes')), 400, 'The requested size is not whitelisted.');

        foreach (config('imageresizer.external') as $placeholder => $url) {
            if (Str::startsWith($file, $placeholder)) {
                $file = Str::replaceFirst($placeholder, '', $file);
                $placeholderUrl = $url;
                break;
            }
        }

        $placeholder = isset($placeholderUrl)
            ? $placeholder
            : 'local';

        $resizedPath = config('rapidez.store').'/resizes/'.$placeholder.'/'.$size.'/'.($customFilename ?? $file).$webp;

        if (!$this->storage()->exists($resizedPath)) {
            $content = isset($placeholderUrl)
                ? $this->download($placeholderUrl.$file)
                : $this->storage()->get(config('rapidez.store').'/'.$file);

            abort_unless($content, 404);

            $tempFile = $this->createTempFile($content);
            $image = Image::load($tempFile)->optimize();
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

            $image = $placeholder == 'magento'
                ? $this->addWatermark($image, $width, $height ?? '400', $size)
                : $image;

            $image->save();

            $this->storage()->put($resizedPath, file_get_contents($tempFile));
        }

        return $this->storage()->response($resizedPath);
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

        @list($height, $width) = explode('x', $size);
        $watermarkImage = $this->download(config('rapidez.media_url').'/catalog/product/watermark/'.$watermarkImage);
        $tempWatermark = $this->createTempFile($watermarkImage);

        $image->watermark($tempWatermark)
            ->watermarkOpacity(Config::getCachedByPath('design/watermark/'.$watermark.'_imageOpacity', 100))
            ->watermarkPosition(config('imageresizer.watermarks.positions.'.Config::getCachedByPath('design/watermark/'.$watermark.'_position', 'center')))
            ->watermarkHeight($height, Manipulations::UNIT_PIXELS)
            ->watermarkWidth($width, Manipulations::UNIT_PIXELS);

        return $image;
    }

    public function storage()
    {
        return Storage::disk(config('imageresizer.disk'));
    }

    public function download($url)
    {
        if (!$content = @fopen($url, 'r')) {
            abort(404, "Url `{$url}` cannot be reached");
        }

        return $content;
    }

    public function createTempFile($content)
    {
        $temp = tempnam(sys_get_temp_dir(), 'rapidez');
        $this->tmpPaths[] = $temp;
        file_put_contents($temp, $content);

        return $temp;
    }

    public function __destruct()
    {
        foreach ($this->tmpPaths as $path) {
            @unlink($path);
        }
    }
}
