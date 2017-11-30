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
use Delight\FileUpload\Throwable\InvalidExtensionException;
use Delight\FileUpload\Throwable\TargetFileWriteError;

/** Helper for simple and convenient uploads of data URIs */
final class DataUriUpload extends AbstractUpload {

	const FORMAT_REGEX = '/^data:([a-zA-Z0-9\/;=+-]*?)(;base64)?,(.*)$/s';

	/** @var string[] the set of permitted MIME types mapped to filename extensions (without leading dots) */
	private $allowedMimeTypesAndExtensions;
	/** @var string|null the data URI to be uploaded */
	private $sourceDataUri;

	public function __construct() {
		parent::__construct();

		$this->allowedMimeTypesAndExtensions = [
			'application/gzip' => 'gz',
			'application/json' => 'json',
			'application/msword' => 'doc',
			'application/octet-stream' => 'bin',
			'application/ogg' => 'ogg',
			'application/pdf' => 'pdf',
			'application/vnd.ms-excel' => 'xls',
			'application/vnd.ms-powerpoint' => 'pps',
			'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
			'application/vnd.oasis.opendocument.text' => 'odt',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
			'application/x-7z-compressed' => '7z',
			'application/xml' => 'xml',
			'application/xspf+xml' => 'xspf',
			'application/zip' => 'zip',
			'audio/mp4' => 'm4a',
			'audio/mpeg' => 'mp3',
			'audio/ogg' => 'ogg',
			'audio/webm' => 'weba',
			'audio/x-matroska' => 'mka',
			'audio/x-mpegurl' => 'm3u',
			'image/gif' => 'gif',
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/svg+xml' => 'svg',
			'image/webp' => 'webp',
			'text/calendar' => 'ics',
			'text/csv' => 'csv',
			'text/plain' => 'txt',
			'text/vcard' => 'vcf',
			'video/mp4' => 'm4v',
			'video/webm' => 'webm',
			'video/x-matroska' => 'mkv'
		];
		$this->sourceDataUri = null;
	}

	/**
	 * Changes the list of permitted MIME types (mapped to filename extensions without leading dots) to the supplied set
	 *
	 * The key of each entry must be the MIME type to be accepted (e.g. `image/jpeg`)
	 *
	 * The value of each entry must be the corresponding filename extension (without a leading dot), e.g. `jpg`
	 *
	 * @param string[] $mimeTypesAndExtensions the list of MIME types mapped to filename extensions (without leading dots)
	 * @return static this instance for chaining
	 */
	public function withAllowedMimeTypesAndExtensions(array $mimeTypesAndExtensions) {
		if (\is_array($mimeTypesAndExtensions) && !empty($mimeTypesAndExtensions)) {
			$normalized = [];

			foreach ($mimeTypesAndExtensions as $key => $value) {
				$key = \strtolower(\trim((string) $key));
				$value = \strtolower(\trim((string) $value));

				$normalized[$key] = $value;
			}

			$this->allowedMimeTypesAndExtensions = $normalized;
		}

		return $this;
	}

	/**
	 * Returns the list of permitted MIME types (mapped to filename extensions without leading dots)
	 *
	 * @return array
	 */
	public function getAllowedMimeTypesAndExtensions() {
		return $this->allowedMimeTypesAndExtensions;
	}

	/**
	 * Returns the list of permitted MIME types as an array
	 *
	 * @return array
	 */
	public function getAllowedMimeTypesAsArray() {
		return \array_keys($this->allowedMimeTypesAndExtensions);
	}

	/**
	 * Returns the list of permitted MIME types as a machine-readable string
	 *
	 * @return string
	 */
	public function getAllowedMimeTypesAsMachineString() {
		return \implode(',', $this->getAllowedMimeTypesAsArray());
	}

	/**
	 * Returns the list of permitted MIME types as a human-readable string
	 *
	 * @param string|null $lastSeparator (optional) the last separator as an alternative to the comma, e.g. ` or `
	 * @return string
	 */
	public function getAllowedMimeTypesAsHumanString($lastSeparator = null) {
		$separator = ', ';

		$str = \implode($separator, $this->getAllowedMimeTypesAsArray());

		if ($lastSeparator !== null) {
			$lastSeparatorPosition = \strrpos($str, $separator);

			if ($lastSeparatorPosition !== false) {
				$str = \substr_replace($str, $lastSeparator, $lastSeparatorPosition, \strlen($separator));
			}
		}

		return $str;
	}

	/**
	 * Returns the list of permitted filename extensions (without leading dots) as an array
	 *
	 * @return array
	 */
	public function getAllowedExtensionsAsArray() {
		return \array_values($this->allowedMimeTypesAndExtensions);
	}

	/**
	 * Returns the list of permitted filename extensions (without leading dots) as a machine-readable string
	 *
	 * @return string
	 */
	public function getAllowedExtensionsAsMachineString() {
		return \implode(',', $this->getAllowedExtensionsAsArray());
	}

	/**
	 * Returns the list of permitted filename extensions (without leading dots) as a human-readable string
	 *
	 * @param string|null $lastSeparator (optional) the last separator as an alternative to the comma, e.g. ` or `
	 * @return string
	 */
	public function getAllowedExtensionsAsHumanString($lastSeparator = null) {
		$separator = ', ';

		$str = \implode($separator, $this->getAllowedExtensionsAsArray());
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
	 * Sets the data URI to be uploaded
	 *
	 * @param string $dataUri
	 * @return static this instance for chaining
	 */
	public function withUri($dataUri) {
		$this->sourceDataUri = (string) $dataUri;

		return $this;
	}

	/**
	 * Returns the data URI to be uploaded
	 *
	 * @return string|null
	 */
	public function getUri() {
		return $this->sourceDataUri;
	}

	public function save() {
		if (!isset($this->sourceDataUri)) {
			throw new InputNotSpecifiedError();
		}

		if ($this->sourceDataUri === '') {
			throw new InputNotFoundException();
		}

		if (!\preg_match(self::FORMAT_REGEX, $this->sourceDataUri, $components)) {
			throw new InputNotFoundException();
		}

		$mimeTypeAndEncoding = \explode(
			';charset=',
			!empty($components[1]) ? $components[1] : 'text/plain;charset=US-ASCII',
			2
		);
		$mimeType = $mimeTypeAndEncoding[0];
		// $encoding = isset($mimeTypeAndEncoding[1]) ? $mimeTypeAndEncoding[1] : null;
		$extension = isset($this->allowedMimeTypesAndExtensions[$mimeType]) ? $this->allowedMimeTypesAndExtensions[$mimeType] : null;

		if ($extension === null) {
			throw new InvalidExtensionException();
		}

		if (empty($components[2])) {
			$sourceBinary = \urldecode($components[3]);
		}
		else {
			$components[3] = \str_replace(' ', '+', $components[3]);
			$sourceBinary = \base64_decode($components[3], true);
		}

		if ($sourceBinary === false || $sourceBinary === '') {
			throw new InputNotFoundException();
		}

		if (\strlen($sourceBinary) > $this->getMaximumSizeInBytes()) {
			throw new FileTooLargeException();
		}

		$targetFile = $this->describeTargetFile($extension);
		$bytesWritten = \file_put_contents($targetFile->getPath(), $sourceBinary);

		if ($bytesWritten === false) {
			throw new TargetFileWriteError();
		}

		return $targetFile;
	}

}
