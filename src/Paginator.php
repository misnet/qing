<?php
namespace Qing\Lib;
/**
 * 页码组件
 * @author Dony
 * @copyright 2013
 */
class Paginator{
	private $_data;
	private $_resetPage = false;
	public function getPaginate(){
		
	}
	//$page=0,$rowCount=0
	public function __construct($config=array()){
		$this->_data['page']       = $config['page'];
		$this->_data['rowCount']   = $config['limit'];
		$this->_data['pageString'] = '@@page@@';
		$this->_data['pageLength'] = 5;
		$this->_data['page']>0||$this->_data['page']=1;
		$this->_data['showLimitSelectBox'] = true;
	}
	public function setCurrentPage($p){
		$this->_data['page'] = $p;
	}
	public function isShowLimitSelectBox($bol){
		$this->_data['showLimitSelectBox'] = $bol;
	}
	/**
	 * 会根据总记录数及页显示条数重算page
	 */
	public function getPage(){
		if(!isset($this->_data['total']) || !isset($this->_data['rowCount']) || !$this->_data['rowCount']){
			return $this->_data['page'];
		}elseif(!$this->_resetPage){
			$this->getTotalPage();
			if($this->_data['page']> $this->_data['totalPage']){
				$this->_data['page'] = $this->_data['totalPage'];
			}
			$this->_resetPage = true;
			return $this->_data['page'];
		}else{
			return $this->_data['page'];
		}
	}
	public function setRowCount($p){
		$this->_data['rowCount'] = $p;
	}
	public function getRowCount(){
		return $this->_data['rowCount'];
	}
	public function setTotal($t){
		$this->_data['total'] = $t;
	}
	public function getTotal($t){
		return $this->_data['total'];
	}
	public function setUrl($url){
		$this->_data['pageUrl'] = $url;
	}
	public function setPageLength($len){
		$this->_data['pageLength'] = $len;
	}
	public function setPageString($str){
		$this->_data['pageString'] = $str;
	}
	public function getTotalPage(){
		if(!isset($this->_data['totalPage']) && isset($this->_data['total']) && isset($this->_data['rowCount']) && $this->_data['rowCount']){
			$this->_data['totalPage'] = ceil($this->_data['total'] / $this->_data['rowCount']);
		}elseif(!isset($this->_data['totalPage'])){
			$this->_data['totalPage'] = 1;
		}
		$this->_data['page'] = min(array($this->_data['page'],$this->_data['totalPage']));
		return $this->_data['totalPage'];
	}
	
	/*public function __set($key,$v){
		$this->_data[$key] = $v;
	}
	public function __get($key){
		if (array_key_exists($key, $this->_data)) {
			return $this->_data[$key];
		}
		return null;
	}
	public function __isset($key){
		return isset($this->_data[$key]);
	}
	public function __unset($key){
		unset($this->_data[$key]);
	}*/
	/**
	 * 生成页相关信息
	 * @return array(
	 *             'prev'=>array('page'=>页码,'url'=>url地址),
	 *             'next'=>array('page'=>页码,'url'=>url地址),
	 *             'page'=>当前页码,
	 *             'pages'=>array(array('page'=>页码1,'url'=>url地址1),array('page'=>页码2,'url'=>url地址2),...),
	 *             'total'=>总记录数,'rowCount'=>'每页条数')
	 */
	public function renderPages(){
		if($this->_data['total']>1){
			if($this->_data['page']==1){
				$this->_data['prev']['page'] = false;
				$this->_data['prev']['url']  = '';
			}else{
				$this->_data['prev']['page'] = $this->_data['page'] - 1;
				$this->_data['prev']['url'] = str_replace($this->_data['pageString'], $this->_data['prev']['page'], $this->_data['pageUrl']);;
			}
			$this->_data['totalPage'] = $this->getTotalPage();
			$p   = ceil($this->_data['page'] / $this->_data['pageLength']);
			$end = $p * $this->_data['pageLength'];
			$start = $end - $this->_data['pageLength'] + 1;
			$end   = min(array($end,$this->_data['totalPage']));
			$j=0;
			$this->_data['pages'] = array();
			for($i=$start;$i<=$end;$i++){
				$this->_data['pages'][$j]['page'] = $i;
				$this->_data['pages'][$j]['url']  = str_replace($this->_data['pageString'], $i, $this->_data['pageUrl']);
				$j++;
			}
			if($this->_data['page']==$this->_data['totalPage']){
				$this->_data['next']['page'] = false;
				$this->_data['next']['url']  = '';
			}else{
				$this->_data['next']['page'] = $this->_data['page'] + 1;
				$this->_data['next']['url']  = str_replace($this->_data['pageString'], $this->_data['next']['page'], $this->_data['pageUrl']);;
			}
			$this->_data['start']['url'] = str_replace($this->_data['pageString'], 1, $this->_data['pageUrl']);
			$this->_data['end']['url'] = str_replace($this->_data['pageString'], $this->_data['totalPage'], $this->_data['pageUrl']);
			
			
			//每页页数
			$this->_data['rowCountList'] = array('10','20','30','50','70','100');
		}else{
			$data = array();
		}
		return $this->_data;
	}
}