<?php

namespace Rapidez\ImageResizer\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Rapidez\ImageResizer\Actions\CreateResizedImage;

class ImageController extends Controller
{
    public function __invoke(
        Request $request,
        CreateResizedImage $createResizedImage,
        string $size,
        string $file,
        string $webp = '',
        string|bool $customFilename = false
    ) {
        abort_if(Str::startsWith($file, 'http'), 400);
        abort_unless(in_array($size, config('imageresizer.sizes')), 400, 'The requested size is not whitelisted.');

        $placeholder = null;
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

        $resizedPath = $createResizedImage->execute($size, ($placeholderUrl ?? '').$file, $placeholder, $webp, $customFilename, $request->has('crop'));

        return $createResizedImage->storage()->response($resizedPath);
    }
}
