<?php

namespace Qing\Lib;
/*
 * Title: Thumb.php URL: http://github.com/jamiebicknell/Thumb Author: Jamie Bicknell Twitter: @jamiebicknell
 */
class Thumbnail {
	const THUMB_CACHE = './cache/';
	const THUMB_CACHE_AGE = 86400;
	const THUMB_BROWSER_CACHE = true;
	const SHARPEN_MIN = 12;
	const SHARPEN_MAX = 28;
	const ADJUST_ORIENTATION = true;
	private $src;
	private $size;
	private $crop;
	private $trim;
	private $align;
	private $sharpen;
	private $ignore;
	private $path;
	private $bgcolor;
	private $fileInfos;
	public function init($request) {
		$this->src = isset ( $request ['src'] ) ? $request ['src'] : false;
		$this->size = isset ( $request ['size'] ) ? str_replace ( array (
				'<',
				'x' 
		), '', $request ['size'] ) != '' ? $request ['size'] : 100 : 100;
		$this->crop = isset ( $request ['crop'] ) ? max ( 0, min ( 1, $request ['crop'] ) ) : 1;
		$this->trim = isset ( $request ['trim'] ) ? max ( 0, min ( 1, $request ['trim'] ) ) : 0;
		$this->zoom = isset ( $request ['zoom'] ) ? max ( 0, min ( 1, $request ['zoom'] ) ) : 0;
		$this->align = isset ( $request ['align'] ) ? $request ['align'] : false;
		$this->sharpen = isset ( $request ['sharpen'] ) ? max ( 0, min ( 100, $request ['sharpen'] ) ) : 0;
		$this->gray = isset ( $request ['gray'] ) ? max ( 0, min ( 1, $request ['gray'] ) ) : 0;
		$this->ignore = isset ( $request ['ignore'] ) ? max ( 0, min ( 1, $request ['ignore'] ) ) : 0;
		$this->path = parse_url ( $this->src );
		$this->bgcolor = isset($request['bgcolor'])?$request['bgcolor']:'ffffff';
		$this->fixsrc ();
		$this->check ();
		$this->removeDirtyCache ();
	}
	private function fixsrc() {
		$path = $this->path;
		if (isset ( $path ['scheme'] )) {
			$base = parse_url ( 'http://' . $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'] );
			if (preg_replace ( '/^www\./i', '', $base ['host'] ) == preg_replace ( '/^www\./i', '', $path ['host'] )) {
				$base = explode ( '/', preg_replace ( '/\/+/', '/', $base ['path'] ) );
				$path = explode ( '/', preg_replace ( '/\/+/', '/', $path ['path'] ) );
				$temp = $path;
				$part = count ( $base );
				foreach ( $base as $k => $v ) {
					if ($v == $path [$k]) {
						array_shift ( $temp );
					} else {
						if ($part - $k > 1) {
							$temp = array_pad ( $temp, 0 - (count ( $temp ) + ($part - $k) - 1), '..' );
							break;
						} else {
							$temp [0] = './' . $temp [0];
						}
					}
				}
				$this->src = implode ( '/', $temp );
			}
		}
	}
	private function check() {
		if (! extension_loaded ( 'gd' )) {
			die ( 'GD extension is not installed' );
		}
		if (! is_writable ( self::THUMB_CACHE )) {
			die ( 'Cache not writable' );
		}
		if (isset ( $this->path ['scheme'] ) || ! file_exists ( $this->src )) {
			die ( 'File cannot be found' );
		}

		if (! in_array ( strtolower ( substr ( strrchr ( $this->src, '.' ), 1 ) ), array (
				'gif',
				'jpg',
				'jpeg',
				'png' 
		) )) {
			die ( 'File is not an image' );
		}

	}
	private function removeDirtyCache() {
		$file_salt = 'v1.0.3';
		$file_size = filesize ( $this->src );
		$file_time = filemtime ( $this->src );
		$file_date = gmdate ( 'D, d M Y H:i:s T', $file_time );
		$file_type = strtolower ( substr ( strrchr ( $this->src, '.' ), 1 ) );
		$file_hash = md5 ( $file_salt . ($this->src . $this->size . $this->crop . $this->trim . $this->zoom . $this->align . $this->sharpen . $this->gray . $this->ignore . $this->bgcolor) . $file_time );
		$file_temp = self::THUMB_CACHE . $file_hash . '.img.txt';
		$file_name = basename ( substr ( $this->src, 0, strrpos ( $this->src, '.' ) ) . strtolower ( strrchr ( $this->src, '.' ) ) );
		if (! file_exists ( self::THUMB_CACHE . 'index.html' )) {
			touch ( self::THUMB_CACHE . 'index.html' );
		}
		if (($fp = fopen ( self::THUMB_CACHE . 'index.html', 'r' )) !== false) {
			if (flock ( $fp, LOCK_EX )) {
				if (time () - self::THUMB_CACHE_AGE > \filemtime ( self::THUMB_CACHE . 'index.html' )) {
					$files = glob ( self::THUMB_CACHE . '*.img.txt' );
					if (is_array ( $files ) && count ( $files ) > 0) {
						foreach ( $files as $file ) {
							if (time () - self::THUMB_CACHE_AGE > \filemtime ( $file )) {
								unlink ( $file );
							}
						}
					}
					touch ( self::THUMB_CACHE . 'index.html' );
				}
				flock ( $fp, LOCK_UN );
			}
			fclose ( $fp );
		}
		$this->fileInfos ['file_temp'] = $file_temp;
		$this->fileInfos ['file_date'] = $file_date;
		$this->fileInfos ['file_hash'] = $file_hash;
		$this->fileInfos ['file_name'] = $file_name;
		$this->fileInfos ['file_size'] = $file_size;
		$this->fileInfos ['file_type'] = $file_type;
	}
	public function resize() {
		if (self::THUMB_BROWSER_CACHE && (isset ( $_SERVER ['HTTP_IF_MODIFIED_SINCE'] ) || isset ( $_SERVER ['HTTP_IF_NONE_MATCH'] ))) {
			if ($_SERVER ['HTTP_IF_MODIFIED_SINCE'] == $this->fileInfos ['file_date'] && $_SERVER ['HTTP_IF_NONE_MATCH'] == $this->fileInfos ['file_hash']) {
				header ( $_SERVER ['SERVER_PROTOCOL'] . ' 304 Not Modified' );
				die ();
			}
		}
		if (! file_exists ( $this->fileInfos ['file_temp'] )) {
			list ( $w0, $h0, $type ) = getimagesize ( $this->src );
			$data = file_get_contents ( $this->src );
			if ($this->ignore && $type == 1) {
				if (preg_match ( '/\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)/s', $data )) {
					header ( 'Content-Type: image/gif' );
					header ( 'Content-Length: ' . $this->fileInfos ['file_size'] );
					header ( 'Content-Disposition: inline; filename="' . $this->fileInfos ['file_name'] . '"' );
					header ( 'Last-Modified: ' . $this->fileInfos ['file_date'] );
					header ( 'ETag: ' . $this->fileInfos ['file_hash'] );
					header ( 'Accept-Ranges: none' );
					if (THUMB_BROWSER_CACHE) {
						header ( 'Cache-Control: max-age=604800, must-revalidate' );
						header ( 'Expires: ' . gmdate ( 'D, d M Y H:i:s T', strtotime ( '+7 days' ) ) );
					} else {
						header ( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
						header ( 'Expires: ' . gmdate ( 'D, d M Y H:i:s T' ) );
						header ( 'Pragma: no-cache' );
					}
					die ( $data );
				}
			}
			
			$oi = imagecreatefromstring ( $data );
			if (self::ADJUST_ORIENTATION && $type == 2) {
				// I know supressing errors is bad, but calling exif_read_data on invalid
				// or corrupted data returns a fatal error and there's no way to validate
				// the EXIF data before calling the function.
				$exif = @exif_read_data ( $this->src, EXIF );
				if (isset ( $exif ['Orientation'] )) {
					$degree = 0;
					$mirror = false;
					switch ($exif ['Orientation']) {
						case 2 :
							$mirror = true;
							break;
						case 3 :
							$degree = 180;
							break;
						case 4 :
							$degree = 180;
							$mirror = true;
							break;
						case 5 :
							$degree = 270;
							$mirror = true;
							$w0 ^= $h0 ^= $w0 ^= $h0;
							break;
						case 6 :
							$degree = 270;
							$w0 ^= $h0 ^= $w0 ^= $h0;
							break;
						case 7 :
							$degree = 90;
							$mirror = true;
							$w0 ^= $h0 ^= $w0 ^= $h0;
							break;
						case 8 :
							$degree = 90;
							$w0 ^= $h0 ^= $w0 ^= $h0;
							break;
					}
					if ($degree > 0) {
						$oi = imagerotate ( $oi, $degree, 0 );
					}
					if ($mirror) {
						$nm = $oi;
						$oi = imagecreatetruecolor ( $w0, $h0 );
						imagecopyresampled ( $oi, $nm, 0, 0, $w0 - 1, 0, $w0, $h0, - $w0, $h0 );
						imagedestroy ( $nm );
					}
				}
			} // endif(self::ADJUST_ORIENTATION && $type == 2)
			
			list ( $w, $h ) = explode ( 'x', str_replace ( '<', '', $this->size ) );
			$w = ($w != '') ? floor ( max ( 8, min ( 1500, $w ) ) ) : '';
			$h = ($h != '') ? floor ( max ( 8, min ( 1500, $h ) ) ) : '';
			if (strstr ( $this->size, '<' )) {
				$h = $w;
				$this->crop = 0;
				$this->trim = 1;
			} elseif (! strstr ( $this->size, 'x' )) {
				$h = $w;
			} elseif ($w == '' || $h == '') {
				$this->crop = 0;
				$this->trim = 1;
			}
			$trim_w = ($this->trim) ? 1 : ($w == '') ? 1 : 0;
			$trim_h = ($this->trim) ? 1 : ($h == '') ? 1 : 0;
			if ($this->crop) {
				$w1 = (($w0 / $h0) > ($w / $h)) ? floor ( $w0 * $h / $h0 ) : $w;
				$h1 = (($w0 / $h0) < ($w / $h)) ? floor ( $h0 * $w / $w0 ) : $h;
				if (! $this->zoom) {
					if ($h0 < $h || $w0 < $w) {
						$w1 = $w0;
						$h1 = $h0;
					}
				}
			} else {
				$w = ($w == '') ? ($w0 * $h) / $h0 : $w;
				$h = ($h == '') ? ($h0 * $w) / $w0 : $h;
				$w1 = (($w0 / $h0) < ($w / $h)) ? floor ( $w0 * $h / $h0 ) : floor ( $w );
				$h1 = (($w0 / $h0) > ($w / $h)) ? floor ( $h0 * $w / $w0 ) : floor ( $h );
				$w = floor ( $w );
				$h = floor ( $h );
				if (! $this->zoom) {
					if ($h0 < $h && $w0 < $w) {
						$w1 = $w0;
						$h1 = $h0;
					}
				}
			}
			
			// --
			$w = ($trim_w) ? (($w0 / $h0) > ($w / $h)) ? min ( $w, $w1 ) : $w1 : $w;
			$h = ($trim_h) ? (($w0 / $h0) < ($w / $h)) ? min ( $h, $h1 ) : $h1 : $h;
			if ($this->sharpen) {
				$matrix = array (
						array (
								- 1,
								- 1,
								- 1 
						),
						array (
								- 1,
								self::SHARPEN_MAX - ($this->sharpen * (self::SHARPEN_MAX - self::SHARPEN_MIN)) / 100,
								- 1 
						),
						array (
								- 1,
								- 1,
								- 1 
						) 
				);
				$divisor = array_sum ( array_map ( 'array_sum', $matrix ) );
			}
			$x = strpos ( $this->align, 'l' ) !== false ? 0 : (strpos ( $this->align, 'r' ) !== false ? $w - $w1 : ($w - $w1) / 2);
			$y = strpos ( $this->align, 't' ) !== false ? 0 : (strpos ( $this->align, 'b' ) !== false ? $h - $h1 : ($h - $h1) / 2);
			$im = imagecreatetruecolor ( $w, $h );

			$r = $g = $b = 255;
			if ($this->bgcolor && preg_match ( '/([0-9A-Za-z]+){6}/', $this->bgcolor )) {
				$r = base_convert ( substr ( $this->bgcolor, 0, 2 ), 16, 10 );
				$g = base_convert ( substr ( $this->bgcolor, 2, 2 ), 16, 10 );
				$b = base_convert ( substr ( $this->bgcolor, 4, 2 ), 16, 10 );
			}
			$bg = imagecolorallocate ( $im, $r, $g, $b);
			imagefill ( $im, 0, 0, $bg );
			// --
			switch ($type) {
				case 1 :
					imagecopyresampled ( $im, $oi, $x, $y, 0, 0, $w1, $h1, $w0, $h0 );
					if ($this->sharpen && version_compare ( PHP_VERSION, '5.1.0', '>=' )) {
						imageconvolution ( $im, $matrix, $divisor, 0 );
					}
					if ($this->gray) {
						imagefilter ( $im, IMG_FILTER_GRAYSCALE );
					}
					imagegif ( $im, $this->fileInfos ['file_temp'] );
					break;
				case 2 :
					imagecopyresampled ( $im, $oi, $x, $y, 0, 0, $w1, $h1, $w0, $h0 );
					if ($this->sharpen && version_compare ( PHP_VERSION, '5.1.0', '>=' )) {
						imageconvolution ( $im, $matrix, $divisor, 0 );
					}
					if ($this->gray) {
						imagefilter ( $im, IMG_FILTER_GRAYSCALE );
					}
					imagejpeg ( $im, $this->fileInfos ['file_temp'], 100 );
					break;
				case 3 :
					imagefill ( $im, 0, 0, imagecolorallocatealpha ( $im, 0, 0, 0, 127 ) );
					imagesavealpha ( $im, true );
					imagealphablending ( $im, false );
					imagecopyresampled ( $im, $oi, $x, $y, 0, 0, $w1, $h1, $w0, $h0 );
					if ($this->sharpen && version_compare ( PHP_VERSION, '5.1.0', '>=' )) {
						$fix = imagecolorat ( $im, 0, 0 );
						imageconvolution ( $im, $matrix, $divisor, 0 );
						imagesetpixel ( $im, 0, 0, $fix );
					}
					if ($this->gray) {
						imagefilter ( $im, IMG_FILTER_GRAYSCALE );
					}
					imagepng ( $im, $this->fileInfos ['file_temp'] );
					break;
			}
			imagedestroy ( $im );
			imagedestroy ( $oi );
		} // endif(!file_exists($this->fileInfos['file_temp']))
	}
	public function output(){
		header('Content-Type: image/' . $this->fileInfos['file_type']);
		header('Content-Length: ' . filesize($this->fileInfos['file_temp']));
		header('Content-Disposition: inline; filename="' . $this->fileInfos['file_name'] . '"');
		header('Last-Modified: ' . $this->fileInfos['file_date']);
		header('ETag: ' . $this->fileInfos['file_hash']);
		header('Accept-Ranges: none');
		if (self::THUMB_BROWSER_CACHE) {
			header('Cache-Control: max-age=604800, must-revalidate');
			header('Expires: ' . gmdate('D, d M Y H:i:s T', strtotime('+7 days')));
		} else {
			header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
			header('Expires: ' . gmdate('D, d M Y H:i:s T'));
			header('Pragma: no-cache');
		}
		readfile($this->fileInfos['file_temp']);
	}
}