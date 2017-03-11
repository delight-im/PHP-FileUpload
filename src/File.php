<?php

/*
 * PHP-FileUpload (https://github.com/delight-im/PHP-FileUpload)
 * Copyright (c) delight.im (https://www.delight.im/)
 * Licensed under the MIT License (https://opensource.org/licenses/MIT)
 */

namespace Delight\FileUpload;

/** Represents a file in the file system */
final class File {

	/** @var Directory the directory of the file */
	private $directory;
	/** @var string the name of the file */
	private $filename;
	/** @var string|null the (filename) extension of the file */
	private $extension;

	/**
	 * @param Directory $directory the directory of the file
	 * @param string $filename the name of the file
	 * @param string|null $extension (optional) the (filename) extension of the file
	 */
	public function __construct(Directory $directory, $filename, $extension = null) {
		$this->directory = $directory;
		$this->filename = \trim((string) $filename);

		if ($extension !== null) {
			$this->extension = \trim((string) $extension);
		}
		else {
			$this->extension = null;
		}
	}

	/**
	 * Returns the directory of the file
	 *
	 * @return Directory
	 */
	public function getDirectory() {
		return $this->directory;
	}

	/**
	 * Returns the name of the file
	 *
	 * @return string
	 */
	public function getFilename() {
		return $this->filename;
	}

	/**
	 * Returns the (filename) extension of the file
	 *
	 * @return string|null
	 */
	public function getExtension() {
		return $this->extension;
	}

	/**
	 * Returns the name of the file including its extension
	 *
	 * @return string
	 */
	public function getFilenameWithExtension() {
		if ($this->extension === null) {
			return $this->filename;
		}
		else {
			return $this->filename . '.' . $this->extension;
		}
	}

	/**
	 * Returns the path of the file
	 *
	 * @return string
	 */
	public function getPath() {
		return $this->directory->getPath() . '/' . $this->getFilenameWithExtension();
	}

	/**
	 * Returns the canonical and absolute path of the file
	 *
	 * @return string
	 */
	public function getCanonicalPath() {
		return \realpath($this->getPath());
	}

	/**
	 * Returns whether the file exists
	 *
	 * @return bool
	 */
	public function exists() {
		return \file_exists($this->getPath()) && !\is_dir($this->getPath());
	}

	public function __toString() {
		return $this->getFilenameWithExtension();
	}

	/**
	 * Returns whether the specified name *may* be a valid filename
	 *
	 * @param string $name
	 * @return bool
	 */
	public static function mayNameBeValid($name) {
		return \strlen($name) > 0 && !\preg_match('/\0|\/|\\\\|:|\*|<|>|\?/', $name);
	}

}
