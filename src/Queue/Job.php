<?php
namespace Qing\Lib\Queue;
class Job implements \Serializable{
	protected $_data;
	/**
	 * 设置任务执行的类名或对象实例
	 * @param mixed $cls 可以是对象或一个字符串
	 */
	public function setJobObject($cls){
		$this->_data['object'] = $cls;
	}
	public function setJobEval($str){
        $this->_data['evalString'] = $str;
    }
	public function getJobObject(){
        return $this->_data['object'];
	}
	public function serialize(){
		return serialize($this->_data);
	}
	public function unserialize($data){
		$this->_data = unserialize($data);
	}
	/**
	 * 设置任务执行的方法名
	 * @param string $act
	 */
	public function setJobAction($act){
		$this->_data['action'] = $act;
	}
	public function getJobAction(){
		return $this->_data['action'];
	}
	/**
	 * 设置回调函数与参数
	 * @param unknown $callback
	 * @param unknown $params
	 * @param boolean $jobResultAsParam Job执行结果是否放到自动加到参数中
	 */
	public function setCallbackAndParams($callback,$params=array(),$jobResultAsParam=false){
		$this->_data['callback'] = $callback;
		$this->_data['callback_params'] = $params;
		$this->_data['job_result_as_param'] = $jobResultAsParam;
	}

	/**
	 * 取得回调函数
	 */
	public function getCallback(){
		return $this->_data['callback'];
	}
	/**
	 * 取得回调函数的参数
	 */
	public function getCallbackParams(){
		return $this->_data['callback_params'];
	}
	public function addCallbackParam($param){
		if($this->_data['callback_params'] && !is_array($this->_data['callback_params'])){
			$this->_data['callback_params'] = array($this->_data['callback_params']);
		}
		$this->_data['callback_params'][] = $param;
	}
	/**
	 * 设置任务执行对象或类的传参
	 * @param array $p
	 */
	public function setJobParameters($p){
		if(!is_array($p)){
			$p = array($p);
		}
		$this->_data['parameters'] = $p;
	}
	/**
	 * 增加重试次数
	 */
	public function addRetryTime(){
		if(!isset($this->_data['retrytime'])){
			$this->_data['retrytime'] = 0;
		}
		$this->_data['retrytime']++;
	}
	/**
	 * 取得重试次数
	 * @return integer
	 */
	public function getRetryTime(){
		if(!isset($this->_data['retrytime'])){
			$this->_data['retrytime'] = 0;
		}
		return $this->_data['retrytime'];
	}
	/**
	 * 执行动作
	 * 当_data['object']是字串时，系统以静态方法执行_data['action']方法
	 * 当_data['object']是对象时，系统直接调用_data['action']方法
     * @param $di \Phalcon\DiInterface
	 * @throws Qing\Lib\Exception
	 */
	public function doJob($di){
		try{
		    if(!isset($this->_data['object'])||!$this->_data['object']){
		        if($this->_data['evalString']){
		            eval($this->_data['evalString']);
                    $result = call_user_func_array(array($this->_data['object'],$this->_data['action']), $this->_data['parameters']);
                }else{
				    $result = call_user_func_array($this->_data['action'], $this->_data['parameters']);
                }
			}else if((is_string($this->_data['object']) && class_exists($this->_data['object']))||is_object($this->_data['object'])){
				$result = call_user_func_array(array($this->_data['object'],$this->_data['action']), $this->_data['parameters']);
			}
			if(isset($this->_data['callback'])){
				if(isset($result) && $this->_data['job_result_as_param'])
					$this->addCallbackParam($result);
				$param = $this->getCallbackParams();
				call_user_func_array($this->getCallback(), $param);
				//$result = call_user_func_array($this->_data['callback'],$this->_data['callback_params']);
			}
		}catch(Exception $e){
			throw new Exception($e->getCode(),$e->getMessage());
		}
	}
}