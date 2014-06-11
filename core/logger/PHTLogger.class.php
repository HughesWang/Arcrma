<?php

	require_once CORE_PATH.'/logger/Logger.interface.php';

	class PHTLogger implements Logger {
		private static $logger = null;
		private $allow_type = array('info', 'error', 'warning', 'notice');
		private $_log = array();
		public function __construct() {
			foreach ($this->getAllowType() as $type) {
				$this->addType($type);
			}
		}
		private function getAllowType() {
			return $this->allow_type;
		}
		private function addType($type = null) {
			if (!isset($this->_log[$type])) {
				$this->_log[$type] = array();
			}
			return $this;
		}
		public function addMessage($message = null, $type = 'error') {
			if (in_array($type, $this->allow_type) and $message !== null and $message != '') {
				$this->_log[$type][] = $message;
			}
			return $this;
		}
		public function getMessages($type = null) {
			$message = array();
			if (isset($this->_log[$type])) {
				$message = $this->_log[$type];
			} else {
				$message = $this->_log;
			}
			return $message;
		}
		public function removeMessages($type = null) {
			if (isset($this->_log[$type])) {
				$this->_log[$type] = array();
			} else {
				foreach ($this->_log as &$log) {
					$log = array();
				}
			}
			return $this;
		}
		public static function getInstance() {
			if (null === self::$logger) {
				self::$logger = new PHTLogger();
			}
			return self::$logger;
		}
	}	