<?php

namespace Rapidez\ImageResizer\Controllers;

use Rapidez\ImageResizer\Exceptions\UnreachableUrl;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Image\Image;
use Spatie\Image\Manipulations;

class ImageController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, string $size, string $file)
    {
        abort_unless(in_array($size, config('imageresizer.sizes')), 400, 'The requested size is not whitelisted.');

        if (
            ($format = config('imageresizer.output_format')) !== null &&
            ($info = pathinfo($file))['extension'] !== $format
        ) {
            $resizedFile = $info['dirname'] . '/' . $info['filename'] . '.' . $format;
        }

        $resizedPath = 'resizes/'.$size.'/'.($resizedFile ?? $file);

        if (!Storage::exists('public/'.$resizedPath)) {
            $remoteFile = config('rapidez.media_url').'/'.$file;
            if (!$stream = @fopen($remoteFile, 'r')) {
                throw UnreachableUrl::create($remoteFile);
            }

            $temporaryFile = tempnam(sys_get_temp_dir(), 'rapidez');
            file_put_contents($temporaryFile, $stream);

            $image = Image::load($temporaryFile)->optimize();
            @list($width, $height) = explode('x', $size);

            if ($height) {
                $image->fit(MANIPULATIONS::FIT_CONTAIN, $width, $height);
            } else {
                $image->width($width);
            }

            if (($format = config('imageresizer.output_format')) !== null) {
                $image->format($format);
            }

            if (!is_dir(storage_path('app/public/'.pathinfo($resizedPath, PATHINFO_DIRNAME)))) {
                mkdir(storage_path('app/public/'.pathinfo($resizedPath, PATHINFO_DIRNAME)), 0755, true);
            }

            $image->save(storage_path('app/public/'.$resizedPath));
        }

        return response()->file(storage_path('app/public/'.$resizedPath));
    }
}
