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

### File uploads

```php
$upload = new \Delight\FileUpload\FileUpload();
$upload->withTargetDirectory('/my-app/users/' . $userId . '/avatars');
$upload->from('my-input-name');

try {
    $uploadedFile = $upload->save();

    // success: $uploadedFile->getFilenameWithExtension()
}
catch (\Delight\FileUpload\Throwable\InputNotFoundException $e) {
    // input not found
}
catch (\Delight\FileUpload\Throwable\InvalidFilenameException $e) {
    // invalid filename
}
catch (\Delight\FileUpload\Throwable\InvalidExtensionException $e) {
    // invalid extension
}
catch (\Delight\FileUpload\Throwable\FileTooLargeException $e) {
    // file too large
}
catch (\Delight\FileUpload\Throwable\UploadCancelledException $e) {
    // upload cancelled
}
```

#### Limiting the maximum permitted file size

```php
$upload->withMaximumSizeInBytes(4194304);

// or

$upload->withMaximumSizeInKilobytes(4096);

// or

$upload->withMaximumSizeInMegabytes(4);
```

#### Reading the maximum permitted file size

```php
// e.g. int(4194304)
$upload->getMaximumSizeInBytes();

// or

// e.g. int(4096)
$upload->getMaximumSizeInKilobytes();

// or

// e.g. int(4)
$upload->getMaximumSizeInMegabytes();
```

#### Restricting the allowed file types or extensions

```php
$upload->withAllowedExtensions([ 'jpeg', 'jpg', 'png', 'gif' ]);
```

#### Reading the allowed file types or extensions

```php
// e.g. array(4) { [0]=> string(4) "jpeg" [1]=> string(3) "jpg" [2]=> string(3) "png" [3]=> string(3) "gif" }
$upload->getAllowedExtensionsAsArray();

// or

// e.g. string(16) "jpeg,jpg,png,gif"
$upload->getAllowedExtensionsAsMachineString();

// or

// e.g. string(19) "JPEG, JPG, PNG, GIF"
$upload->getAllowedExtensionsAsHumanString();

// or

// e.g. string(21) "JPEG, JPG, PNG or GIF"
$upload->getAllowedExtensionsAsHumanString(' or ');
```

#### Reading the target directory

```php
// e.g. string(24) "/my-app/users/42/avatars"
$upload->getTargetDirectory();
```

#### Defining the target filename

```php
$upload->withTargetFilename('my-picture');
```

**Note:** By default, a random filename will be used, which is sufficient (and desired) in many cases.

#### Reading the target filename

```php
// e.g. string(10) "my-picture"
$upload->getTargetFilename();
```

#### Reading the name of the input field

```php
// e.g. string(13) "my-input-name"
$upload->getSourceInputName();
```

## Contributing

All contributions are welcome! If you wish to contribute, please create an issue first so that your feature, problem or question can be discussed.

## License

This project is licensed under the terms of the [MIT License](https://opensource.org/licenses/MIT).
