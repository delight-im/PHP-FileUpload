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
use Delight\FileUpload\Throwable\InvalidExtensionException;
use Delight\FileUpload\Throwable\InvalidFilenameException;
use Delight\FileUpload\Throwable\TargetDirectoryNotSpecifiedError;
use Delight\FileUpload\Throwable\TargetFileWriteError;
use Delight\FileUpload\Throwable\TotalSizeExceededError;
use Delight\FileUpload\Throwable\UploadCancelledException;

/** Abstract base class for simple and convenient uploads */
abstract class AbstractUpload {

	/** @var int the maximum size in bytes that all file uploads in a request combined may have */
	private $maxTotalSize;
	/** @var int the maximum size in bytes that a single file upload may have */
	private $maxIndividualSize;
	/** @var Directory|null the directory where uploaded files are to be stored */
	private $targetDirectory;
	/** @var string|null the filename to store uploaded files with (instead of an automatically generated name) */
	private $targetFilename;

	/**
	 * @throws Error (do *not* catch)
	 */
	public function __construct() {
		$this->maxTotalSize = self::determineMaximumUploadSize();
		$this->maxIndividualSize = $this->maxTotalSize;
		$this->targetDirectory = null;
		$this->targetFilename = null;

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
	abstract public function save();

	/**
	 * Returns a description of the target file
	 *
	 * @param string $extension the filename extension to use
	 * @return File the description of the targe file
	 * @throws InvalidFilenameException if the supplied filename has been invalid
	 * @throws Error (do *not* catch)
	 */
	protected function describeTargetFile($extension) {
		if ($this->targetDirectory === null) {
			throw new TargetDirectoryNotSpecifiedError();
		}

		$targetFilename = isset($this->targetFilename) ? $this->targetFilename : self::createRandomString();

		if (!File::mayNameBeValid($targetFilename)) {
			throw new InvalidFilenameException();
		}

		if (!$this->targetDirectory->exists() && !$this->targetDirectory->createRecursively(0755)) {
			throw new TargetFileWriteError();
		}

		return new File($this->targetDirectory, $targetFilename, $extension);
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
		$postMaxSize = self::parseIniSize(
			\ini_get('post_max_size')
		);

		if ($postMaxSize <= 0) {
			$postMaxSize = \PHP_INT_MAX;
		}

		$memoryLimit = self::parseIniSize(
			\ini_get('memory_limit')
		);

		if ($memoryLimit === -1) {
			$memoryLimit = \PHP_INT_MAX;
		}

		return \min(
			self::parseIniSize(
				\ini_get('upload_max_filesize')
			),
			$postMaxSize,
			$memoryLimit
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
