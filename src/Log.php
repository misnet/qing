<?php

namespace Qing\Lib;

class Log extends \Phalcon\Logger\Adapter\Stream {
	const ERROR = \Phalcon\Logger\Logger::ERROR;
	const ALERT = \Phalcon\Logger\Logger::ALERT;
	const CRITICAL = \Phalcon\Logger\Logger::CRITICAL;
	const CUSTOM = \Phalcon\Logger\Logger::CUSTOM;
	const DEBUG = \Phalcon\Logger\Logger::DEBUG;
	const INFO = \Phalcon\Logger\Logger::INFO;
	const NOTICE = \Phalcon\Logger\Logger::NOTICE;
	const WARNING = \Phalcon\Logger\Logger::WARNING;
	public function __construct($name = '', $option = null) {
		parent::__construct ( $name, $option );
	}
}