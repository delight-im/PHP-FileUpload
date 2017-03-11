<?php

/*
 * PHP-FileUpload (https://github.com/delight-im/PHP-FileUpload)
 * Copyright (c) delight.im (https://www.delight.im/)
 * Licensed under the MIT License (https://opensource.org/licenses/MIT)
 */

namespace Delight\FileUpload;

use Delight\FileUpload\Throwable\Error;
use Delight\FileUpload\Throwable\FileTooLargeException;
use Delight\FileUpload\Throwable\FileUploadsDisabledError;
use Delight\FileUpload\Throwable\InputNotFoundException;
use Delight\FileUpload\Throwable\InputNotSpecifiedError;
use Delight\FileUpload\Throwable\InvalidExtensionException;
use Delight\FileUpload\Throwable\InvalidFilenameException;
use Delight\FileUpload\Throwable\TargetDirectoryNotSpecifiedError;
use Delight\FileUpload\Throwable\TargetFileWriteError;
use Delight\FileUpload\Throwable\TempDirectoryNotFoundError;
use Delight\FileUpload\Throwable\TempFileWriteError;
use Delight\FileUpload\Throwable\TotalSizeExceededError;
use Delight\FileUpload\Throwable\UploadCancelledError;
use Delight\FileUpload\Throwable\UploadCancelledException;

/** Helper for simple and convenient file uploads */
final class FileUpload {

	/** @var int the maximum size in bytes that all file uploads in a request combined may have */
	private $maxTotalSize;
	/** @var int the maximum size in bytes that a single file upload may have */
	private $maxIndividualSize;
	/** @var string[] the set of permitted filename extensions (without leading dots) */
	private $allowedExtensions;
	/** @var Directory|null the directory where uploaded files are to be stored */
	private $targetDirectory;
	/** @var string|null the filename to store uploaded files with (instead of an automatically generated name) */
	private $targetFilename;
	/** @var string|null the name of the input that is the source for the file to be uploaded */
	private $sourceInputName;

	/**
	 * @throws Error (do *not* catch)
	 */
	public function __construct() {
		$this->maxTotalSize = self::determineMaximumUploadSize();
		$this->maxIndividualSize = $this->maxTotalSize;

		$this->allowedExtensions = [
			'7z',
			'csv',
			'doc',
			'docx',
			'gif',
			'gz',
			'ical',
			'ics',
			'jpeg',
			'jpg',
			'json',
			'log',
			'm3u',
			'm4a',
			'm4v',
			'mkv',
			'mp3',
			'mp4',
			'ods',
			'odt',
			'ogg',
			'pdf',
			'png',
			'pps',
			'ppt',
			'pptx',
			'svg',
			'txt',
			'vcard',
			'vcf',
			'webm',
			'webp',
			'xls',
			'xlsx',
			'xml',
			'xspf',
			'zip'
		];

		$this->targetDirectory = null;
		$this->targetFilename = null;
		$this->sourceInputName = null;

		if (!self::areFileUploadsEnabled()) {
			throw new FileUploadsDisabledError();
		}
	}

	/**
	 * Restricts the maximum size of individual files to be uploaded to the specified size
	 *
	 * @param int $size the size in bytes
	 * @return static this instance for chaining
	 * @throws Error (do *not* catch)
	 */
	public function withMaximumSizeInBytes($size) {
		$size = (int) $size;

		if ($size > $this->maxTotalSize) {
			throw new TotalSizeExceededError();
		}

		$this->maxIndividualSize = $size;

		return $this;
	}

	/**
	 * Restricts the maximum size of individual files to be uploaded to the specified size
	 *
	 * @param int $size the size in kilobytes (KB)
	 * @return static this instance for chaining
	 */
	public function withMaximumSizeInKilobytes($size) {
		return $this->withMaximumSizeInBytes((int) $size * 1024);
	}

	/**
	 * Restricts the maximum size of individual files to be uploaded to the specified size
	 *
	 * @param int $size the size in megabytes (MB)
	 * @return static this instance for chaining
	 */
	public function withMaximumSizeInMegabytes($size) {
		return $this->withMaximumSizeInBytes((int) $size * 1024 * 1024);
	}

	/**
	 * Restricts the maximum size of individual files to be uploaded to the specified size
	 *
	 * @param int $size the size in gigabytes (GB)
	 * @return static this instance for chaining
	 */
	public function withMaximumSizeInGigabytes($size) {
		return $this->withMaximumSizeInBytes((int) $size * 1024 * 1024 * 1024);
	}

	/**
	 * Returns the maximum size of individual files to be uploaded
	 *
	 * @return int the size in bytes
	 */
	public function getMaximumSizeInBytes() {
		return $this->maxIndividualSize;
	}

