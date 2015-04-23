<?php
if ( class_exists( 'Bbpp_Animated_Gif' ) ) {
	class EWWWIO_GD_Editor extends Bbpp_Animated_Gif {
		public function save( $filename = null, $mime_type = null ) {
			global $ewww_debug;
//TODO: rethink these checks. should we be checking for JPEGTRAN still, or perhaps the CLOUD constant?
			if (!defined('EWWW_IMAGE_OPTIMIZER_JPEGTRAN'))
				ewww_image_optimizer_init();
			$saved = parent::save($filename, $mimetype);
			if ( ! is_wp_error( $saved ) ) {
				if ( ! $filename ) {
					$filename = $saved['path'];
				}
				ewww_image_optimizer($filename);
//				ewww_image_optimizer_aux_images_loop($filename, true);
				$ewww_debug .= "image editor (AGR gd) saved: $filename <br>";
				$image_size = filesize($filename);
				$ewww_debug .= "image editor size: $image_size <br>";
				ewww_image_optimizer_debug_log();
			}
			ewwwio_memory( __FUNCTION__ );
			return $saved;
		}
		public function multi_resize( $sizes ) {
			global $ewww_debug;
			if (!defined('EWWW_IMAGE_OPTIMIZER_JPEGTRAN'))
				ewww_image_optimizer_init();
			$metadata = parent::multi_resize($sizes);
			$ewww_debug .= "image editor (AGR gd) multi resize<br>";
			$ewww_debug .= print_r($metadata, true) . "<br>";
			$ewww_debug .= print_r($this, true) . "<br>";
			$info = pathinfo( $this->file );
			$dir = $info['dirname'];
			foreach ($metadata as $size) {
				$filename = trailingslashit( $dir ) . $size['file'];
				ewww_image_optimizer($filename);
				//ewww_image_optimizer_aux_images_loop($filename, true);
				$ewww_debug .= "image editor (AGR gd) saved: $filename <br>";
				$image_size = filesize($filename);
				$ewww_debug .= "image editor size: $image_size <br>";
			}
			ewww_image_optimizer_debug_log();
			ewwwio_memory( __FUNCTION__ );
			return $metadata;
		}
	}
} elseif (class_exists('WP_Thumb_Image_Editor_GD')) {
	class EWWWIO_GD_Editor extends WP_Thumb_Image_Editor_GD {
		protected function _save( $image, $filename = null, $mime_type = null ) {
			global $ewww_debug;
			if (!defined('EWWW_IMAGE_OPTIMIZER_JPEGTRAN'))
				ewww_image_optimizer_init();
			$saved = parent::_save($image, $filename, $mime_type);
			if ( ! is_wp_error( $saved ) ) {
				if ( ! $filename ) {
					$filename = $saved['path'];
				}
				ewww_image_optimizer($filename);
				//ewww_image_optimizer_aux_images_loop($filename, true);
				$ewww_debug .= "image editor (wpthumb GD) saved : $filename <br>";
				$image_size = filesize($filename);
				$ewww_debug .= "image editor size: $image_size <br>";
				ewww_image_optimizer_debug_log();
			}
			ewwwio_memory( __FUNCTION__ );
			return $saved;
		}
	}
} else {
	class EWWWIO_GD_Editor extends WP_Image_Editor_GD {
		protected function _save ($image, $filename = null, $mime_type = null) {
			global $ewww_debug;
			if (!defined('EWWW_IMAGE_OPTIMIZER_JPEGTRAN'))
				ewww_image_optimizer_init();
			$saved = parent::_save($image, $filename, $mime_type);
			if ( ! is_wp_error( $saved ) ) {
				if ( ! $filename ) {
					$filename = $saved['path'];
				}
				ewww_image_optimizer($filename);
				//ewww_image_optimizer_aux_images_loop($filename, true);
				$ewww_debug = "$ewww_debug image editor (gd) saved: $filename <br>";
				$image_size = filesize($filename);
				$ewww_debug = "$ewww_debug image editor size: $image_size <br>";
				ewww_image_optimizer_debug_log();
			}
			ewwwio_memory( __FUNCTION__ );
			return $saved;
		}
	}
}
if (class_exists('WP_Thumb_Image_Editor_Imagick')) {
	class EWWWIO_Imagick_Editor extends WP_Thumb_Image_Editor_Imagick {
		protected function _save( $image, $filename = null, $mime_type = null ) {
			global $ewww_debug;
			if (!defined('EWWW_IMAGE_OPTIMIZER_JPEGTRAN'))
				ewww_image_optimizer_init();
			$saved = parent::_save($image, $filename, $mime_type);
			if ( ! is_wp_error( $saved ) ) {
				if ( ! $filename ) {
					$filename = $saved['path'];
				}
				ewww_image_optimizer($filename);
				//ewww_image_optimizer_aux_images_loop($filename, true);
				$ewww_debug .= "image editor (wpthumb imagick) saved : $filename <br>";
				$image_size = filesize($filename);
				$ewww_debug .= "image editor size: $image_size <br>";
				ewww_image_optimizer_debug_log();
			}
			ewwwio_memory( __FUNCTION__ );
			return $saved;
		}
	}
} else {
	class EWWWIO_Imagick_Editor extends WP_Image_Editor_Imagick {
		protected function _save( $image, $filename = null, $mime_type = null ) {
			global $ewww_debug;
			if (!defined('EWWW_IMAGE_OPTIMIZER_JPEGTRAN'))
				ewww_image_optimizer_init();
			$saved = parent::_save($image, $filename, $mime_type);
			if ( ! is_wp_error( $saved ) ) {
				if ( ! $filename ) {
					$filename = $saved['path'];
				}
				ewww_image_optimizer($filename);
				//ewww_image_optimizer_aux_images_loop($filename, true);
				$ewww_debug .= "image editor (imagick) saved: $filename <br>";
				$image_size = filesize($filename);
				$ewww_debug .= "image editor size: $image_size <br>";
				ewww_image_optimizer_debug_log();
			}
			ewwwio_memory( __FUNCTION__ );
			return $saved;
		}
	}
}
if (class_exists('WP_Image_Editor_Gmagick')) {
	class EWWWIO_Gmagick_Editor extends WP_Image_Editor_Gmagick {
		protected function _save( $image, $filename = null, $mime_type = null ) {
			global $ewww_debug;
			if (!defined('EWWW_IMAGE_OPTIMIZER_JPEGTRAN'))
				ewww_image_optimizer_init();
			$saved = parent::_save($image, $filename, $mime_type);
			if ( ! is_wp_error( $saved ) ) {
				if ( ! $filename ) {
					$filename = $saved['path'];
				}
				ewww_image_optimizer($filename);
				//ewww_image_optimizer_aux_images_loop($filename, true);
				$ewww_debug .= "image editor (gmagick) saved : $filename <br>";
				$image_size = filesize($filename);
				$ewww_debug .= "image editor size: $image_size <br>";
				ewww_image_optimizer_debug_log();
			}
			ewwwio_memory( __FUNCTION__ );
			return $saved;
		}
	}
}
