<?php

namespace Rapidez\ImageResizer\Actions;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rapidez\Core\Models\Config;
use Spatie\Image\Image;
use Spatie\Image\Manipulations;

class CreateResizedImage
{
    protected array $tmpPaths = [];

    public function execute(
        string $size,
        string $file,
        string $placeholder = 'local',
        string $webp = '',
        string|bool $customFilename = false,
        bool $crop = false
    ): string {
        $fileUrl = null;
        if (str_starts_with($file, 'http')) {
            $fileUrl = $file;
            $file = trim(parse_url($file)['path'], '/');
        }

        $resizedPath = config('rapidez.store').'/resizes/'.$placeholder.'/'.$size.'/'.($customFilename ?: $file).$webp;

        if (!$this->storage()->exists($resizedPath)) {
            $content = isset($fileUrl)
                ? $this->download($fileUrl)
                : $this->storage()->get(config('rapidez.store').'/'.$file);

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
                $image->fit($crop ? Manipulations::FIT_CROP : Manipulations::FIT_CONTAIN, $width, $height);
            } else {
                $image->width($width);
            }

            $image = $placeholder == 'magento'
                ? $this->addWatermark($image, $width, $height ?? '400', $size)
                : $image;

            $image->save();

            $this->storage()->put($resizedPath, file_get_contents($tempFile));
        }

        return $resizedPath;
    }

    public function addWaterMark(Image $image, string $width = '400', string $height = '400', string $size = '400'): Image
    {
        $watermark = $width < 200 ? 'thumbnail' : ($width < 600 ? 'small_image' : 'image');

        $watermarkImage = Config::getCachedByPath('design/watermark/'.$watermark.'_image');
        if (!$watermarkImage) {
            return $image;
        }

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