<?php
namespace Xicrow\PhpThumb;

/**
 * Class Thumb
 *
 * @package Xicrow\PhpThumb
 */
class Thumb {
	/**
	 * Engine to use for resizing
	 *
	 * @var string
	 */
	private static $engine = '\Xicrow\PhpThumb\Engine\GdLibrary';

	/**
	 * Default options, can be modified for class generally on bootstrap or on each call
	 *
	 * @var array
	 */
	private static $options = [
		// Full path to folder used for images given with relative path
		'path_images'     => './images',
		// Full path to folder used for thumbnails
		'path_thumbs'     => './thumbs',
		// Full path to folder used for watermarks given with relative path
		'path_watermarks' => './watermarks',
		// Full path to folder used for fonts given with relative path
		'path_fonts'      => './Fonts',
		// Resize options
		'resize'          => [
			// Width of the thumbnail (empty value to auto calculate in relation to height)
			'width'      => 500,
			// Height of the thumbnail (empty value to auto calculate in relation to width)
			'height'     => 500,
			// Method to use when resizing
			'method'     => 'fit',
			// Stretch image if smaller than given dimensions
			'stretch'    => true,
			// Trim whitespace if fit method is used
			'trim'       => false,
			// Horizontal alignment of resized image within canvas (left, center, right)
			'align_x'    => 'center',
			// Vertical alignment of resized image within canvas (top, middle, bottom)
			'align_y'    => 'middle',
			// Color used for background when not trimming fitted thumbnails ('transparent' or hex color ie. #FFFFFF)
			'background' => 'transparent',
			// Greyscale the thumbnail
			'grayscale'  => false,
		],
		// Watermark options
		'watermark'       => [
			// Image to add as watermark
			'image'     => false,
			// Width of the watermark image
			'width'     => 100,
			// Height of the watermark image
			'height'    => 100,
			// Text to add as watermark
			'text'      => false,
			// Font to use for text
			'font'      => 'Arial',
			// Font size to use for text
			'font_size' => 10,
			// Color to use for text (hex color ie. #FFFFFF)
			'color'     => '#FFFFFF',
			// Horizontal alignment of watermark within canvas (left, center, right)
			'align_x'   => 'center',
			// Vertical alignment of watermark within canvas (top, middle, bottom)
			'align_y'   => 'middle',
		],
		// Quality of the generated image
		'quality'         => 80,
	];

	/**
	 * Set the engine to use for resizing
	 *
	 * @param string $engine
	 */
	public static function setEngine($engine) {
		// Set engine to use
		self::$engine = $engine;
	}

	/**
	 * Overwrite default options
	 *
	 * @param array $options
	 */
	public static function setOptions(array $options = []) {
		// Merge options with default options
		self::$options = array_replace_recursive(self::$options, $options);
	}

	/**
	 * Resize an image
	 *
	 * @param string $image
	 * @param array  $options
	 *
	 * @return string
	 */
	public static function resize($image, array $options = []) {
		// Merge options with default options
		$options = array_replace_recursive(self::$options, $options);

		// Get engine
		$engine = false;
		if (class_exists(self::$engine)) {
			$engine = new self::$engine();
			if (!$engine instanceof EngineInterface) {
				$engine = false;
			}
		}

		if (!$engine) {
			die('\Xicrow\PhpThumb\Thumb: Invalid engine supplied');
		}

		// Get full image path
		// @todo Handle remote images
		$imagePath = $image;
		if (!file_exists($imagePath)) {
			$imagePath = rtrim($options['path_images'], DIRECTORY_SEPARATOR);
			$imagePath .= DIRECTORY_SEPARATOR;
			$imagePath .= ltrim($image, DIRECTORY_SEPARATOR);
		}
		$imagePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imagePath);

		if (!file_exists($imagePath)) {
			die('\Xicrow\PhpThumb\Thumb: Image does not exist');
		}

		// Get image path information
		$imageFolder         = pathinfo($imagePath, PATHINFO_DIRNAME);
		$imageFolderRelative = false;
		if ($imageFolder != $options['path_images'] && strpos($imageFolder, $options['path_images']) !== false) {
			$imageFolderRelative = trim(substr($imageFolder, strlen($options['path_images'])), DIRECTORY_SEPARATOR);
		}
		$imageFileName = pathinfo($imagePath, PATHINFO_FILENAME);
		$imageFileExt  = pathinfo($imagePath, PATHINFO_EXTENSION);

		// Set path to thumbnail file
		$thumbPath = rtrim($options['path_thumbs'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		if ($imageFolderRelative) {
			$thumbPath .= $imageFolderRelative . DIRECTORY_SEPARATOR;
		}
		$thumbPath .= $imageFileName . '-' . md5(json_encode($options)) . '.' . $imageFileExt;

		// If thumbnail does not already exist
		if (!file_exists($thumbPath)) {
			// Make the thumbnail engine work
			$engine->load($imagePath, $options);
			if ($options['resize'] && (!empty($options['resize']['width']) || !empty($options['resize']['height']))) {
				$engine->resize($options['resize']);
			}
			if ($options['watermark'] && (!empty($options['watermark']['image']) || !empty($options['watermark']['text']))) {
				// Check image path
				if (!empty($options['watermark']['image']) && !file_exists($options['watermark']['image'])) {
					$options['watermark']['image'] = rtrim($options['path_watermarks'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $options['watermark']['image'];
				}
				// Check font path
				if (!empty($options['watermark']['text']) && !file_exists($options['watermark']['font'])) {
					$options['watermark']['font'] = rtrim($options['path_fonts'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $options['watermark']['font'];
				}

				$engine->watermark($options['watermark']);
			}
			$engine->save($thumbPath, $options);
			unset($engine);
		}

		// Return path to thumbnail
		return $thumbPath;
	}
}
