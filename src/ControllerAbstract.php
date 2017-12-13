<?php
namespace Qing\Lib;
use Phalcon\Mvc\Model\Criteria;
abstract class ControllerAbstract extends \Phalcon\Mvc\Controller{


	protected function sendContent($content){
		$this->view->disable();
		$this->response->setContent($content)->send();
	}
	protected function setJsonResponse($data){
        $this->response->setHeader('Content-Type', 'application/json; charset=utf8');
		//$data = \Zend\Json\Encoder::encode($data,true);
        $data = json_encode($data);
        //$data = json_encode($data);
        $this->sendContent($data);
        exit;
	}
	/**
	 * 替代Criteria::fromInput
	 * @param unknown $dependencyInjector
	 * @param unknown $modelName
	 * @param unknown $data
	 * @return \Qing\Lib\Criteria
	 */

	public static function fromInput($dependencyInjector, $modelName, $data){
	
	
		$conditions = array();
		if (count($data)) {
	
			$metaData = $dependencyInjector->getShared('modelsMetadata');
	
			$model = new $modelName();
			$dataTypes = $metaData->getDataTypes($model);
			$columnMap = $metaData->getReverseColumnMap($model);
			$bind = array();
	
			foreach ($data as $fieldName => $value) {
	
				if (isset($columnMap[$fieldName])) {
					$field = $columnMap[$fieldName];
				} else {
					continue;
				}
	
				if (isset($dataTypes[$field])) {
	
					if (!is_null($value)) {
						if ($value != '') {
							if ($dataTypes[$field] == 2) {
								$condition = '`'.$field . "` LIKE :" . $fieldName;
								$bind[$fieldName] = '%' . $value . '%';
							} else {
								$condition = '`'.$field . '` = :' . $fieldName;
								$bind[$fieldName] = $value;
							}
							$conditions[] = $condition;
						}
					}
				}
			}
		}
	
		$criteria = new Criteria();
		if (count($conditions)) {
			$criteria->where(join(' AND ', $conditions));
			$criteria->bind($bind);
		}
	
		return $criteria;
	
	}
}