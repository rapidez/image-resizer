# Rapidez Image Resizer

Instead of just loading the full and big images from Magento this extension resizes the images. This works by passing the Magento image path through the image route: `/storage/resizes/{size}/{file}`.

Let's say a product image is located at: `https://magentowebsite.com/media/catalog/product/a/a/product-image.jpg` the path will be `/catalog/product/a/a/product-image.jpg`. To get this image with a maximum width of 200 pixels you go to: `/storage/resizes/200/catalog/product/a/a/product-image.jpg`. If you also want to specify a maximum height: `/storage/resizes/200x200/catalog/product/a/a/product-image.jpg`.

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

## How it's working

Images are downloaded from the media url (see `config/rapidez.php`) and stored in `/storage/app/public/resizes`. Because of the symlink created with `php artisan storage:link` the files are publicly availabe and because the route is the same as the path; the webserver first tries to serve the file if it exists, otherwise it will go through PHP to resize and create it.

## License

GNU General Public License v3. Please see [License File](LICENSE) for more information.
