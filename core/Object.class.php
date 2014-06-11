<?php

	require_once CORE_PATH.'/Validator.php';
	require_once CORE_PATH.'/logger/PHTLogger.class.php';

	class Object extends stdClass {
		protected $_logger = null;
		public function __construct() {
			if (null === $this->_logger) {
				$this->_logger = new PHTLogger();
			}
		}
		protected function addError($error = null) {
			if ($error !== null and $error != '') {
				$this->_logger->addMessage($error, 'error');
			}
			return $this;
		}
		public function getErrors() {
			return $this->_logger->getMessages('error');
		}
		public function removeErrors() {
			return $this->_logger->removeMessages('error');
		}
		public function hasErrors() {
			$errors = self::getErrors();
			return !empty($errors);
		}
		public function __get($name) {
			$value = null;
			if (isset($this->$name)) {
				$value = $this->$name;
			}
			return $value;
		}
		public function __isset($name) {
			return isset($this->$name);
		}
	}	