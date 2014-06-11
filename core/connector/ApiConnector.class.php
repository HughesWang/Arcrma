<?php

	require_once CORE_PATH.'/view/ApiView.class.php';

	class ApiConnector {
		private static $connector = array(
				'curl' => null,
				'redirect' => null
		);
		protected $_prepared = false;
		public static function getInstance($type = null) {
			$type = strtolower($type);
			if (!in_array($type, array_keys(self::$connector))) {
				throw new Exception(sprintf('Do not support this [ type : %s ] connector', $type));
			}
			if (null === self::$connector[$type]) {
				$connect_name = ucfirst($type).'Connector';
				if (!class_exists($connect_name)) {
					require CORE_PATH.'/connector/'.$connect_name.'.class.php';
				}
				self::$connector[$type] = new $connect_name();
			}
			return self::$connector[$type];
		}
		final private function __clone() {
			
		}
		public function validDataType($type = null) {
			if (null === $type) {
				return false;
			}
			$reflect = new ReflectionClass('ApiView');
			$conds = array();
			foreach ((array) $reflect->getConstants() as $const_name => $const_value) {
				$conds[] = $const_value;
			}
			return in_array(strtolower($type), array_keys($conds));
		}
		protected function isPrepared() {
			return $this->_prepared;
		}
	}	