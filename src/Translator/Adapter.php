<?php 
namespace Qing\Lib\Translator;

abstract class Adapter{
	
	public function __construct(array $options){
		
	}
	public function t($translateKey, $placeholders = null,$domain=null)
	{
	    return $this->query($translateKey, $placeholders,$domain);
	}

	public function _($translateKey, $placeholders = null,$domain=null)
	{
	    return $this->query($translateKey, $placeholders,$domain);
	}
    /**
     * 
     * @var \Qing\Lib\Cache
     */
    protected $_cacheEngine;
    public function setCacheEngine($cache){
        $this->_cacheEngine = $cache;
    }
	abstract public function query($translateKey, $placeholders = null,$domain=null);
}