	/**
	 * Returns the maximum size of individual files to be uploaded
	 *
	 * @return int the size in kilobytes (KB)
	 */
	public function getMaximumSizeInKilobytes() {
		return (int) ($this->maxIndividualSize / 1024);
	}

	/**
	 * Returns the maximum size of individual files to be uploaded
	 *
	 * @return int the size in megabytes (MB)
	 */
	public function getMaximumSizeInMegabytes() {
		return (int) ($this->maxIndividualSize / 1024 / 1024);
	}

	/**
	 * Returns the maximum size of individual files to be uploaded
	 *
	 * @return int the size in gigabytes (GB)
	 */
	public function getMaximumSizeInGigabytes() {
		return (int) ($this->maxIndividualSize / 1024 / 1024 / 1024);
	}

	/**
	 * Changes the list of allowed file extensions (without leading dots) to the supplied set
	 *
	 * @param string[] $extensions the list of file extensions (without leading dots)
	 * @return static this instance for chaining
	 */
	public function withAllowedExtensions(array $extensions) {
		if (\is_array($extensions) && !empty($extensions)) {
			$extensions = \array_map('trim', $extensions);
			$extensions = \array_map('strtolower', $extensions);

			$this->allowedExtensions = $extensions;
		}

		return $this;
	}

	/**
	 * Returns the list of allowed file extensions (without leading dots) as an array
	 *
	 * @return array
	 */
	public function getAllowedExtensionsAsArray() {
		return $this->allowedExtensions;
	}

	/**
	 * Returns the list of allowed file extensions (without leading dots) as a machine-readable string
	 *
	 * @return string
	 */
	public function getAllowedExtensionsAsMachineString() {
		return \implode(',', $this->allowedExtensions);
	}

	/**
	 * Returns the list of allowed file extensions (without leading dots) as a human-readable string
	 *
	 * @param string|null $lastSeparator (optional) the last separator as an alternative to the comma, e.g. ` or `
	 * @return string
	 */
	public function getAllowedExtensionsAsHumanString($lastSeparator = null) {
		$separator = ', ';

		$str = \implode($separator, $this->allowedExtensions);
		$str = \strtoupper($str);

		if ($lastSeparator !== null) {
			$lastSeparatorPosition = \strrpos($str, $separator);

			if ($lastSeparatorPosition !== false) {
				$str = \substr_replace($str, $lastSeparator, $lastSeparatorPosition, \strlen($separator));
			}
		}

		return $str;
	}

	/**
	 * Sets the target directory where uploaded files are to be stored
	 *
	 * The directory should *not* be publicly accessible (e.g. via HTTP) whenever possible
	 *
	 * In general, you should never let this directory be controlled by the user
	 *
	 * @param string $targetDirectory
	 * @return static this instance for chaining
	 */
	public function withTargetDirectory($targetDirectory) {
		$this->targetDirectory = new Directory($targetDirectory);

		return $this;
	}

	/**
	 * Returns the target directory where uploaded files are to be stored
	 *
	 * @return string|null
	 */
	public function getTargetDirectory() {
		if ($this->targetDirectory === null) {
			return null;
		}
		else {
			return $this->targetDirectory->getPath();
		}
	}

	/**
	 * Sets the target filename which uploaded files are to be stored with
	 *
	 * If you do not specify a filename explicitly, a new random filename will be generated automatically
	 *
	 * The filename should *not* include any file extension because the extension will be appended automatically
	 *
	 * You can control permitted file extensions via the {@see withAllowedExtensions} method
	 *
	 * @param string $targetFilename
	 * @return static this instance for chaining
	 */
	public function withTargetFilename($targetFilename) {
		$targetFilename = (string) $targetFilename;
		$targetFilename = \trim($targetFilename);

		$this->targetFilename = $targetFilename;

		return $this;
	}

	/**
	 * Returns the target filename which uploaded files are to be stored with
	 *
	 * @return string|null
	 */
	public function getTargetFilename() {
		return $this->targetFilename;
	}

	/**
	 * Sets the name of the file input whose selected file is to be received and stored
	 *
	 * @param string $inputName usually the `name` attribute of the `<input type="file">` HTML element
	 * @return static this instance for chaining
	 */
	public function from($inputName) {
		$this->sourceInputName = (string) $inputName;

		// remove leading and trailing whitespace
		$this->sourceInputName = \trim($this->sourceInputName);

		return $this;
	}

	/**
	 * Returns the name of the file input whose selected file is to be received and stored
	 *
	 * @return string|null
	 */
	public function getSourceInputName() {
		return $this->sourceInputName;
	}

