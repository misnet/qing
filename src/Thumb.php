<?php
namespace Qing\Lib;
class Thumb {
	/*
	 * number of files to store before clearing cache
	 */
	const CACHE_SIZE = 250;
	/**
	 * maximum number of files to delete on each cache clear
	 */
	const CACHE_CLEAR = 5;
	/**
	 */
	const VERSION = '1.19';
	/**
	 * cache directory
	 */
	const DIRECTORY_CACHE = './cache';
	/**
	 * maximum image width
	 * 
	 * @var integer
	 */
	const MAX_WIDTH = 1000;
	const MAX_HEIGHT = 1000;
	const CACHE_USE = true;
	/**
	 * allow external website (override security precaution)
	 * 
	 * @var boolean
	 */
	const ALLOW_EXTERNAL = false;
	/**
	 * 决定是
	 * 
	 * @var unknown_type
	 */
	const IMAGICK_USE = false;
	private $_allowedSites = array ();
	private $_useImagick = false;
	private $_src = '';
	private $_mimetype = '';
	private $_cacheFile = '';
	private $_srcWidth;
	private $_srcHeight;
	private $_isZoomCenter;
	private $_bgcolor;
	private $_align;
	private $_thumbFrameWidth;
	private $_thumbFrameHeight;
	private $_thumbWidth;
	private $_thumbHeight;
	private $_quality = 90;
	private $_dstX = 0;
	private $_dstY = 0;
	private $_srcX = 0;
	private $_srcY = 0;
	private $_done = '.done';
	private $_imageObject = false;
	private $_httprequest = true;
	/**
	 * 用于非http请求
	 * @var unknown
	 */
	private $_propData = array();
	public function __construct($data=array()) {
		$this->_propData = $data;
	}
	public function init() {
		ini_set ( 'memory_limit', '50M' );
		$src = $this->_request ( 'src', '' );
		if ($src == '' || strlen ( $src ) <= 3) {
			$this->_displayError ( 'no image specified' );
		}
		$src = rawurldecode ( $src );
		$this->_src = $this->_cleanSource ( $src );
		$this->_src = realpath ( $this->_src );
		$this->_src = realpath ( $this->_src );
		$this->_mimetype = $this->_mimetype ( $this->_src );
		// check to see if this image is in the cache already
		// if already cached then display the image and die
		$this->_checkCache ();
		// cache doesn't exist and then process everything
		// check to see if GD function exist
		if ($this->_useImagick && ! extension_loaded ( 'imagick' )) {
			$this->_displayError ( 'Please Install Imagick Library' );
		} elseif (! $this->_useImagick && ! function_exists ( 'imagecreatetruecolor' )) {
			$this->_displayError ( 'Please Install  GD Library' );
		}
		
		$this->_handleRequest ();
	}
	public function initNotHttpRequest(){
		$this->_httprequest = false;
		$this->init();
	}
	public function addAllowSite($site) {
		$this->_allowedSites [] = $site;
	}
	public function useImagick($bol) {
		$this->_useImagick = $bol;
	}
	private function _getImageInfo() {
		if ($this->_useImagick) {
			$imageInfo = $this->_imageObject->getimagepage ();
		} else {
			$imageInfo ['width'] = imagesx ( $this->_imageObject );
			$imageInfo ['height'] = imagesy ( $this->_imageObject );
		}
		$this->_srcWidth = $imageInfo ['width'];
		$this->_srcHeight = $imageInfo ['height'];
	}
	public function resize() {
		
		if (file_exists ( $this->_src )) {
			$this->_imageObject = $this->_openImage ( $this->_mimetype, $this->_src );
			if ($this->_imageObject === false) {
				$this->_displayError ( 'Unable to open image : ' . $this->_src );
			}
			$this->_getImageInfo ();
			$this->_caculate ();
			$this->_thumbWidth = $this->_thumbFrameWidth;
			$this->_thumbHeight = $this->_thumbFrameHeight;
			$src_w = $this->_srcWidth;
			$src_h = $this->_srcHeight;
			$cmp_x = $this->_srcWidth / $this->_thumbFrameWidth;
			$cmp_y = $this->_srcHeight / $this->_thumbFrameHeight;
			if ($this->_isZoomCenter) {
				if ($this->_useImagick) {
					$this->_imageObject->cropthumbnailimage ( $this->_thumbWidth, $this->_thumbHeight );
				} else {
					
					$canvas = imagecreatetruecolor ( $this->_thumbFrameWidth, $this->_thumbFrameHeight );
					imagealphablending ( $canvas, false );
					// Create a new transparent color for image
					$r = $g = $b = 0;
					if ($this->_bgcolor && preg_match ( '/([0-9A-Za-z]+){6}/', $this->_bgcolor )) {
						$r = base_convert ( substr ( $this->_bgcolor, 0, 2 ), 16, 10 );
						$g = base_convert ( substr ( $this->_bgcolor, 2, 2 ), 16, 10 );
						$b = base_convert ( substr ( $this->_bgcolor, 4, 2 ), 16, 10 );
					}
					$color = imagecolorallocatealpha ( $canvas, $r, $g, $b, 127 );
					// Completely fill the background of the new image with allocated color.
					imagefill ( $canvas, 0, 0, $color );
					// Restore transparency blending
					imagesavealpha ( $canvas, true );
					imagecopyresampled ( $canvas, $this->_imageObject, $this->_dstX, $this->_dstY, $this->_srcX, $this->_srcY, $this->_thumbWidth, $this->_thumbHeight, $src_w, $src_h );
					$this->_imageObject = $canvas;
				}
			} else {
				if ($this->_useImagick) {
					$image2 = new \Imagick ();
					$draw = new \ImagickDraw ();
					if ($this->_bgcolor)
						$bg = $this->_bgcolor;
					else
						$bg = 'transparent';
					$pixel = new \ImagickPixel ( '#' . $bg );
					$image2->newImage ( $this->_thumbFrameWidth, $this->_thumbFrameHeight, $pixel );
					$this->_imageObject->scaleImage ( $this->_thumbFrameWidth, $this->_thumbFrameHeight, true );
					$newImageInfo = $this->_imageObject->getimagepage ();
					$image2->compositeimage ( $this->_imageObject, \Imagick::COMPOSITE_OVER, ($this->_thumbFrameWidth - $newImageInfo ['width']) / 2, ($this->_thumbFrameHeight - $newImageInfo ['height']) / 2 );
					$image2->setimagepage ( $this->_thumbFrameWidth, $this->_thumbFrameHeight, 0, 0 );
					$this->_imageObject = $image2;
				} else {
					$canvas = imagecreatetruecolor ( $this->_thumbFrameWidth, $this->_thumbFrameHeight );
					imagealphablending ( $canvas, false );
					// Create a new transparent color for image
					$r = $g = $b = 0;
					if ($this->_bgcolor && preg_match ( '/([0-9A-Za-z]+){6}/', $this->_bgcolor )) {
						$r = base_convert ( substr ( $this->_bgcolor, 0, 2 ), 16, 10 );
						$g = base_convert ( substr ( $this->_bgcolor, 2, 2 ), 16, 10 );
						$b = base_convert ( substr ( $this->_bgcolor, 4, 2 ), 16, 10 );
					}
					$color = imagecolorallocatealpha ( $canvas, $r, $g, $b, 127 );
					// Completely fill the background of the new image with allocated color.
					imagefill ( $canvas, 0, 0, $color );
					// Restore transparency blending
					imagesavealpha ( $canvas, true );
					if ($this->_thumbWidth && ($this->_srcWidth < $this->_srcHeight)) {
						$this->_thumbWidth = ($this->_thumbHeight / $this->_srcHeight) * $this->_srcWidth;
						$this->_dstX = round ( ($this->_thumbFrameWidth - $this->_thumbWidth) / 2 );
					} else {
						$this->_thumbHeight = ($this->_thumbWidth / $this->_srcWidth) * $this->_srcHeight;
						$this->_dstY = round ( ($this->_thumbFrameHeight - $this->_thumbHeight) / 2 );
					}
					imagecopyresampled ( $canvas, $this->_imageObject, $this->_dstX, $this->_dstY, $this->_srcX, $this->_srcY, $this->_thumbWidth, $this->_thumbHeight, $this->_srcWidth, $this->_srcHeight );
					$this->_imageObject = $canvas;
				}
			}
			// output image to browser based on mime type
			$this->_showImage ( $this->_imageObject );
			$this->_cleanCache ();
			
			// remove image from memory
			// imagedestroy ($canvas);
			
			// if not in cache then clear some space and generate a new file
			//clean_cache ();
			
			die ();
		} else {
			
			if (strlen ( $this->_src )) {
				$this->_displayError ( 'image ' . $this->_src . ' not found' );
			} else {
				$this->_displayError ( 'no source specified' );
			}
		}
	}
	private function _handleRequest() {
		$this->_thumbFrameWidth = ( int ) abs ( $this->_request ( 'w', 0 ) );
		$this->_thumbFrameHeight = ( int ) abs ( $this->_request ( 'h', 0 ) );
		$this->_isZoomCenter = ( int ) $this->_request ( 'zc', 1 );
		$this->_quality = ( int ) abs ( $this->_request ( 'q', 90 ) );
		// $this->_align = $this->_request ( 'a', 'c' );
		// $this->_far = ( bool ) $this->_request ( 'far', 0 );
		$this->_bgcolor = $this->_request ( 'bg', 'ffffff' );
	}
	private function _caculate() {
		
		// set default width and height if neither are set already
		if ($this->_thumbFrameWidth == 0 && $this->_thumbFrameHeight == 0) {
			$this->_thumbFrameWidth = 100;
			$this->_thumbFrameHeight = 100;
		}
		// ensure size limits can not be abused
		$this->_thumbFrameWidth = min ( $this->_thumbFrameWidth, self::MAX_WIDTH );
		$this->_thumbFrameHeight = min ( $this->_thumbFrameHeight, self::MAX_HEIGHT );
		// 不指定宽高时重新计算宽与高
		if ($this->_thumbFrameWidth && ! $this->_thumbFrameHeight) {
			$this->_thumbFrameHeight = floor ( $this->_srcHeight * ($this->_thumbFrameWidth / $this->_srcWidth) );
		} else if ($this->_thumbFrameHeight && ! $this->_thumbFrameWidth) {
			$this->_thumbFrameWidth = floor ( $this->_srcWidth * ($this->_thumbFrameHeight / $this->_srcHeight) );
		}
	}
	
