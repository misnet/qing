<?php

namespace Qing\Lib;

class SimpleStorage {
	/**
	 *
	 * @var \Qing\Lib\RedisServer
	 */
	private $_redisServer;
	private $_options;
	private $_transacting = 0;
	public function __construct($config) {
		$this->_options ['host'] = '127.0.0.1';
		$this->_options ['port'] = 6391;
		$this->_options ['auth'] = '';
		$this->_options ['index'] = 0;
		$this->_filterOption ( $config );
        //$this->_redisServer = new RedisServer ( $this->_options ['host'], $this->_options ['port'] );

        //IMPORTANT:
        //如果使用php-redis库，在zRangeByScore使用参数withscores时，可能内存溢出，整个php进程的相关变量会莫名其妙出问题，例：
        //function test(){
        //  $list = $this->_redisServer->zRangeByScore(...);
        //  return ["a","b"];
        //}
        //在取回test函数返回值时，居然返回的会是空数组[]
        //
        // 采用php-redis时zRangeByScore相关的调用必须重写，传参不一样了


        $this->_redisServer = new \Redis();
        $this->_redisServer->connect($this->_options ['host'], $this->_options ['port']);
		if ($this->_options ['auth']) {
			$this->_redisServer->Auth ( $this->_options ['auth'] );
		}
		$this->_redisServer->Select ( $this->_options ['index'] );
        //$this->_redisServer->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
    }

    /**
     * rq
     * @param $index
     */
	public function selectDatabase($index){
	    $this->_redisServer->Select($index);
	    $this->_options['index'] = $index;
    }
	public function get($key) {
		$data = $this->_redisServer->get( $key );
		return $this->_unserialize ( $data );
	}
	public function set($key, $value) {
		$value = $this->_serialize ( $value );
		return $this->_redisServer->Set ( $key, $value );
	}
	public function delete($key) {
		return $this->_redisServer->Del ( $key );
	}

    /**
     * 修改List中指定的值
     * @param $key
     * @param $index
     * @param $value
     *
     * @return array|bool|int|null|string|void
     */
	public function updateListAt($key,$index,$value){
        $value = $this->_serialize ( $value );
        return $this->_redisServer->LSet($key,$index,$value);
    }
	public function deleteFromList($key, $value, $count = 0) {
		$this->_redisServer->LRem ( $key, $count, $value );
	}
	public function addToSet($key, $value) {
		$value = $this->_serialize ( $value );
		return $this->_redisServer->sAdd ( $key, $value );
	}

    public function getFromSet($key){
        $result = $this->_redisServer->sMembers($key);
        return $this->_unserialize($result);
    }
    public function scanSet($key,$limit=10,$match=''){
	    $it = null;
        $result = $this->_redisServer->sScan($key,$it,$match,$limit);
        if($result){
            foreach($result as $key=>&$value){
                $value = $this->_unserialize($value);
            }
        }
        return $result;
    }