	/**
	 * Receives and stores the file to be uploaded
	 *
	 * @return File an object containing information about the uploaded file at its target location
	 * @throws InputNotFoundException if the specified file input has not been found or has been empty
	 * @throws InvalidFilenameException if the supplied filename has been invalid
	 * @throws InvalidExtensionException if the file extension of the file to be uploaded has not been on the list of permitted extensions
	 * @throws FileTooLargeException if the file to be uploaded has been too large
	 * @throws UploadCancelledException if the upload has been cancelled for some reason (either by the client or by the server)
	 * @throws Error (do *not* catch)
	 */
	public function save() {
		if (empty($this->sourceInputName)) {
			throw new InputNotSpecifiedError();
		}

		if ($this->targetDirectory === null) {
			throw new TargetDirectoryNotSpecifiedError();
		}

		$targetFilename = isset($this->targetFilename) ? $this->targetFilename : self::createRandomString();

		if (!File::mayNameBeValid($targetFilename)) {
			throw new InvalidFilenameException();
		}

		if (!isset($_FILES[$this->sourceInputName])) {
			throw new InputNotFoundException();
		}

		$data = $_FILES[$this->sourceInputName];

		if ($data['error'] === UPLOAD_ERR_INI_SIZE || $data['error'] === UPLOAD_ERR_FORM_SIZE) {
			throw new FileTooLargeException();
		}
		elseif ($data['error'] === UPLOAD_ERR_PARTIAL) {
			throw new UploadCancelledException();
		}
		elseif ($data['error'] === UPLOAD_ERR_NO_FILE) {
			throw new InputNotFoundException();
		}
		elseif ($data['error'] === UPLOAD_ERR_NO_TMP_DIR) {
			throw new TempDirectoryNotFoundError();
		}
		elseif ($data['error'] === UPLOAD_ERR_CANT_WRITE) {
			throw new TempFileWriteError();
		}
		elseif ($data['error'] === UPLOAD_ERR_EXTENSION) {
			throw new UploadCancelledError();
		}

		if ($data['error'] !== UPLOAD_ERR_OK) {
			throw new Error();
		}

		if ($data['size'] > $this->maxIndividualSize) {
			throw new FileTooLargeException();
		}

		$originalExtension = \strtolower(
			\pathinfo(
				$data['name'],
				PATHINFO_EXTENSION
			)
		);

		if (!\in_array($originalExtension, $this->allowedExtensions, true)) {
			throw new InvalidExtensionException();
		}

		if (!$this->targetDirectory->exists() && !$this->targetDirectory->createRecursively(0755)) {
			throw new TargetFileWriteError();
		}

		$targetFile = new File($this->targetDirectory, $targetFilename, $originalExtension);

		if (!@move_uploaded_file($data['tmp_name'], $targetFile->getPath())) {
			throw new Error();
		}

		return $targetFile;
	}

	/**
	 * Returns whether file uploads are enabled as per PHP's configuration
	 *
	 * @return bool
	 */
	private static function areFileUploadsEnabled() {
		if (self::parseIniBoolean(\ini_get('file_uploads')) === false) {
			return false;
		}

		if (((int) \ini_get('max_file_uploads')) <= 0) {
			return false;
		}

		return true;
	}

	/**
	 * Determines the maximum allowed size of file uploads as per PHP's configuration
	 *
	 * @return int the size in bytes
	 */
	private static function determineMaximumUploadSize() {
		return \min(
			self::parseIniSize(
				\ini_get('upload_max_filesize')
			),
			self::parseIniSize(
				\ini_get('post_max_size')
			),
			self::parseIniSize(
				\ini_get('memory_limit')
			)
		);
	}

	/**
	 * Parses a memory or storage size as found in the `php.ini` configuration file
	 *
	 * @param string $sizeStr the representation found in `php.ini`
	 * @return int the effective memory or storage size
	 */
	private static function parseIniSize($sizeStr) {
		$sizeStr = \trim($sizeStr);

		$unitPrefix = \strtoupper(
			\substr($sizeStr, -1)
		);

		$sizeInt = (int) $sizeStr;

		switch ($unitPrefix) {
			case 'K':
				$sizeInt *= 1024;
				break;
			case 'M':
				$sizeInt *= 1024 * 1024;
				break;
			case 'G':
				$sizeInt *= 1024 * 1024 * 1024;
				break;
		}

		return $sizeInt;
	}

	/**
	 * Parses a boolean value as found in the `php.ini` configuration file
	 *
	 * @param string $booleanStr the representation found in `php.ini`
	 * @return bool the effective boolean value
	 */
	private static function parseIniBoolean($booleanStr) {
		if (empty($booleanStr)) {
			return false;
		}

		$booleanStr = \strtolower($booleanStr);

		if ($booleanStr === 'off' || $booleanStr === 'false' || $booleanStr === 'no' || $booleanStr === 'none') {
			return false;
		}

		if (((bool) $booleanStr) === false) {
			return false;
		}

		return true;
	}

	/**
	 * Creates a random hexadecimal string
	 *
	 * @return string
	 */
	private static function createRandomString() {
		return \bin2hex(
			\openssl_random_pseudo_bytes(32)
		);
	}

}
