<?php
namespace Qing\Lib;
class Utils{
    /**
     * 略写函数
     *
     * @param string $string 字串
     * @param int $sublen 截取长度
     * @param int $start 截取开始位置，默认为0
     * @param string $encoding 输入字串的编码，默认为utf-8
     * @return string
     */
    static public function shortWrite($string,$sublen,$appendStr='...',$start=0,$encoding='utf-8'){
        if ($sublen<=0){
            return $string;
        }
        if(strtoupper($encoding) == 'UTF-8'){
            $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
            preg_match_all($pa, $string, $t_string);
            if(count($t_string[0]) - $start > $sublen)
                return join('', array_slice($t_string[0], $start, $sublen)).$appendStr;
            else
                return join('', array_slice($t_string[0], $start, $sublen));
        }else{
            $start = $start*2;
            $sublen = ($sublen-1)*2;
            $strlen = strlen($string);
            $tmpstr = '';
            for($i=0; $i<$strlen; $i++){
                if($i>=$start && $i<($start+$sublen)){
                    if(ord(substr($string, $i, 1))>129)
                        $tmpstr.= substr($string, $i, 2);
                    else
                        $tmpstr.= substr($string, $i, 1);
                }
                if(ord(substr($string, $i, 1))>129)
                    $i++;
            }
            if(strlen($tmpstr)<$strlen )
                $tmpstr.= $appendStr;
            return $tmpstr;
        }
    }
    /**
     * 过滤掉危险代码
     * @param string $str
     * @return string
     */
    static public function filterDangerCode($str){
        $dangerArray = array("/<(\/?)(script|i?frame|style|html|body|title|link|meta|\?|\%)([^>]*?)>/isU",
            "/(<[^>]*)on[a-zA-Z] \s*=([^>]*>)/isU"
        );
        $safeArr = array("","\\1\\2");
        $str = preg_replace($dangerArray,$safeArr,$str);
        return $str;
    }
	/**
	 * 使用google api生成二维码
	 * @param $chl 要生成的文本、URL
	 * @param int $width 图片宽度
	 * @param string $margin 边界宽度。图片周围的边界的宽度
	 * @param string $EC_level 錯誤修正，可以使用下面的值
	 *  - L 可恢复 7% 的 QR 代码
	 *  - M 可恢复 15% 的 QR 代码
	 *  - Q 可恢复 25% 的 QR 代码
	 *  - H 可恢复 30% 的 QR 代码
	 * @return string
	 */
	static public function qrCode($chl, $width = 50, $margin = '0', $EC_level = 'M'){
		$param = array(
				'cht' => 'qr',
				'chs' => "{$width}x{$width}",
				'chld' => "{$EC_level}|{$margin}",
				'chl' => $chl,
		);
		return 'http://chart.apis.google.com/chart?'.http_build_query($param);
	}
    static public function getYestoday($format='Y-m-d'){
        return date($format,strtotime('-1 day'));
    }
    static public function getLastMonth($format){
        return date($format,strtotime('-1 month'));
    }
    static public function getLastYear($format){
        return date($format,strtotime('-1 year'));
    }
	/**
	 * 取得本周一的unix时间
	 * @param boolean $fromStart 为true时，返回的时间是那天的0:0:0，为false时返回的时间是那天的23:59:59
	 * @return integer
	 */
	static public function getThisWeekMonday($fromStart=null){
        $curWeekDay = date('N');
        if($curWeekDay>1){
            $t = strtotime("last Monday");
        }else{
            $t = time();
        }

        if(!is_null($fromStart)||$fromStart===true){
            return mktime(0,0,0,date('n',$t),date('j',$t),date('Y',$t));
        }elseif($fromStart===false){
            return mktime(23,59,59,date('n',$t),date('j',$t),date('Y',$t));
        }else{
            return $t;
        }
	}
	/**
	 * 取得本周日的unix时间
	 * @param boolean $fromStart 为true时，返回的时间是那天的0:0:0，为false时返回的时间是那天的23:59:59
	 * @return integer
	 */
	static public function getThisWeekSunday($fromStart=null){
        $curWeekDay = date('N');
        if($curWeekDay<7){
            $t =  strtotime("next Sunday");
        }else{
            $t = time();
        }

        if($curWeekDay<7){
            if(is_null($fromStart))
                return $t;
            elseif($fromStart===false){
                return mktime(23,59,59,date('n',$t),date('j',$t),date('Y',$t));
            }else{
                return mktime(0,0,0,date('n',$t),date('j',$t),date('Y',$t));
            }
        }else{
            return $t;
        }
	}
	/**
	 * 数组的继承,$array2可以从$array1继承或override里面的值
	 * @param array $array
	 * @param array $array2
	 * @return array
	 */
	static public function arrayExtend($array1, $array2) {
		if (! is_array ( $array1 ))
			return $array2;
		$merged = $array1;
		foreach ( $array2 as $key => &$value ) {
			if (is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] )) {
				$merged [$key] = self::arrayExtend( $merged [$key], $value );
			} else {
				$merged [$key] = $value;
			}
		}
	
		return $merged;
	}
	/**
	 * 对象至数组的转化
	 * @param object $obj
	 * @return array
	 */
	static public function objectToArray($obj){
		$return = null;
		if (is_object ( $obj )||is_array($obj)){
			while(list($k,$v) = each($obj)){
				$return[$k]=self::objectToArray($v);
			}
		}else{
			$return = $obj;
		}
		return $return;
	}
	static public function getRandColor($prefix='#'){
	    $seed = '1234567890abcdef';
	    $str = '';
	    for($i=1;$i<=6;$i++){
	       $index  = rand(0,sizeof($seed)-1);
	       $str.=$seed[$index];
	    }
	    return $prefix.$str;
	}
	/**
	 * 取得客户端IP
	 * @return string
	 */
	static public function getClientIp(){
		$ip = '';
		if (!empty($_SERVER['HTTP_CLIENT_IP'])){
			//check ip from share internet
			$ip=$_SERVER['HTTP_CLIENT_IP'];
		}
		else{
			if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
				$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
			}
			else{
				$ip=isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'';
			}
		}
		return $ip;
	}
	/**
	 * 删除文件夹
	 * @param string $dirPath
	 */
	public static function removeDir($dirPath){
	    if(stripos($dirPath,'../')===0||stripos($dirPath,'./')===0){
	        return;
        }
		if(is_file($dirPath)){
			 unlink($dirPath);
		} elseif(is_dir($dirPath) && $dirPath!='.' && $dirPath!='..'){
            $fh = opendir($dirPath);
            while($file = readdir($fh)){
                if($file!='.' && $file!='..'){
                    self::removeDir($dirPath.DIRECTORY_SEPARATOR.$file);
                }
            }
            @closedir($fh);
			@rmdir($dirPath);

		}
	}
    public static  function xcopy($src,$dst) {
        $dir = opendir($src);
        if(!file_exists($dst)){
            @mkdir($dst,0700,true);
        }
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    self::xcopy($src . '/' . $file,$dst . '/' . $file);
                }else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);

    }
	/**
	 * 将数字转为62进制的字串
	 * @param integer $num
	 * @param integer $base
	 * @param boolean $index
	 */
	static public function dec2any( $num, $base=62, $index=false ) {
		if (! $base ) {
			$base = strlen( $index );
		} else if (! $index ) {
			$index = substr( "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" ,0 ,$base );
		}
		$out = "";
		for ( $t = floor( log10( $num ) / log10( $base ) ); $t >= 0; $t-- ) {
			$a = floor( $num / pow( $base, $t ) );
			$out = $out . substr( $index, $a, 1 );
			$num = $num - ( $a * pow( $base, $t ) );
		}
		return $out;
	}
	/**
	 * 将62进制字串转为数字
	 * @param $num
	 * @param $base
	 * @param $index
	 */
	static public function any2dec( $num, $base=62, $index=false ) {
		if (! $base ) {
			$base = strlen( $index );
		} else if (! $index ) {
			$index = substr( "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", 0, $base );
		}
		$out = 0;
		$len = strlen( $num ) - 1;
		for ( $t = 0; $t <= $len; $t++ ) {
			$out = $out + strpos( $index, substr( $num, $t, 1 ) ) * pow( $base, $len - $t );
		}
		return $out;
	}
	/**
	 * 根据地址取图片属性信息
	 * @param string $imgurl
	 * @return Ambigous <string, mixed>
	 */
	static public function getImgProperty($imgAbsolutePath,$isRemote=false){
		$item['width'] = $item['height'] = '0';
		if(file_exists($imgAbsolutePath)||$isRemote){
			list($item['width'], $item['height'])  = getimagesize($imgAbsolutePath);
			$pathinfo = pathinfo($imgAbsolutePath);
			$item['extension'] = $pathinfo['extension'];
		}
		return $item;
	}
	static public function toTimeString($time){
	    $value['hour'] = $value['minute'] = $value['second'] = 0;
	    if($time>=3600){
	        $value['hour'] = floor($time/ 3600);
	        $time = $time % 3600;
	    }
	    if($time>=60){
	        $value['minute'] = floor($time/ 60);
	        $time = $time % 60;
	    }
	    $value['second'] = floor($time);
	    $d = sprintf('%02d',$value['minute']).':'.sprintf('%02d',$value['second']);
	    if($value['hour']>0){
            return sprintf('%02d',$value['hour']).':'.$d;
        }else{
	        return $d;
        }

	}
	/**
	 * 将一个数值转化为文件大小的单位数
	 * @param integer $val
	 * @return string
	 */
	static public function generateFileSize($val){
	    $sepa = 1;
	    $li   = 3;
	    $sep     = pow(10, $sepa);
	    $li      = pow(10, $li);
	    $retval  = $val;
	    $unit    = 'Bytes';
	
	    if ($val >= $li * 1000000)
	    {
	        $val = round( $val / (1073741824/$sep) ) / $sep;
	        $unit  = 'GB';
	    }
	    else if ($val >= $li*1000)
	    {
	        $val = round( $val / (1048576/$sep) ) / $sep;
	        $unit  = 'MB';
	    }
	    else if ($val >= $li)
	    {
	        $val = round( $val / (1024/$sep) ) / $sep;
	        $unit  = 'KB';
	    }
	    if ($unit != 'Bytes')
	    {
	        $retval = number_format($val, $sepa, '.', ',');
	    }
	    else
	    {
	        $retval = number_format($val, 0, '.', ',');
	    }
	    return $retval.$unit;
	    //return array($retval, $unit);
	}
	static public function isPicture($file){
	    $finfo = new \finfo(FILEINFO_MIME_TYPE);
	    return !(false === $ext = array_search(
	        $finfo->file($file),
	        array(
	            'jpg' => 'image/jpeg',
	            'png' => 'image/png',
	            'gif' => 'image/gif',
	        ),
	        true
	    ));
	}
	/**
	 * 
	 * @param unknown $file
	 * @return 
	 */
	static public function getFileExtendName($filename){
	    return strrchr($filename, ".");
	}
	/**
	 * 手机号保密
	 */
	static public function secreteMobile($mobile){
	    $start = 3;
	    $end   = 4;
	    if(strlen($mobile)<=3){
	        $start = 1;
	        $pattern = '/^([\d]{'.$start.'})(\d+)$/is';
	    }else if(strlen($mobile)<=7){
	        $start = 3;
	        $pattern = '/^([\d]{'.$start.'})(\d+)$/is';
	    }else{
	        $pattern = '/^([\d]{'.$start.'})(\d+)([\d]{'.$end.'})$/is';
	    }
	    return preg_replace_callback($pattern, function($matches){
	        $s =  $matches[1].str_repeat('*', strlen($matches[2]));
	        if(isset($matches[3])){
	            $s.=$matches[3];
	        }
	        return $s;
	    }, $mobile);
	}


    public static function findRelativePath ( $frompath, $topath ) {
        $from = explode( DIRECTORY_SEPARATOR, $frompath ); // Folders/File
        $to = explode( DIRECTORY_SEPARATOR, $topath ); // Folders/File
        $relpath = '';

        $i = 0;
        // Find how far the path is the same
        while ( isset($from[$i]) && isset($to[$i]) ) {
            if ( $from[$i] != $to[$i] ) break;
            $i++;
        }
        $j = count( $from ) - 1;
        // Add '..' until the path is the same
        while ( $i <= $j ) {
            if ( !empty($from[$j]) ) $relpath .= '..'.DIRECTORY_SEPARATOR;
            $j--;
        }
        // Go to folder from where it starts differing
        while ( isset($to[$i]) ) {
            if ( !empty($to[$i]) ) $relpath .= $to[$i].DIRECTORY_SEPARATOR;
            $i++;
        }

        // Strip last separator
        return substr($relpath, 0, -1);
    }

    /**
     * 10进制转2进制，分解为数组，象unix权限系统那样，7分解为1+3+4，5分解为1+4，6分解为2+3..
     * @param $d
     * @return array
     */
    public static function  hex2bin2Array($d){
        $bin = decbin($d);
        $index = 0;
        $data  = [];
        for($i=strlen($bin)-1;$i>=0;$i--){
            $v = substr($bin,$i,1);
            $r = $v * pow(2,$index);
            if($r>0){
                $data[] = $r;
            }
            $index++;
        }
        return $data;
    }

    public static function createDir($d){
        if(!file_exists($d)){
            @mkdir($d,0755,true);
        }
    }
}