<?php
namespace Qing\Lib;
use Phalcon\Cache\Cache as PhalconCache;
use Phalcon\Cache\AdapterFactory;
use Phalcon\Storage\SerializerFactory;
class Cache{
	/**
     * 可用的缓存引擎
     * @var array 
     */
    private static $_avaiableEngine = array('stream','memory','libmemcached','apcu','redis');
    /**
     * 默认缓存引擎
     * @var string
     */
    private static $_defaultEngine = 'stream';
    /**
     * 默认缓存生命期
     * @var integer
     */
    private $_lifetime = 172800;
    private  $_config;
    /**
     * 
     *  @var \Phalcon\Cache\Cache
     */
    private  $_cacheEngine;
    /**
     * @var
     */
    private $_option;
    private $_adapterName = 'stream';
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
        $serializerFactory = new SerializerFactory();
        $adapterFactory    = new AdapterFactory($serializerFactory);

        if(!isset($config['fast'])){
            $key = 'slow';
        }else{
            $key = 'fast';
        }
        $options =$this->_config[$key]['option'];
        $options['defaultSerializer'] = 'php';
        $this->_option = $options;
        $this->_adapterName = $this->_config[$key]['engine'];
        $adapter = $adapterFactory->newInstance($this->_config[$key]['engine'], $options);
        $this->_cacheEngine = new PhalconCache($adapter);
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
			throw new \Exception('请指定slow选项');
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
		$cacheBackend = strtolower($cacheBackend);
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
	 * @param integer $lifetime 未指定时使用配置文件指定的Lifetime，redis要永久存储则使用负数-1
	 */
	public function set($keyName,$data,$lifetime=null){
        $keyName = $this->_filterKeyName($keyName);
        if($lifetime<0 && strtolower($this->_adapterName)==='redis'){
            $this->_cacheEngine->getAdapter()->setForever($keyName,$data);
        }else{
            $this->_cacheEngine->set($keyName,$data,$lifetime);
        }

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
        $prefix = '';
        if($this->_option['prefix']){
            $prefix = $this->_option['prefix'];
        }
    	return $this->_cacheEngine->getKeys($prefix.$key);
	}
	/**
	 * 删除以$prefix开头的缓存
	 * @param string $prefix  只要前辍，不需要加*
	 */
	public function deleteKeys($prefix){
	    $prefix = $this->_filterKeyName($prefix);
		$keys = $this->queryKeys($prefix);
		if($keys){
		    $options = $this->_option;
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

    /**
     * 保存缓存的key名称规范，在windows平台也能正常
     * @param $name 缓存key名
     * @return array|mixed|string|string[]
     */
	private function _filterKeyName($name){
        return str_ireplace(':','-',$name);
    }
}
