<?php 
namespace Qing\Lib\Queue;
/**
 * 消息队列接口类
 * @author dony
 */
interface QueueInterface{
	/**
	 * 消息进入队列
	 * @param mixed $message
	 */
	public function put($message);
	/**
	 * 接收消息
	 * @param string $name 队列名
	 * @param number $num 出列数量
	 */
	public function receive($num=10);
	/**
	 * 创建队列
	 * @param string $name 队列名
	 */
	public function createQueue($name);
	/**
	 * 删除队列
	 * @param string $name
	 */
	public function deleteQueue($name);
	/**
	 * 设置参数
	 * @param string $key 参数名
	 * @param mixed $value 值
	 */
	public function setOption($key,$value);
	/**
	 * 删除队列中某项,如果队列中存在多个相同的$data，会全部删除
	 * @param mixed $message
	 * @return 返回删除总数
	 */ 
	public function delete($message);
}