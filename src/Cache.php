<?php
namespace Qing\Lib;
use Phalcon\Cache\Multiple;
class Cache{
	/**
     * 可用的缓存引擎
     * @var array 
     */
    private static $_avaiableEngine = array('file','memcache','libmemcached','mongo','apc','xcache','redis');
    /**
     * 默认缓存引擎
     * @var string
     */
    private static $_defaultEngine = 'file';
    /**
     * 默认缓存生命期
     * @var integer
     */
    private $_lifetime = 172800;
    /**
     * 使用多层缓存系统
     * @var boolean
     */
    private  $_useMutiLevels = true;
    private  $_config;
    /**
     * 
     *  @var \Phalcon\Cache\BackendInterface
     */
    private  $_cacheEngine;
	/**
	 * 建立缓存对象
	 * 
	 * $config['slow']['engine'] = 'file';
	 * $config['slow']['option']['cacheDir'] = '/tmp';
	 * $config['slow']['option']['lifetime'] = 86400;
	 * $config['fast']['engine'] = 'memcached';
	 * $config['fast']['option']['host'] = 'localhost';
	 * $config['fast']['option']['port'] = 11211;
	 * $config['fast']['option']['lifetime'] = 3600; //比slow小
	 * $cache = new \Qing\Lib\Cache($config);
	 * 
	 * 或
	 * $config['slow']['engine'] = 'file';
	 * $config['slow']['option']['cacheDir'] = '/tmp';
	 * $config['slow']['option']['lifetime'] = 86400;
	 * $config['fast']['engine'] = '';
	 * $cache = new \Qing\Lib\Cache($config);
	 * 
	 * 注：$config['slow']['engine']必须指定
	 * @param array $config 
	 */
	public function __construct($config=array()){
		$config = $this->_checkAndInitConfig($config);
		$this->_config = $config;
		if(!isset($config['fast'])){
			$this->_useMutiLevels = false;
		}
		if(!$this->_useMutiLevels){
			$frontendAdapter = new \Phalcon\Cache\Frontend\Data(array(
				'lifetime'=>$this->_config['slow']['option']['lifetime']
			));
			$ref = new \ReflectionClass('\\Phalcon\\Cache\\Backend\\'.$this->_config['slow']['engine']);
			$s = $this->_config['slow']['option'];
			//$this->_cacheEngine = new \Phalcon\Cache\Backend\File($frontendAdapter,$s);
			$this->_cacheEngine = $ref->newInstance($frontendAdapter, $this->_config['slow']['option']);
			
		}else{
// 			$slowFront = new \Phalcon\Cache\Frontend\Data(array(
// 				'lifetime'=>$this->_config['slow']['option']['lifetime']
// 			));
// 			$ref = new \ReflectionClass('\\Phalcon\\Cache\\Backend\\'.$this->_config['slow']['engine']);
// 			$slowEnd = $ref->newInstance($slowFront,$this->_config['slow']['option']);

			$fastFrond = new \Phalcon\Cache\Frontend\Data(array(
				'lifetime'=>$this->_config['fast']['option']['lifetime']
			));
			$ref = new \ReflectionClass('\\Phalcon\\Cache\\Backend\\'.$this->_config['fast']['engine']);
			$fastEnd = $ref->newInstance($fastFrond,$this->_config['fast']['option']);
			$this->_cacheEngine = $fastEnd;
// 			$this->_cacheEngine = new Multiple(array(
// 				$fastEnd,$slowEnd
// 			));
		}
	}
	/**
	 * 
	 * @return \Phalcon\Cache\BackendInterface
	 */
	public function getCacheEngine(){
	    return $this->_cacheEngine;
	}
	/**
	 * 检查并初始化配置
	 * @param unknown $config
	 * @throws Exception
	 */
	private function _checkAndInitConfig($config){
		if(!isset($config['slow'])){
			throw new Exception('请指定slow选项');
		}
		$config['slow'] = $this->_prepareEngineOption($config['slow']);
		if(isset($config['fast'])){
			$config['fast'] = $this->_prepareEngineOption($config['fast']);
			if($config['fast']['engine']==$config['slow']['engine']){
				unset($config['fast']);
			}
		}
		return $config;
	}
	/**
	 * 准备缓存引擎的选项
	 * @param unknown $config
	 * @return Ambigous <number, string>
	 */
	private function _prepareEngineOption($config){
		$cacheBackend = isset($config['engine'])?$config['engine']:self::$_defaultEngine;
		$cacheBackend = strtolower($cacheBackend);
		$cacheBackend = in_array($cacheBackend,self::$_avaiableEngine)?$cacheBackend:self::$_defaultEngine;
		$cacheBackend = ucfirst($cacheBackend);
		$config['engine'] = $cacheBackend;
		if(!isset($config['option']['lifetime'])||!is_int($config['option']['lifetime'])||$config['option']['lifetime']<0){
			$config['option']['lifetime'] = $this->_lifetime;
		}
		$config = $this->_prepareOptionForFileEngine($config);
		return $config;
	}
	/**
	 * 取得缓存
	 * @param string $keyName 缓存key
	 * @return \Phalcon\Cache\mixed
	 */
	/**
	 * 根据$keyName取缓存数据，如果没取到，则从$saveCacheCallback的回调函数中生成缓存再取回
	 * 例：
	 *
	 * $cache->get($keyName,array('func'=>array('TestClass','save'),'param'=>array('Dony')));
	 *
	 * @param string $keyName 缓存ID，不写则由系统随机生成
	 * @param array $saveCacheCallback 缓存生成时的回调函数，数组[func,param]
	 *              $saveCacheCallback['func']表示执行的主体函数，如果是类则是array(类对象,方法名称)
	 *              $saveCacheCallback['param']表示传入的参数,用array(参数1,参数2,...)
	 * @return mixed
	 */
	public function get($keyName,$saveCacheCallback=null){
        $keyName = $this->_filterKeyName($keyName);
		$data =  $this->_cacheEngine->get($keyName);
		if(isset($saveCacheCallback['func'])){
			if(!isset($saveCacheCallback['param'])||!is_array($saveCacheCallback['param'])){
				$callback['param'] = array();
			}
			$data = call_user_func_array($saveCacheCallback['func'], $saveCacheCallback['param']);
			$this->set($keyName,$data);
		}
		return $data;
	}
	/**
	 * 保存缓存
	 * @param string $keyName
	 * @param mixed $data 要缓存的数据
	 * @param integer $lifetime
	 */
	public function set($keyName,$data,$lifetime=null){
        $keyName = $this->_filterKeyName($keyName);
		$this->_cacheEngine->save($keyName,$data,$lifetime);
	}
	/**
	 * 删除缓存
	 * @param string $keyName 缓存key
	 */
	public function delete($keyName){
        $keyName = $this->_filterKeyName($keyName);
		$this->_cacheEngine->delete($keyName);
	}
	/**
	 * 查到以$key开头的缓存
	 * @param unknown $key
	 */
	public function queryKeys($key){
        $key = $this->_filterKeyName($key);
	    $options = $this->_cacheEngine->getOptions();
    	return $this->_cacheEngine->queryKeys($options['prefix'].$key);
	}
	/**
	 * 删除以$prefix开头的缓存
	 * @param string $prefix  只要前辍，不需要加*
	 */
	public function deleteKeys($prefix){
	    $prefix = $this->_filterKeyName($prefix);
		$keys = $this->queryKeys($prefix);
		if($keys){
		    $options = $this->_cacheEngine->getOptions();
			foreach($keys as $key){
				$noPrefixKey = str_replace($options['prefix'], '', $key);
				$this->_cacheEngine->delete($noPrefixKey);
				
			}
		}
	}
	/**
	 * 缓存到文件时必要的选项初始化
	 * @param array $config
	 * @return array
	 */
	private function _prepareOptionForFileEngine($config){
		if(strtolower($config['engine'])=='file'){
			if(!isset($config['option']['cacheDir']) || !is_writable($config['option']['cacheDir'])){
				$config['option']['cacheDir'] = '/tmp';
				if(!file_exists($config['option']['cacheDir'])){
					mkdir($config['option']['cacheDir'],0700,true);
				}
			}
		}
		return $config;
	}
	private function _filterKeyName($name){
        if($this->_config['slow']['engine']=='file' && stripos(PHP_OS, 'win')!==false){
            $name = str_ireplace(':','__',$name);
        }
        return $name;
    }
}