	/**
	 *
	 * @param <type> $mime_type        	
	 * @param <type> $src        	
	 * @return <type>
	 */
	private function _openImage($mime_type, $src) {
		if ($this->_useImagick) {
			return new \Imagick ( $src );
		} else {
			$mime_type = strtolower ( $mime_type );
			
			if (stristr ( $mime_type, 'gif' )) {
				
				$image = imagecreatefromgif ( $src );
			} elseif (stristr ( $mime_type, 'jpeg' )) {
				
				$image = imagecreatefromjpeg ( $src );
			} elseif (stristr ( $mime_type, 'png' )) {
				
				$image = imagecreatefrompng ( $src );
			}
			
			return $image;
		}
	}
	private function _displayError($errorString) {
		// header ( 'HTTP/1.1 400 Bad Request' );
		echo '<pre>' . htmlentities ( $errorString );
		echo '<br />Query String : ' . htmlentities ( $_SERVER ['QUERY_STRING'] );
		echo '<br />QingThumb version : ' . self::VERSION . '</pre>';
		die ();
	}
	private function _request($property, $default = 0) {
		if($this->_httprequest){
			if (isset ( $_GET [$property] )) {
				return $_GET [$property];
			} else {
				return $default;
			}
		}else{
			if(isset($this->_propData[$property])){
				return $this->_propData[$property];
			}else{
				return $default;
			}
		}
	}
	private function _cleanSource($src) {
		$host = str_replace ( 'www.', '', $_SERVER ['HTTP_HOST'] );
		$regex = "/^((ht|f)tp(s|):\/\/)(www\.|)" . $host . "/i";
		
		// $src = preg_replace ( $regex, '', $src );
		$src = strip_tags ( $src );
		$src = str_replace ( ' ', '%20', $src );
		$src = $this->_checkExternal ( $src );
		
		// remove slash from start of string
		if (strpos ( $src, '/' ) === 0) {
			$src = substr ( $src, - (strlen ( $src ) - 1) );
		}
		
		// don't allow users the ability to use '../'
		// in order to gain access to files below document root
		$src = preg_replace ( "/\.\.+\//", "", $src );
		
		// get path to image on file system
		$src = $this->_getDocumentRoot ( $src ) . '/' . $src;
		return $src;
	}
	/**
	 *
	 * @global array $allowedSites
	 * @param string $src        	
	 * @return string
	 */
	private function _checkExternal($src) {
		if (stristr ( $src, 'http://' ) !== false) {
			
			$url_info = parse_url ( $src );
			// convert youtube video urls
			// need to tidy up the code
			
			// check allowed sites (if required)
			if (self::ALLOW_EXTERNAL) {
				
				$isAllowedSite = true;
			} else {
				
				$isAllowedSite = false;
				foreach ( $this->_allowedSites as $site ) {
					// $site = '/' . addslashes ($site) . '/';
					if (stristr ( $url_info ['host'], $site ) !== false) {
						$isAllowedSite = true;
					}
				}
			}
			
			// if allowed
			if ($isAllowedSite) {
				
				$fileDetails = pathinfo ( $src );
				$ext = strtolower ( $fileDetails ['extension'] );
				
				$filename = md5 ( $src );
				$local_filepath = self::DIRECTORY_CACHE . '/' . $filename . '.' . $ext;
				
				if (! file_exists ( $local_filepath )) {
					
					if (function_exists ( 'curl_init' )) {
						
						$fh = fopen ( $local_filepath, 'w' );
						$ch = curl_init ( $src );
						
						curl_setopt ( $ch, CURLOPT_TIMEOUT, 15 );
						curl_setopt ( $ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1" );
						curl_setopt ( $ch, CURLOPT_URL, $src );
						curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
						curl_setopt ( $ch, CURLOPT_HEADER, 0 );
						curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
						curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0' );
						curl_setopt ( $ch, CURLOPT_FILE, $fh );
						
						if (curl_exec ( $ch ) === FALSE) {
							if (file_exists ( $local_filepath )) {
								unlink ( $local_filepath );
							}
							$this->_displayError ( 'error reading file ' . $src . ' from remote host: ' . curl_error ( $ch ) );
						}
						// 根据http状态码判断是否有收到文件
						if (curl_getinfo ( $ch, CURLINFO_HTTP_CODE ) == 404) {
							if (file_exists ( $local_filepath )) {
								unlink ( $local_filepath );
							}
							$this->_displayError ( 'file:' . $src . ' does not exist.' );
						}
						curl_close ( $ch );
						fclose ( $fh );
						file_put_contents ( $local_filepath . $this->_done, '1' );
					} else {
						
						if (! $img = file_get_contents ( $src )) {
							$this->_displayError ( 'remote file for ' . $src . ' can not be accessed. It is likely that the file permissions are restricted' );
						}
						
						if (file_put_contents ( $local_filepath, $img ) == FALSE) {
							$this->_displayError ( 'error writing temporary file' );
						}
					}
					
					if (! file_exists ( $local_filepath )) {
						$this->_displayError ( 'local file for ' . $src . ' can not be created' );
					}
				}
				
				$src = $local_filepath;
			} else {
				
				$this->_displayError ( 'remote host "' . $url_info ['host'] . '" not allowed' );
			}
		}
		
		return $src;
	}
	/**
	 *
	 * @param <type> $src        	
	 * @return string
	 */
	private function _getDocumentRoot($src) {
		
		// check for unix servers
		if (file_exists ( $_SERVER ['DOCUMENT_ROOT'] . '/' . $src )) {
			return $_SERVER ['DOCUMENT_ROOT'];
		}
		
		// check from script filename (to get all directories to timthumb location)
		$parts = array_diff ( explode ( '/', $_SERVER ['SCRIPT_FILENAME'] ), explode ( '/', $_SERVER ['DOCUMENT_ROOT'] ) );
		$path = $_SERVER ['DOCUMENT_ROOT'];
		foreach ( $parts as $part ) {
			$path .= '/' . $part;
			if (file_exists ( $path . '/' . $src )) {
				return $path;
			}
		}
		
		// the relative paths below are useful if timthumb is moved outside of document root
		// specifically if installed in wordpress themes like mimbo pro:
		// /wp-content/themes/mimbopro/scripts/timthumb.php
		$paths = array (
				"./",
				"../",
				"../../",
				"../../../",
				"../../../../",
				"../../../../../" 
		);
		
		foreach ( $paths as $path ) {
			if (file_exists ( $path . $src )) {
				return $path;
			}
		}
		
		// special check for microsoft servers
		if (! isset ( $_SERVER ['DOCUMENT_ROOT'] )) {
			$path = str_replace ( "/", "\\", $_SERVER ['ORIG_PATH_INFO'] );
			$path = str_replace ( $path, '', $_SERVER ['SCRIPT_FILENAME'] );
			
			if (file_exists ( $path . '/' . $src )) {
				return $path;
			}
		}
		$this->_displayError ( 'file not found ' . $src, ENT_QUOTES );
	}
	/**
	 * determine the file mime type
	 *
	 * @param <type> $file        	
	 * @return <type>
	 */
	private function _mimetype($file) {
		if (! file_exists ( $file . $this->_done )) {
			sleep ( 1 );
		}
		$file_infos = getimagesize ( $file );
		$mime_type = $file_infos ['mime'];
		// use mime_type to determine mime type
		if (! preg_match ( "/jpg|jpeg|gif|png/i", $mime_type )) {
			$this->_displayError ( 'Invalid src mime type: ' . $mime_type );
		}
		return $mime_type;
	}
	
	/**
	 */
	private function _checkCache() {
		if (self::CACHE_USE) {
			
			if (! $this->_showCacheFile ( $this->_mimetype )) {
				if (! file_exists ( self::DIRECTORY_CACHE )) {
					mkdir ( self::DIRECTORY_CACHE );
					chmod ( self::DIRECTORY_CACHE, 0777 );
				}
			}
		}
	}
	
	/**
	 *
	 * @param <type> $mime_type        	
	 * @return <type>
	 */
	private function _showCacheFile() {
		
		// use browser cache if available to speed up page load
		if (isset ( $_SERVER ['HTTP_IF_MODIFIED_SINCE'] )) {
			if (strtotime ( $_SERVER ['HTTP_IF_MODIFIED_SINCE'] ) < strtotime ( 'now' )) {
				header ( 'HTTP/1.1 304 Not Modified' );
				die ();
			}
		}
		
		$cache_file = $this->_getCacheFile ();
		
		if (file_exists ( $cache_file )) {
			
			// change the modified headers
			$gmdate_expires = gmdate ( 'D, d M Y H:i:s', strtotime ( 'now +10 days' ) ) . ' GMT';
			$gmdate_modified = gmdate ( 'D, d M Y H:i:s' ) . ' GMT';
			
			// send content headers then display image
			header ( 'Content-Type: ' . $this->_mimetype );
			header ( 'Accept-Ranges: bytes' );
			header ( 'Last-Modified: ' . $gmdate_modified );
			header ( 'Content-Length: ' . filesize ( $cache_file ) );
			header ( 'Cache-Control: max-age=864000, must-revalidate' );
			header ( 'Expires: ' . $gmdate_expires );
			
			if (! @readfile ( $cache_file )) {
				$content = file_get_contents ( $cache_file );
				if ($content != FALSE) {
					echo $content;
				} else {
					display_error ( 'cache file could not be loaded' );
				}
			}
			
			die ();
		}
		
		return FALSE;
	}
	/**
	 *
	 * @staticvar string $cache_file
	 * @param <type> $mime_type        	
	 * @return string
	 */
	private function _getCacheFile() {
		$file_type = '.png';
		
		if (stristr ( $this->_mimetype, 'jpeg' )) {
			$file_type = '.jpg';
		}
		
		if (! $this->_cacheFile) {
			// filemtime is used to make sure updated files get recached
			$this->_cacheFile = self::DIRECTORY_CACHE . '/' . md5 ( $_SERVER ['QUERY_STRING'] . self::VERSION . filemtime ( $this->_src ) ) . $file_type;
		}
		
		return $this->_cacheFile;
	}
	/**
	 *
	 * @global <type> $quality
	 * @param <type> $mime_type        	
	 * @param <type> $image_resized        	
	 */
	private function _showImage($image_resized) {
		
		// check to see if we can write to the cache directory
		$cache_file = $this->_getCacheFile ();
		if ($this->_useImagick) {
			$image_resized->writeimages ( $cache_file, true );
		} else {
			if (stristr ( $this->_mimetype, 'jpeg' )) {
				imagejpeg ( $image_resized, $cache_file, $this->_quality );
			} else {
				imagepng ( $image_resized, $cache_file, floor ( $this->_quality * 0.09 ) );
			}
			imagedestroy ( $image_resized );
		}
		$this->_showCacheFile ();
	}
	/**
	 * clean out old files from the cache
	 * you can change the number of files to store and to delete per loop in the defines at the top of the code
	 *
	 * @return <type>
	 */
	private function _cleanCache() {
		
		// add an escape
		// Reduces the amount of cache clearing to save some processor speed
		if (rand ( 1, 100 ) > 10) {
			return true;
		}
		
		flush ();
		
		$files = glob ( self::DIRECTORY_CACHE . '/*', GLOB_BRACE );
		
		if (count ( $files ) > self::CACHE_SIZE) {
			
			$yesterday = time () - (24 * 60 * 60);
			
			usort ( $files, array($this,'filemtime_compare') );
			$i = 0;
			
			foreach ( $files as $file ) {
				
				$i ++;
				
				if ($i >= CACHE_CLEAR) {
					return;
				}
				
				if (@filemtime ( $file ) > $yesterday) {
					return;
				}
				
				if (file_exists ( $file )) {
					unlink ( $file );
				}
			}
		}
	}

	/**
	 * compare the file time of two files
	 *
	 * @param <type> $a
	 * @param <type> $b
	 * @return <type>
	 */
	public function filemtime_compare($a, $b) {
		$break = explode ( '/', $_SERVER ['SCRIPT_FILENAME'] );
		$filename = $break [count ( $break ) - 1];
		$filepath = str_replace ( $filename, '', $_SERVER ['SCRIPT_FILENAME'] );
	
		$file_a = realpath ( $filepath . $a );
		$file_b = realpath ( $filepath . $b );
	
		return filemtime ( $file_a ) - filemtime ( $file_b );
	}
}