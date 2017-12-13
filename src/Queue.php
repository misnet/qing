<?php
namespace Qing\Lib;
use Qing\Lib\Queue\Job;
use Qing\Lib\Queue\JobEvent;

/**
 * 任务处理型队列系统，任务要说明执行主体对象、方法与参数，支持发送send与接收处理receive两个接口
 * 
 * 发送至队列 例子:
 *      //发送至队列
 *      $q = new \Qing\Lib\Queue();
		$job = new \Qing\Lib\Queue\Job();
		$job->setJobObject($sender);
		$job->setJobAction('send');
		$job->setJobParameters(array('test@teeume.com',"【特有米】撒娇",$html));
		$q->send('sendmail',$job); //sendmail是对列名称

        队列接收并处理 例子：
   Tee::getQueue()->receive('sendmail',10);//sendmail是队列名，10是表示接收10条
 * @author Dony
 */
class Queue {
	/**
	 * 默认队列类型
	 * @var string
	 */
	private $_queueType = 'Redis';
	/**
	 * 支持的队列类型
	 * @var array
	 */
	private $_queueSupported = array('Redis');
	private $_queueOption = array();
	/**
	 * 任务重试次数
	 * @var integer
	 */
	private $_retryTime   = 10;
	/**
	 * 每个任务的最长执行时间(秒)
	 * @var integer
	 */
	private $_jobLimitTime   = 1000;
	/**
	 *
	 * @var \Qing\Lib\Queue\QueueAbstract
	 */
	private $_queueAdapter;
	protected $di;
	public function setDI($di){
	    $this->di = $di;
    }
	public function setAdapter($adapter){
		$this->_queueAdapter = $adapter;
	}
	/**
	 * 设置队列系统选项
	 * @param array $opt
	 */
	public function setQueueAdapterOption($opt){
		$this->_queueOption = $opt;
	}
	/**
	 * 将Job放入队列
	 * @param string $name 队列名称
	 * @param Job $data
	 */
	public function put($name,$data){
		if(!$this->_queueAdapter){
			throw new Exception('请先正确调用initAdapter方法，先初始化队列系统');
		}
		$this->_queueAdapter->setOption('name', $name);
		$this->_queueAdapter->createQueue($name);
		$this->_queueAdapter->put($data);
	}
	/**
	 * 设置每次任务的执行限制时间(秒)
	 * @param integer 秒
	 */
	public function setJobLimitTime($t){
		$this->_jobLimitTime = $t;		
	}
	/**
	 * 设置任务执行发生错误时，重试次数，默认10次
	 * @param integer $t
	 */
	public function setRetryTime($t){
		$this->_retryTime = $t;
	}
	/**
	 * 从队列中接收Job并处理Job
	 * @param string $name 队列名称
	 * @param integer $maxNum 接收Job数量
	 */
	public function receive($name,$maxNum=10){
		if(!$this->_queueAdapter){
			throw new Exception('请先正确调用initAdapter方法，先初始化队列系统');
		}
		$this->_queueAdapter->setOption('name', $name);
		$msgs =  $this->_queueAdapter->receive($maxNum);
		//队列执行过程日志文件
		$logFilename = QING_ROOT_PATH.'/var/logs/queue.txt';
		$loger = new Log($logFilename);
		if($msgs){
			foreach($msgs as $m){
				if($m instanceof Job||$m instanceof JobEvent){
					try{
						set_time_limit($this->_jobLimitTime);
						$m->doJob($this->di);
					}catch(Exception $e){
						//发生错误，重新进入队列
						if($m->getRetryTime()<=$this->_retryTime){
							$m->addRetryTime();
							$this->put($name,$m);
						}else{
							//超过执行次数
							$loger->log(Log::ERROR,'Execute Queue['.$name.'] Out of retrytime['.$this->_retryTime.']:'.serialize($m->body));
						}
						//记录错误日志
						$loger->log(Log::ERROR,'Execute Queue['.$name.'] Error]:'.serialize($m));
						$loger->log(Log::ERROR,$e->getMessage());
					}
				}
			}
		}
	}
}