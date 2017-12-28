<?php

namespace Qing\Lib\Queue\Adapter;

use Qing\Lib\RedisServer;
use Qing\Lib\Queue\QueueAbstract;
use Qing\Lib\Queue\Exception as QueueException;
/**
 * Redis消息队列
 * @author dony
 *
 */
class Redis extends QueueAbstract {
	/**
	 * Redis服务器
	 * @var \Qing\Lib\RedisServer
	 */
	protected $_redisServer;
	/**
	 * 存放队列名称的集合名称
	 * @var string
	 */
	protected $_queueListName = 'queues';
	/**
	 * Redis数据库索引最大值（库名）
	 * @var integer
	 */
	const DB_MAX_INDEX = 15;
	/**
	 * Redis数据库索引最小值（库名）
	 * @var integer
	 */
	const DB_MIN_INDEX = 0;
	/**
	 * 命名空间分隔符
	 * @var string
	 */
	protected $_namespaceSplitWord = ':';
	/**
	 * 创建实例
	 * @param \Phalcon\Config | array $option
	 */
	public function __construct($option = array()) {
		$this->_options = $this->_filterOption ( $option );
		$this->_redisServer = new RedisServer ( $this->_options ['host'], $this->_options ['port'] );
		if ($this->_options ['auth']) {
			$this->_redisServer->Auth ( $this->_options ['auth'] );
		}
		$this->_redisServer->Select ( $this->_options ['index'] );
	}
	/**
	 * 选项设置默认值
	 * 
	 * @param \Phalcon\Config|array $option        	
	 * @return Ambigous <multitype:, array, unknown>
	 */
	private function _filterOption($option) {
		if ($option instanceof \Phalcon\Config) {
			$option = $option->toArray ();
		} elseif (! is_array ( $option )) {
			$option = array ();
		}
		$defaultOption ['host'] = '127.0.0.1';
		$defaultOption ['auth'] = '';
		$defaultOption ['port'] = 6379;
		$defaultOption ['name'] = 'default';
		$defaultOption ['index'] = 0;
		$option = \Qing\Lib\Utils::arrayExtend ( $defaultOption, $option );
		if (! is_integer ( $option ['index'] )) {
			$option ['index'] = intval ( $option ['index'] );
		}
		if ($option ['index'] < self::DB_MIN_INDEX || $option ['index'] > self::DB_MAX_INDEX) {
			throw new QueueException ( '指定的Redis数据库【' . $option ['index'] . '】不存在' );
		}
		return $option;
	}
	/**
	 * (non-PHPdoc)
	 * 
	 * @see \Qing\Lib\Queue\QueueAbstract::createQueue()
	 */
	public function createQueue($name) {
		$this->_options ['name'] = $name;
		$this->_redisServer->sAdd ( $this->_queueListName, $name );
	}
	/**
	 * 取得队列名称
	 * 
	 * @return string
	 */
	protected function getQueueName() {
		return $this->_queueListName . $this->_namespaceSplitWord . 'que_' . $this->_options ['name'];
	}
	/**
	 * (non-PHPdoc)
	 * 
	 * @see \Qing\Lib\Queue\QueueAbstract::put()
	 */
	public function put($data) {
		$list = $this->_redisServer->sMembers ( $this->_queueListName );
		$queueName = $this->getQueueName ();
		if (! $list || ! in_array ( $this->_options ['name'], $list )) {
			throw new QueueException ( '队列名称无效' );
		}
		$data = $this->_serialize ( $data );
		$this->_redisServer->LPush ( $queueName, $data );
	}
	
	/**
	 * 删除队列
	 * @param unknown $name        	
	 */
	public function deleteQueue($name) {
		$this->_redisServer->sRem ( $this->_queueListName, $name );
		$this->_redisServer->Del ( $name );
	}
	/**
	 * 删除队列中某项,如果队列中存在多个相同的$data，会全部删除
	 * @param mixed $data
	 * @return 返回删除总数        	
	 */
	public function delete($data) {
		$data = $this->_serialize ( $data );
		return $this->_redisServer->LRem ( $this->getQueueName (), null, $data );
	}
	/**
	 * (non-PHPdoc)
	 * @see \Qing\Lib\Queue\QueueAbstract::receive()
	 */
	public function receive($num = 10) {
		$num = intval ( $num );
		$num || $num = 1;
		$queueName = $this->getQueueName ();
		$messages = array ();
		for($i = 0; $i < $num; $i ++) {
			$message = $this->_redisServer->RPop ( $queueName );
			$message = $this->_unserialize ( $message );
			if (! is_null ( $message )) {
				$messages [] = $message;
			}
		}
		return $messages;
	}
}