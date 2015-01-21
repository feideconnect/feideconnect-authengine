<?php

namespace FeideConnect\Authentication;

use \Imagick;

/**
 * You initalize an accountphoto object with a base64 encoded jpeg string.
 * You will be able to get a raw hash and a generated resized profile photo on request.
 */
class AccountPhoto {


	public $raw = null;
	protected $hash = null;

	function __construct($raw) {
		$this->raw = $raw;
	}

	public function getHash() {
		if ($this->hash !== null) return $this->hash;
		$this->hash = sha1($this->raw);
		return $this->hash;
	}

	public function getPhoto() {


		// $fileraw = tempnam(sys_get_temp_dir(), 'FeideConnect-PP-Raw');
		$filegen = tempnam(sys_get_temp_dir(), 'FeideConnect-PP-Gen');


		// file_put_contents($fileraw, base64_decode($this->raw));
		// $image = new \Imagick($fileraw);
		$image = new Imagick();
		$image->readImageBlob(base64_decode($this->raw));

		$maxsize = 128;

		// Resizes to whichever is larger, width or height
		if($image->getImageHeight() <= $image->getImageWidth()) {
			// Resize image using the lanczos resampling algorithm based on width
			$image->resizeImage($maxsize, 0, Imagick::FILTER_LANCZOS, 1);
		} else {
			// Resize image using the lanczos resampling algorithm based on height
			$image->resizeImage(0, $maxsize, Imagick::FILTER_LANCZOS, 1);
		}

		// Set to use jpeg compression
		$image->setImageCompression(Imagick::COMPRESSION_JPEG);
		// Set compression level (1 lowest quality, 100 highest quality)
		$image->setImageCompressionQuality(80);
		// Strip out unneeded meta data
		$image->stripImage();
		// Writes resultant image to output directory
		// $image->writeImage('output_image_filename_and_location');
		
		$result = $image->getImageBlob();

		// Destroys Imagick object, freeing allocated resources in the process
		$image->destroy();

		return $result;

	}


}