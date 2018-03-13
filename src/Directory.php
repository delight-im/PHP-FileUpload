<?php

/*
 * PHP-FileUpload (https://github.com/delight-im/PHP-FileUpload)
 * Copyright (c) delight.im (https://www.delight.im/)
 * Licensed under the MIT License (https://opensource.org/licenses/MIT)
 */

namespace Delight\FileUpload;

/** Represents a directory or folder in the file system */
final class Directory {

	/** @var string the path of the directory */
	private $path;

	/**
	 * @param string $path the path of the directory
	 */
	public function __construct($path) {
		$path = (string) $path;
		$path = \trim($path);
		$path = \rtrim($path, '/');

		$this->path = $path;
	}

	/**
	 * Returns the path of the directory
	 *
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Returns the canonical and absolute path of the directory
	 *
	 * @return string
	 */
	public function getCanonicalPath() {
		return \realpath($this->path);
	}

	/**
	 * Returns the name of the directory
	 *
	 * @return string
	 */
	public function getName() {
		return \basename($this->path);
	}

	/**
	 * Returns whether the directory exists
	 *
	 * @return bool
	 */
	public function exists() {
		return \file_exists($this->path) && \is_dir($this->path);
	}

	/**
	 * Attempts to create the directory
	 *
	 * @param int|null $mode (optional) the file mode (permissions) as used with the `chmod` method
	 * @return bool whether the directory has successfully been created
	 */
	public function create($mode = null) {
		return $this->createInternal(false, $mode);
	}

	/**
	 * Attempts to create the directory including any missing parent directories recursively
	 *
	 * @param int|null $mode (optional) the file mode (permissions) as used with the `chmod` method
	 * @return bool whether the directory has successfully been created
	 */
	public function createRecursively($mode = null) {
		return $this->createInternal(true, $mode);
	}

	/**
	 * Attempts to delete the directory including any contents recursively
	 */
	public function deleteRecursively() {
		if ($this->exists()) {
			$entries = @\scandir($this->path);

			foreach ($entries as $entry) {
				if ($entry !== '.' && $entry !== '..') {
					$entryPath = $this->path . '/' . $entry;

					if (@\is_dir($entryPath)) {
						$this->deleteRecursively($entryPath);
					}
					else {
						@\unlink($entryPath);
					}
				}
			}

			@\rmdir($this->path);
		}
	}

	public function __toString() {
		return $this->getPath();
	}

	/**
	 * Attempts to create the directory
	 *
	 * @param bool $recursive whether missing parent directories (if any) should be created recursively
	 * @param int|null $mode (optional) the file mode (permissions) as used with the `chmod` method
	 * @return bool whether the directory has successfully been created
	 */
	private function createInternal($recursive, $mode = null) {
		if ($mode === null) {
			$mode = 0755;
		}

		return @mkdir($this->path, $mode, $recursive);
	}

}
