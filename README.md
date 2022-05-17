# Rapidez Image Resizer

Instead of just loading the full and big images from Magento this extension resizes the images. This works by passing the Magento image path through the image route: `/storage/resizes/{size}/{file}`.

Let's say a product image is located at: `https://magentowebsite.com/media/catalog/product/a/a/product-image.jpg` the path will be `/catalog/product/a/a/product-image.jpg`. To get this image with a maximum width of 200 pixels you go to: `/storage/resizes/200/catalog/product/a/a/product-image.jpg`. If you also want to specify a maximum height: `/storage/resizes/200x200/catalog/product/a/a/product-image.jpg`.

Automatic webp conversion will also be done if the url has `.webp` as it's extension e.g. `/storage/resizes/200x200/catalog/product/a/a/product-image.jpg.webp`
This will make it retrieve the image from `https://magentowebsite.com/media/catalog/product/a/a/product-image.jpg`, resize it, format it as webp and save it as `/storage/resizes/200x200/catalog/product/a/a/product-image.jpg.webp`.

## Installation

This package is installed by default in Rapidez. But if removed you can re-install it with:
```
composer require rapidez/image-resizer
```
And make sure you ran `php artisan storage:link`

## Config

Keep in mind that you've to whitelist all sizes to avoid ddos attacks! Publish the config and specify the sizes you want:

```
php artisan vendor:publish --provider="Rapidez\ImageResizer\ImageResizerServiceProvider" --tag=config
```

### External sources

If you are using images from an external location (for example from a CMS like [Strapi](https://github.com/rapidez/strapi)) you can add that source:

```
'external' => [
     'strapi' => env('STRAPI_URL'),
],
```

Now you can use the following path to resize the images from the external source:

```
<img src="{{ '/storage/resizes/<size>/strapi'.$data->image->url }}" />
```

## How it's working

Images are downloaded from the media url (see `config/rapidez.php`) and stored in `/storage/app/public/resizes`. Because of the symlink created with `php artisan storage:link` the files are publicly availabe and because the route is the same as the path; the webserver first tries to serve the file if it exists, otherwise it will go through PHP to resize and create it.

## Deleting resizes

The image resizes can be deleted using this artisan command:
```
php artisan rapidez:resizes:delete {store?}
```

## License

GNU General Public License v3. Please see [License File](LICENSE) for more information.
