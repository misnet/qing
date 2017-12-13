<?php

namespace Qing\Lib\Queue;

/**
 * 消息队列抽象类
 * 
 * @author dony
 *        
 */
abstract class QueueAbstract implements QueueInterface {
	protected $_options;
	protected $_queues = array ();
	/**
	 * (non-PHPdoc)
	 * 
	 * @see \Qing\Lib\Queue\QueueInterface::setOption()
	 */
	public function setOption($key, $value) {
		$this->_options [$key] = $value;
	}
	public function createQueue($name) {
		$this->_options ['name'] = $name;
	}
	abstract public function put($message);
	abstract public function receive($num = 10);
	abstract public function deleteQueue($name);
	abstract public function delete($message);
	/**
	 * 序列化
	 * 
	 * @param unknown $data        	
	 * @return string
	 */
	protected function _serialize($data) {
		if (is_numeric ( $data )) {
			return ( string ) $data;
		} else {
			return serialize ( $data );
		}
	}
	/**
	 * 反序列化
	 * 
	 * @param unknown $data        	
	 * @return NULL unknown
	 */
	protected function _unserialize($data) {
		if (is_null ( $data )) {
			return null;
		} else if (is_numeric ( $data )) {
			if (strpos ( $data, '.' ) === false) {
				$unserializedValue = ( integer ) $data;
			} else {
				$unserializedValue = ( float ) $data;
			}
			
			if (( string ) $unserializedValue !== $data) {
				$unserializedValue = $data;
			}
		} else {
			try {
				$unserializedValue = unserialize ( $data );
			} catch ( Exception $e ) {
				print_r ( $e->getMessage () );
				$unserializedValue = $data;
			}
		}
		
		return $unserializedValue;
	}
}