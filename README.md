# Rapidez Image Resizer

Instead of just loading the full and big images from Magento this extension resizes the images. This works by passing the Magento image path through the image route: `/storage/{store id}/resizes/{size}/magento/{file}`.

Let's say a product image is located at: `https://magentowebsite.com/media/catalog/product/a/a/product-image.jpg` the path will be `/catalog/product/a/a/product-image.jpg`. To get this image with a maximum width of 200 pixels you go to: `/storage/1/resizes/200/magento/catalog/product/a/a/product-image.jpg`. If you also want to specify a maximum height: `/storage/1/resizes/200x200/magento/catalog/product/a/a/product-image.jpg`.

Automatic webp conversion will also be done if the url has `.webp` as it's extension e.g. `/storage/1/resizes/200x200/magento/catalog/product/a/a/product-image.jpg.webp`
This will make it retrieve the image from `https://magentowebsite.com/media/catalog/product/a/a/product-image.jpg`, resize it, format it as webp and save it as `/storage/1/resizes/200x200/magento/catalog/product/a/a/product-image.jpg.webp`.

## Installation

This package is installed by default in Rapidez. But if removed you can re-install it with:
```sh
composer require rapidez/image-resizer
```
And make sure you ran `php artisan storage:link`

## Image from SKU

You can retrieve a product image by using the product's SKU. This is enabled by default, but can be toggled with the `allow_sku` value in the config file.

To retrieve a product image using SKU, request a path like this: `/storage/1/resizes/200x200/sku/13706`. You can also request a webp like this: `/storage/1/resizes/200x200/sku/13706.webp`.

## Config

Keep in mind that you have to whitelist all sizes to avoid ddos attacks! Publish the config and specify the sizes you want:

```sh
php artisan vendor:publish --provider="Rapidez\ImageResizer\ImageResizerServiceProvider" --tag=config
```

### External sources

If you are using images from other external location (see the [CMS integrations](https://docs.rapidez.io/0.x/packages.html#cms)) you can add that source:

```
'external' => [
     'strapi' => env('STRAPI_URL'),
],
```

Now you can use the following path to resize the images from the external source:

```html
<img src="{{ '/storage/' . config('rapidez.store') . '/resizes/<size>/strapi'.$data->image->url }}" />
```

Or alternatively using Laravels route function

```html
<img src="{{ route('resized-image', [
    'store' => config('rapidez.store'), 
    'size' => '<size>', 
    'placeholder' => 'strapi', 
    'file' => $data->image->url
]) }}" />
```

## How it works

Images are downloaded from the media url and stored in `/storage/app/public/<store>/resizes`. Because of the symlink created with `php artisan storage:link` the files are then publicly available. Because the route is the same as the path, the webserver first tries to serve the file if it exists, otherwise it will go through PHP to resize and create it.

## Deleting resizes

The image resizes can be deleted using this artisan command:
```sh
php artisan rapidez:resizes:delete {store?}
```

## License

GNU General Public License v3. Please see [License File](LICENSE) for more information.
