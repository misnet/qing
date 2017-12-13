<?php

namespace Qing\Lib;

class Log extends \Phalcon\Logger\Adapter\File {
	const ERROR = \Phalcon\Logger::ERROR;
	const ALERT = \Phalcon\Logger::ALERT;
	const CRITICAL = \Phalcon\Logger::CRITICAL;
	const CUSTOM = \Phalcon\Logger::CUSTOM;
	const DEBUG = \Phalcon\Logger::DEBUG;
	const EMERGENCE = \Phalcon\Logger::EMERGENCE;
	const INFO = \Phalcon\Logger::INFO;
	const NOTICE = \Phalcon\Logger::NOTICE;
	const SPECIAL = \Phalcon\Logger::SPECIAL;
	const WARNING = \Phalcon\Logger::WARNING;
	public function __construct($name = '', $option = null) {
		parent::__construct ( $name, $option );
	}
}