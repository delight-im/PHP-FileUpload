<?php

/*
 * PHP-FileUpload (https://github.com/delight-im/PHP-FileUpload)
 * Copyright (c) delight.im (https://www.delight.im/)
 * Licensed under the MIT License (https://opensource.org/licenses/MIT)
 */

namespace Delight\FileUpload;

use Delight\FileUpload\Throwable\Error;
use Delight\FileUpload\Throwable\FileTooLargeException;
use Delight\FileUpload\Throwable\InputNotFoundException;
use Delight\FileUpload\Throwable\InputNotSpecifiedError;
use Delight\FileUpload\Throwable\InvalidExtensionException;
use Delight\FileUpload\Throwable\TempDirectoryNotFoundError;
use Delight\FileUpload\Throwable\TempFileWriteError;
use Delight\FileUpload\Throwable\UploadCancelledError;
use Delight\FileUpload\Throwable\UploadCancelledException;

/** Helper for simple and convenient file uploads */
final class FileUpload extends AbstractUpload {

	/** @var string[] the set of permitted filename extensions (without leading dots) */
	private $allowedExtensions;
	/** @var string|null the name of the input that is the source for the file to be uploaded */
	private $sourceInputName;

	public function __construct() {
		parent::__construct();

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
		$this->sourceInputName = null;
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

	public function save() {
		if (empty($this->sourceInputName)) {
			throw new InputNotSpecifiedError();
		}

		if (!isset($_FILES[$this->sourceInputName])) {
			throw new InputNotFoundException();
		}

		$data = $_FILES[$this->sourceInputName];

		if ($data['error'] === \UPLOAD_ERR_INI_SIZE || $data['error'] === \UPLOAD_ERR_FORM_SIZE) {
			throw new FileTooLargeException();
		}
		elseif ($data['error'] === \UPLOAD_ERR_PARTIAL) {
			throw new UploadCancelledException();
		}
		elseif ($data['error'] === \UPLOAD_ERR_NO_FILE) {
			throw new InputNotFoundException();
		}
		elseif ($data['error'] === \UPLOAD_ERR_NO_TMP_DIR) {
			throw new TempDirectoryNotFoundError();
		}
		elseif ($data['error'] === \UPLOAD_ERR_CANT_WRITE) {
			throw new TempFileWriteError();
		}
		elseif ($data['error'] === \UPLOAD_ERR_EXTENSION) {
			throw new UploadCancelledError();
		}

		if ($data['error'] !== \UPLOAD_ERR_OK) {
			throw new Error();
		}

		if ($data['size'] > $this->getMaximumSizeInBytes()) {
			throw new FileTooLargeException();
		}

		$originalExtension = \strtolower(
			\pathinfo(
				$data['name'],
				\PATHINFO_EXTENSION
			)
		);

		if (!\in_array($originalExtension, $this->allowedExtensions, true)) {
			throw new InvalidExtensionException();
		}

		$targetFile = $this->describeTargetFile($originalExtension);

		if (!@move_uploaded_file($data['tmp_name'], $targetFile->getPath())) {
			throw new Error();
		}

		return $targetFile;
	}

}
