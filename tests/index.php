<?php

/*
 * PHP-FileUpload (https://github.com/delight-im/PHP-FileUpload)
 * Copyright (c) delight.im (https://www.delight.im/)
 * Licensed under the MIT License (https://opensource.org/licenses/MIT)
 */

// enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 'stdout');

// enable assertions
ini_set('assert.active', 1);
@ini_set('zend.assertions', 1);
ini_set('assert.exception', 1);

header('Content-type: text/html; charset=utf-8');

require __DIR__.'/../vendor/autoload.php';

$upload = new \Delight\FileUpload\FileUpload();
$upload->withMaximumSizeInMegabytes(2);
$upload->withAllowedExtensions([ 'jpeg', 'jpg', 'png', 'gif' ]);
$upload->withTargetDirectory(__DIR__ . '/../uploads');

if (mt_rand(1, 100) <= 50) {
	$upload->withTargetFilename(\time());
}

$upload->from('my-file');

try {
	$uploadedFile = $upload->save();

	$message = 'Success: '.$uploadedFile->getFilenameWithExtension();
}
catch (\Delight\FileUpload\Throwable\InputNotFoundException $e) {
	$message = 'Input not found';
}
catch (\Delight\FileUpload\Throwable\InvalidFilenameException $e) {
	$message = 'Invalid filename';
}
catch (\Delight\FileUpload\Throwable\InvalidExtensionException $e) {
	$message = 'Invalid extension';
}
catch (\Delight\FileUpload\Throwable\FileTooLargeException $e) {
	$message = 'File too large';
}
catch (\Delight\FileUpload\Throwable\UploadCancelledException $e) {
	$message = 'Upload cancelled';
}

echo '<!DOCTYPE html>';
echo '<html>';
echo '  <head>';
echo '    <meta charset="utf-8">';
echo '    <meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '  </head>';
echo '  <body>';
echo '    <h1>' . $message . '</h1>';
echo '    <form action="" method="post" enctype="multipart/form-data">';
echo '      <input type="hidden" name="MAX_FILE_SIZE" value="' . $upload->getMaximumSizeInBytes() . '">';
echo '      <fieldset>';
echo '        <label for="my-file">File</label>';
echo '        <input type="file" id="my-file" name="my-file">';
echo '        <p>Supported formats: ' . $upload->getAllowedExtensionsAsHumanString(' and ') . '</p>';
echo '        <p>Maximum size: ' . $upload->getMaximumSizeInKilobytes() . ' KB</p>';
echo '      </fieldset>';
echo '      <fieldset>';
echo '        <button type="submit">Upload</button>';
echo '      </fieldset>';
echo '    </form>';
echo '  </body>';
echo '</html>';
