<?php

namespace Qing\Lib;

class Exception extends \Phalcon\Exception {
	/**
	 *
	 * @var null Exception
	 */
	private $_previous = null;
	protected $_messages = array ();
	/**
	 * Construct the exception
	 *
	 * @param string $msg        	
	 * @param int $code        	
	 * @param Exception $previous        	
	 * @return void
	 */
	public function __construct($msg = '', $code = 0, Exception $previous = null) {
		if(is_array($msg)){
			$this->_messages = $msg;
			$msg = join("\n",$msg);
		}
		if (version_compare ( PHP_VERSION, '5.3.0', '<' )) {
			parent::__construct ( $msg, ( int ) $code );
			$this->_previous = $previous;
		} else {
			parent::__construct ( $msg, ( int ) $code, $previous );
		}
	}
	public function getMessages() {
		return $this->_messages;
	}
	/**
	 * Overloading
	 *
	 * For PHP < 5.3.0, provides access to the getPrevious() method.
	 *
	 * @param string $method        	
	 * @param array $args        	
	 * @return mixed
	 */
	public function __call($method, array $args) {
		if ('getprevious' == strtolower ( $method )) {
			return $this->_getPrevious ();
		}
		return null;
	}
	
	/**
	 * String representation of the exception
	 *
	 * @return string
	 */
	public function __toString() {
		if (version_compare ( PHP_VERSION, '5.3.0', '<' )) {
			if (null !== ($e = $this->getPrevious ())) {
				return $e->__toString () . "\n\nNext " . parent::__toString ();
			}
		}
		return parent::__toString ();
	}
	
	/**
	 * Returns previous Exception
	 *
	 * @return Exception null
	 */
	protected function _getPrevious() {
		return $this->_previous;
	}
}