	public function deleteFromSet($key,$value){
		$value = $this->_serialize ( $value );
		return $this->_redisServer->sRem($key, $value);
	}
	public function isInSet($setKey,$member){
		$value = $this->_serialize ( $member );
		return $this->_redisServer->sIsMember($setKey, $value);
	}
	public function expired($key,$seconds){
	    $this->_redisServer->Expire($key, $seconds);
	}
	/**
	 * 将hash中某字段序号增加一定量
	 * @param string $key 哈希值
	 * @param string $field 字段名
	 * @param number $amount 增加量
	 * @return integer 返回增加后最终的数值
	 */
	public function incrementInHash($key, $field, $amount = 1) {
		return $this->_redisServer->hIncrBy ( $key, $field, $amount );
	}
	/**
	 * 将值存入Hash中
	 * 
	 * @param unknown $key
	 *        	Hash集名
	 * @param unknown $fieldOrData
	 *        	字段名或一组数据
	 * @param string $value
	 *        	值
	 * @param boolean $overwrite
	 *        	如果存在是否希望重载
	 * @throws Exception
	 */
	public function setToHash($key, $fieldOrData, $value = null, $overwrite = true) {
		if (is_array ( $fieldOrData )) {
			$data = $fieldOrData;
			
			if (empty ( $data )) {
				throw new \Exception ( 'Not present fields and values for set' );
			}
			foreach ( $data as $field => $value ) {
				$fields [$field] = $this->_serialize ( $value );
			}
			return $this->_redisServer->hMSet ( $key, $fields );
		} else {
			$field = $fieldOrData;
			$value = $this->_serialize ( $value );
			return $overwrite ? $this->_redisServer->hSet ( $key, $field, $value ) : $this->_redisServer->hSetNX ( $key, $field, $value );
			
		}
	}
	/**
	 * 从Hash对象中取内容
	 * @param string $key Hash对象ＫＥＹ
	 */
	public function getFromHash($key){
	    $hashData = $this->_redisServer->hGetAll($key);
	    if($hashData){
	        foreach($hashData as $key=>&$value){
	            $value = $this->_unserialize($value);
	        }
	    }
	    return $hashData;
	}
	/**
	 * 在列表追加数据
	 * 
	 * @param unknown $key        	
	 * @param unknown $value        	
	 */
	public function appendToList($key, $value) {
		$value = $this->_serialize ( $value );
		$this->_redisServer->RPush ( $key, $value );
	}
	/**
	 * 列表长度
	 * @param string $key
	 * @return boolean|NULL|string|number
	 */
	public function getListLength($key){
	    return $this->_redisServer->LLen($key);
	}
	/**
	 * 在列表前面插入数据
	 * 
	 * @param unknown $key        	
	 * @param unknown $value        	
	 */
	public function prependToList($key, $value) {
		$value = $this->_serialize ( $value );
		$this->_redisServer->LPush ( $key, $value );
	}
	/**
	 * 从列表中最后面移除并返回该元素
	 * @param unknown $key
	 * @return Ambigous <NULL, unknown>
	 */
	public function popFromList($key){
		$value = $this->_redisServer->RPop($key);
		return $this->_unserialize($value);
	}
	/**
	 * 取得list内容
	 * 
	 * @param unknown $key        	
	 * @param unknown $start        	
	 * @param unknown $end        	
	 * @return Ambigous <boolean, NULL, string, number, multitype:>
	 */
	public function getList($key, $start=0, $end=-1) {
		$list = $this->_redisServer->LRange ( $key, $start, $end );
		if ($list) {
			foreach ( $list as &$item ) {
				$item = $this->_unserialize ( $item );
			}
		}
		return $list;
	}
	/**
	 * 将元素加到有序集合中
	 * 
	 * @param unknown $key        	
	 * @param unknown $member        	
	 * @param number $score        	
	 */
	public function addToSortedSet($key, $member, $score = 0) {
		$member = $this->_serialize ( $member );
		$this->_redisServer->zAdd ( $key, $score, $member );
	}
	public function deleteFromSortedSet($key,$value){
		$value = $this->_serialize ( $value );
		return $this->_redisServer->zRem($key, $value);
	}

    /**
     * 排序集合长度
     * @param $key
     * @param int $min
     * @param int $max
     * @return int
     */
	public function getSortedSetLengthByScore($key,$min='-inf',$max='+inf'){
	    return $this->_redisServer->zCount($key,$min,$max);
    }
	/**
	 * 从排序集合中按分数取出集合内容
	 * 
	 * @param string $key
	 *        	集合名
	 * @param float $min
	 *        	最小分
	 * @param float $max
	 *        	最大分
	 * @param boolean $withScores
	 *        	带分数
	 * @param string $limit
	 *        	最多取几条
	 * @param string $offset
	 *        	起始序号
	 * @param boolean $revert
	 *        	是否反向
	 * @return Ambigous <boolean, NULL, string, number, multitype:>
	 */
	public function getFromSortedSetByScore($key, $min, $max, $withScores = false, $limit = null, $offset = null, $revert = false)
    {

        $options = ['withscores'=>$withScores];
	    if($limit!==null && $offset!==null){
	        $options['limit']= [$offset,$limit];
        }
		if (! $revert)
			//$result = $this->_redisServer->zRangeByScore ( $key, $min, $max, $withScores,$options);
            $result = $this->_redisServer->zRangeByScore ( $key, $min, $max, $options);
		else
            $result = $this->_redisServer->zRevRangeByScore ( $key, $max,$min, $options);
        $list = [];
		if (! empty ( $result )) {
            $len = sizeof($result);
            if($withScores){
                for($i=0;$i<$len-1;$i+=2){
                    $key   = $this->_unserialize($result[$i]);
                    $score = $this->_unserialize($result[$i+1]);
                    $list[][$key] = $score;
                }
            }else{
                foreach($result as $item){
                    $list[] = $this->_unserialize($item);
                }
            }
//			foreach ( $result as &$item ) {
//				$item = $this->_unserialize ( $item );
//			}
		}
		return $list;
	}

