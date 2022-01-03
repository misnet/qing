<?php
namespace Qing\Lib\Queue;
class JobEvent implements \Serializable{
    protected $_data;
    /**
     * @var \Phalcon\DiInterface
     */
    protected $di;
    public function serialize(){
        return serialize($this->_data);
    }
    public function unserialize($data){
        $this->_data = unserialize($data);
    }
    public function setEventName($name){
        $this->_data['eventName'] = $name;
    }
    public function setEventParameters($p){
        $this->_data['eventParameters'] = $p;
    }
    /**
     * 执行动作
     * 当_data['object']是字串时，系统以静态方法执行_data['action']方法
     * 当_data['object']是对象时，系统直接调用_data['action']方法
     * @param $di \Phalcon\Di\DiInterface
     * @throws Qing\Lib\Exception
     */
    public function doJob($di){
        try{
            $this->di = $di;
            $this->di->getShared('eventsManager')->fire($this->_data['eventName'],null,$this->_data['eventParameters']);
        }catch(Exception $e){
            throw new Exception($e->getCode(),$e->getMessage());
        }
    }
}