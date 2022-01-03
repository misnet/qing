<?php 
namespace Qing\Lib;
class Application{
	private static $_defaultHost = 'www';
	private static $_domainNameDir;
	private static $_config;
	private static $_domainNameAlias;
	private static $_appRootDir = '';
    /**
     * 设置域名别名
     * array('www'=>'www1|www2','img'=>'img1|img2')
     *  表示www.xxx.com有别名www1.xxx.com,www2.xxx.com,
     *  img.xxx.com域名有别名img1.xxx.com,img2.xxx.com
     * @param array $array
     */
    public static function setDomainAlias($array){
        if(!is_array($array)) $array = array();
        self::$_domainNameAlias = $array;
    }
	
//	public static function startup(ApplicationInterface $app,$di){
//		//$di     = \Phalcon\DI\FactoryDefault::getDefault();
//		$loader = new \Phalcon\Autoload\Loader();
//		//如果用phalcon module ，下面两行要注释掉
//		$app->registerAutoloaders($loader,$di);
//		$app->registerServices($di);
//
//		$app->registerRoutes($di);
//	}
	public static function setConfig($config){
		self::$_config = $config;
	}
	public static function getConfig(){
		return self::$_config;
	}
	/**
	 * 取得baseurl
	 * @param string $publicDir 当前访问的目录地址
	 * @return string
	 */
	public static function getBaseUrl($publicDir){
		$dr = isset($_SERVER['DOCUMENT_ROOT'])?$_SERVER['DOCUMENT_ROOT']:$_SERVER['ZEND_PHPUNIT_PROJECT_LOCATION'];
		
		$dr = rtrim($dr,'\\/');
		$dr = str_ireplace('\\',DIRECTORY_SEPARATOR,$dr);
		$dr = str_ireplace('/',DIRECTORY_SEPARATOR,$dr);
		$pubPath = str_ireplace('\\',DIRECTORY_SEPARATOR,$publicDir);
		$pubPath = str_ireplace('/',DIRECTORY_SEPARATOR,$pubPath);
		if(stripos(PHP_OS, 'win')!==false){
			$pubPath=strtolower($pubPath);
			$dr=strtolower($dr);
		}
		$baseurl = str_replace($dr,'',$pubPath);
		$baseurl = str_ireplace(DIRECTORY_SEPARATOR,'/',$baseurl);
		$baseurl = rtrim($baseurl,'\\/').'/';
		return $baseurl;
	}
	public static function setAppBaseDir($b){
	    self::$_appRootDir = $b;
    }
	/**
	 * 定位app目录
	 * @return Ambigous <mixed, string>
	 */
	public static function getAppDir(){
		self::_analyseDomainDir();
		if(!file_exists(self::$_appRootDir.DIRECTORY_SEPARATOR.self::$_domainNameDir)){
			self::$_domainNameDir=  self::$_defaultHost;
		}
		return self::$_domainNameDir;
	}

    /**
     * 是否是默认主目录
     * @return bool
     */
	public static function isDefault(){
	    return self::$_domainNameDir==self::$_defaultHost;
	}
	/**
	 * 区分不同主机访问定向不同的apps子目录
	 */
	private static function _analyseDomainDir(){
		if(self::$_domainNameDir){
			return;
		}
		self::$_domainNameDir = self::$_defaultHost;
		try{
			if(isset($_SERVER['HTTP_HOST'])){
				$currentHostInfo = $_SERVER['HTTP_HOST'];
				$ipaddr = ip2long($currentHostInfo);
				if($ipaddr!==false && long2ip($ipaddr)==$currentHostInfo){
					//IP地址
					return;
				}
				$cookieDomain = self::getCookieDomain();
				if(!$cookieDomain){
					return;
				}
				//转为小写
				$currentHostInfo = strtolower($currentHostInfo);
				$currentHostInfo = preg_replace('/([a-zA-Z0-9\-\.]+):(\d+)/', '$1', $currentHostInfo);
				$currentHostInfo = preg_replace('/'.$cookieDomain.'/i', '', $currentHostInfo);
				$hosts = explode('.',$currentHostInfo);
				self::$_domainNameDir = end($hosts);
                self::$_domainNameDir = self::_getRealDomain(self::$_domainNameDir);
			}
		}catch(\Exception $e){
			self::$_domainNameDir = self::$_defaultHost;
		}
	}
    /**
     * 取得别名域名对应的真实域名
     * @param string $domain1
     * @return string
     */
    static private function _getRealDomain($domain1){
        if(empty(self::$_domainNameAlias)){
            return $domain1;
        }
        $realDomain = array();
        foreach(self::$_domainNameAlias as $realDomainName=>$aliasDomainNames){
            $eachDomains = explode("|",$aliasDomainNames);
            if(in_array($domain1, $eachDomains)){
                return $realDomainName;
            }
        }
        return $domain1;
    }
	/**
	 * 自动分析当前URL地址信息，取得主域名
	 * 如当前网站若是www.xxx.com，则返回.xxx.com
	 * 如当前网站若是admin.blog.xxx.com，也返回.xxx.com，系统返回后面双点域名
	 * @return string
	 */
	static public function getCookieDomain(){
		try{
			if(array_key_exists('HTTP_HOST',$_SERVER)){
			    //IP地址时
				if(preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/',$_SERVER['HTTP_HOST'])){
					return '';
				}
				$currentHostInfo = $_SERVER['HTTP_HOST'];
				$currentHostInfo = preg_replace('/([a-zA-Z0-9\-\.]+):(\d+)/', '$1', $currentHostInfo);
				if(stripos($currentHostInfo,'.')===false){
					return '';
				}
				$hosts = explode('.',$currentHostInfo);
				$len = sizeof($hosts);
				if($len>=2){
				    //TODO:要支持更多域名请自行添加
				    preg_match('/([a-z0-9][a-z0-9\-]*?\.(?:com|cn|net|org|gov|info|la|cc|co|me|wang|io)(?:\.(?:cn|jp))?)$/is',$currentHostInfo,$match);
					if(isset($match[1])){
						return '.'.$match[1];
					}else{
						return '';
					}
				}else{
					return '';
				}
			}else{
				return '';
			}
		}catch(\Exception $e){
			return '';
		}
	}
}