    /**
     * 排名
     * @param $key
     * @param $member
     * @return array|bool|int|null|string
     */
	public function getRankFromSortedSet($key,$member,$revert=false){
	    return $revert?$this->_redisServer->zRevRank($key,$member):$this->_redisServer->zRank($key,$member);
    }
    public function getScoreFromSortedSet($key,$member){
	    return $this->_redisServer->zScore($key,$member);
    }
	/**
	 * 自增某个Key
	 * @param unknown $key
	 * @param number $amount
	 * @return boolean|NULL|string|number
	 */
	public function incrementBy($key,$amount=1){
	    return $this->_redisServer->IncrBy($key, $amount);
	}
	/**
	 * 是否存在ＫＥＹ
	 * @param unknown $key
	 * @return boolean|NULL|string|number
	 */
	public function exists($key){
	    return $this->_redisServer->Exists($key);
	}
	/**
	 * 序列化
	 *
	 * @param unknown $data        	
	 * @return string
	 */
	protected function _serialize($data) {
//	    return $data;
//
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
	    //return $data;
		if (is_null ( $data )||$this->_transacting) {
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
				$unserializedValue = $data;
			}
		}

		return $unserializedValue;
	}
	/**
	 * 选项设置默认值
	 *
	 * @param \Phalcon\Config|array $option        	
	 * @return Ambigous <multitype:, array, unknown>
	 */
	protected function _filterOption($option) {
		if ($option instanceof \Phalcon\Config) {
			$option = $option->toArray ();
		} elseif (! is_array ( $option )) {
			$option = array ();
		}
		$this->_options = \Qing\Lib\Utils::arrayExtend ( $this->_options, $option );
	}
	public function getOptions(){
	    return $this->_options;
    }
	public function __destruct()
    {
        if($this->_redisServer){
            //$this->_redisServer->close();
        }
    }

    /**
     * 查以$prefix开头的key
     * @param $prefix
     * @return array|bool|int|null|string
     */
    public function queryKeys($prefix){
	    return $this->_redisServer->Keys($prefix);
    }

    /**
     * 批量删除Key
     * @param $prefix 删除的KEY后要加*否则无法删除
     */
    public function deleteKeys($prefix){
        $list = $this->queryKeys($prefix);
        if($list){
            foreach($list as $key){
                $this->_redisServer->Del($key);
            }
        }
    }

    /**
     * 批量管道,事务开始
     */
    public function begin(){
        $this->_redisServer->Multi();
        $this->_transacting =true;
    }

    /**
     * 批量管道，事务执行
     * @return array|bool|int|null|string
     */
    public function commit(){
        $this->_transacting =false;
        return $this->_redisServer->Exec();
    }

    /**
     * 事务回滚，批量管理中的命令取消
     */
    public function rollback(){
        $this->_transacting =false;
        $this->_redisServer->Discard();
    }

    /**
     * 返回集合大小
     * @param $key
     * @return int
     */
    public function countSet($key){
        return $this->_redisServer->sCard($key);
    }
    public function close()
    {
        if($this->_redisServer){
            $this->_redisServer->close();
        }
    }
}