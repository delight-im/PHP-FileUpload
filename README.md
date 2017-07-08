# PHP-FileUpload

Simple and convenient file uploads — secure by default

## Requirements

 * PHP 5.6.0+

## Installation

 1. Include the library via Composer [[?]](https://github.com/delight-im/Knowledge/blob/master/Composer%20(PHP).md):

    ```
    $ composer require delight-im/file-upload
    ```

 1. Include the Composer autoloader:

    ```php
    require __DIR__ . '/vendor/autoload.php';
    ```

 1. Set up your HTML form for the file upload, e.g.:

    ```html
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="MAX_FILE_SIZE" value="1048576">
        <input type="file" name="my-input-name">
        <button type="submit">Upload</button>
    </form>
    ```

    The two attributes `method="post"` and `enctype="multipart/form-data"` on the `<form>` element are mandatory. Likewise, there must be at least one `<input type="file">` element with a proper `name` attribute. Finally, some way to submit the form, e.g. the `<button type="submit">` element, is required. The hidden input named `MAX_FILE_SIZE` is an optional hint for the client.

## Usage

```php
$upload = new \Delight\FileUpload\FileUpload();
$upload->withTargetDirectory('/path/to/users/' . $userId . '/avatars');
$upload->from('my-input-name');

try {
    $uploadedFile = $upload->save();

    // success

    // $uploadedFile->getFilenameWithExtension();
}
catch (\Delight\FileUpload\Throwable\InputNotFoundException $e) {
    // Input not found
}
catch (\Delight\FileUpload\Throwable\InvalidFilenameException $e) {
    // Invalid filename
}
catch (\Delight\FileUpload\Throwable\InvalidExtensionException $e) {
    // Invalid extension
}
catch (\Delight\FileUpload\Throwable\FileTooLargeException $e) {
    // File too large
}
catch (\Delight\FileUpload\Throwable\UploadCancelledException $e) {
    // Upload cancelled
}
```

### Limiting the maximum permitted file size

```php
$upload->withMaximumSizeInBytes(8192);

// or

$upload->withMaximumSizeInKilobytes(512);

// or

$upload->withMaximumSizeInMegabytes(32);
```

### Reading the maximum permitted file size

```php
$upload->getMaximumSizeInBytes(); // int(8192)

// or

$upload->getMaximumSizeInKilobytes(); // int(512)

// or

$upload->getMaximumSizeInMegabytes(); // int(32)
```

### Restricting the allowed file types or extensions

```php
$upload->withAllowedExtensions([ 'jpeg', 'jpg', 'png', 'gif' ]);
```

### Reading the allowed file types or extensions

```php
$upload->getAllowedExtensionsAsArray(); // array(4) { ... }

// or

$upload->getAllowedExtensionsAsMachineString(); // string(16) "jpeg,jpg,png,gif"

// or

$upload->getAllowedExtensionsAsHumanString(); // string(19) "JPEG, JPG, PNG, GIF"

// or

$upload->getAllowedExtensionsAsHumanString(' or '); // string(21) "JPEG, JPG, PNG or GIF"
```

### Reading the target directory

```php
$upload->getTargetDirectory(); // string(25) "/path/to/users/42/avatars"
```

### Defining the target filename

```php
$upload->withTargetFilename('my-picture');
```

### Reading the target filename

```php
$upload->getTargetFilename(); // string(10) "my-picture"
```

### Reading the name of the input field

```php
$upload->getSourceInputName(); // string(13) "my-input-name"
```

## Contributing

All contributions are welcome! If you wish to contribute, please create an issue first so that your feature, problem or question can be discussed.

## License

This project is licensed under the terms of the [MIT License](https://opensource.org/licenses/MIT).
