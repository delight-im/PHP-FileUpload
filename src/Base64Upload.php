<?php

/*
 * PHP-FileUpload (https://github.com/delight-im/PHP-FileUpload)
 * Copyright (c) delight.im (https://www.delight.im/)
 * Licensed under the MIT License (https://opensource.org/licenses/MIT)
 */

namespace Delight\FileUpload;

use Delight\FileUpload\Throwable\FileTooLargeException;
use Delight\FileUpload\Throwable\InputNotFoundException;
use Delight\FileUpload\Throwable\InputNotSpecifiedError;
use Delight\FileUpload\Throwable\TargetFileWriteError;

/** Helper for simple and convenient uploads of Base64-encoded data */
final class Base64Upload extends AbstractUpload {

	/** @var string|null the filename extension that uploaded files are to be stored with */
	private $targetFilenameExtension;
	/** @var string|null the Base64-encoded data to upload */
	private $sourceDataBase64;

	public function __construct() {
		parent::__construct();

		$this->targetFilenameExtension = null;
		$this->sourceDataBase64 = null;
	}

	/**
	 * Sets the filename extension that uploaded files are to be stored with
	 *
	 * @param string $filenameExtension
	 * @return static this instance for chaining
	 */
	public function withFilenameExtension($filenameExtension) {
		$this->targetFilenameExtension = \trim((string) $filenameExtension);

		return $this;
	}

	/**
	 * Returns the filename extension that uploaded files are to be stored with
	 *
	 * @return string|null
	 */
	public function getFilenameExtension() {
		return $this->targetFilenameExtension;
	}

	/**
	 * Sets the Base64-encoded data to upload
	 *
	 * @param string $base64
	 * @return static this instance for chaining
	 */
	public function withData($base64) {
		$this->sourceDataBase64 = (string) $base64;

		return $this;
	}

	/**
	 * Returns the Base64-encoded data to upload
	 *
	 * @return string|null
	 */
	public function getData() {
		return $this->sourceDataBase64;
	}

	public function save() {
		if (!isset($this->sourceDataBase64)) {
			throw new InputNotSpecifiedError();
		}

		if ($this->sourceDataBase64 === '') {
			throw new InputNotFoundException();
		}

		$sourceBinary = \base64_decode($this->sourceDataBase64, true);

		if ($sourceBinary === false || $sourceBinary === '') {
			throw new InputNotFoundException();
		}

		if (\strlen($sourceBinary) > $this->getMaximumSizeInBytes()) {
			throw new FileTooLargeException();
		}

		$extension = isset($this->targetFilenameExtension) ? $this->targetFilenameExtension : 'bin';
		$targetFile = $this->describeTargetFile($extension);
		$bytesWritten = \file_put_contents($targetFile->getPath(), $sourceBinary);

		if ($bytesWritten === false) {
			throw new TargetFileWriteError();
		}

		return $targetFile;
	}

}
