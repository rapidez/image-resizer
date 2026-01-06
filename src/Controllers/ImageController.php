<?php

namespace Rapidez\ImageResizer\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rapidez\Core\Models\Config;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Enums\Unit;
use Spatie\Image\Image;

class ImageController extends Controller
{
    protected array $tmpPaths = [];

    public function __invoke(Request $request, int $store, string $size, string $placeholder, string $file, string $webp = '')
    {
        abort_unless(in_array($size, config('rapidez.imageresizer.sizes')), 400, __('The requested size is not whitelisted.'));
        // Incorrect store is not authorized to generate another stores image.
        // Note: if storage is symlinked it will still SERVE the image.
        abort_if(config('rapidez.store') !== $store, 403);

        $placeholderUrl = config('rapidez.imageresizer.external.'.$placeholder);

        if (!$placeholderUrl && $placeholder !== 'local') {
            $file = $placeholder.'/'.$file;
            $placeholder = 'local';

            return redirect(route('resized-image', @compact('store', 'size', 'placeholder', 'file', 'webp')), 301);
        }

        $resizedPath = Str::after($request->path(), 'storage/');
        $file = Str::start($file, '/');
        if (!$this->storage()->exists($resizedPath)) {
            $content = $placeholderUrl
                ? $this->download($placeholderUrl.$file)
                : $this->storage()->get(config('rapidez.store').$file);

            abort_unless($content, 404);

            $tempFile = $this->createTempFile($content, pathinfo($file, PATHINFO_EXTENSION));
            $image = Image::useImageDriver(config('rapidez.imageresizer.driver', 'imagick'))->loadFile($tempFile)->optimize();
            @list($width, $height) = explode('x', $size);

            // Don't upscale images.
            foreach (['width', 'height'] as $dimension) {
                if (${$dimension} > $image->{'get'.ucfirst($dimension)}()) {
                    ${$dimension} = $image->{'get'.ucfirst($dimension)}();
                }
            }

            if ($height) {
                $image->fit($request->has('crop') ? Fit::Crop : Fit::Contain, $width, $height);
            } else {
                $image->width($width);
            }

            $image = $placeholder == 'magento'
                ? $this->addWatermark($image, $width, $height ?? '400', $size)
                : $image;

            if ($webp) {
                $image->format('webp');
            }

            $image->save();

            $this->storage()->put($resizedPath, file_get_contents($tempFile));
        }

        return $this->storage()->response($resizedPath);
    }

    public function redirectFromSku(Request $request, int $store, string $size, string $file)
    {
        $webp = str_ends_with($file, '.webp') ? '.webp' : '';
        $sku = $webp ? Str::replaceLast('.webp', '', $file) : $file;
        $file = $this->productImageUrlFromSku($sku);
        $placeholder = 'magento';

        return redirect(
            route('resized-image', compact('store', 'size', 'placeholder', 'file', 'webp')),
            config('rapidez.imageresizer.sku.redirect.status_code')
        )->setPublic()->setMaxAge(config('rapidez.imageresizer.sku.redirect.max_age'));
    }

    public function productImageUrlFromSku(string $sku): string
    {
        $productModel = config('rapidez.models.product');
        $query = $productModel::query();
        $product = $query->where($query->qualifyColumn('sku'), $sku)->first();

        if (!$product || !$product->image) {
            return 'catalog/placeholder.jpg';
        }

        return 'catalog/product'.$product->image;
    }

    public function addWaterMark(Image $image, string $width = '400', string $height = '400', string $size = '400'): Image
    {
        $watermark = $width < 200 ? 'thumbnail' : ($width >= 200 && $width < 600 ? 'small_image' : 'image');

        $watermarkImage = Config::getCachedByPath('design/watermark/'.$watermark.'_image');
        if (!$watermarkImage) {
            return $image;
        }

        $position = Config::getCachedByPath('design/watermark/'.$watermark.'_position', 'center');
        $size = Config::getCachedByPath('design/watermark/'.$watermark.'_size', '100x100');

        @list($height, $width) = explode('x', $size);
        $watermarkImageContent = $this->download(config('rapidez.media_url').'/catalog/product/watermark/'.$watermarkImage);
        $tempWatermark = $this->createTempFile($watermarkImageContent, pathinfo($watermarkImage, PATHINFO_EXTENSION));

        $image->watermark(
            watermarkImage: $tempWatermark,
            position: config('rapidez.imageresizer.watermarks.positions.'.$position),
            width: $width,
            widthUnit: Unit::Pixel,
            height: $height,
            heightUnit: Unit::Pixel,
            alpha: Config::getCachedByPath('design/watermark/'.$watermark.'_imageOpacity', 100)
        );

        return $image;
    }

    public function storage()
    {
        return Storage::disk(config('rapidez.imageresizer.disk'));
    }

    public function download($url)
    {
        if (!$content = @fopen($url, 'r')) {
            abort(404, "Url `{$url}` cannot be reached");
        }

        return $content;
    }

    public function createTempFile($content, $extension = '')
    {
        $temp = tempnam(sys_get_temp_dir(), 'rapidez').($extension ? '.'.$extension : '');
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
