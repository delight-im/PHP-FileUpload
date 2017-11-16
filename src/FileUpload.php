<?php

/*
 * PHP-FileUpload (https://github.com/delight-im/PHP-FileUpload)
 * Copyright (c) delight.im (https://www.delight.im/)
 * Licensed under the MIT License (https://opensource.org/licenses/MIT)
 */

namespace Delight\FileUpload;

/** Helper for simple and convenient file uploads */
final class FileUpload extends Upload {

	/** @var string|null the name of the input that is the source for the file to be uploaded */
	private $sourceInputName;

	public function __construct() {
		parent::__construct();

		$this->sourceInputName = null;
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

}
