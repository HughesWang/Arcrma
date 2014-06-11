<?php

	require_once CORE_PATH.'/IHandler.class.php';

	abstract class ArcrmaHandler implements IHandler {
		const COMMAND_DELIVERY_NOTIFY = 'NotifyDelv';
		const COMMAND_EXCHANGE_RATE_QUERY = 'exchangerate';
		const COMMAND_ORDER_CREATE = 'create';
		const COMMAND_ORDER_QUERY = 'order';
		const COMMAND_ORDER_REFUND = 'refund';
		const COMMAND_ORDER_REFUND_QUERY = 'refund';
		const COMMAND_PRODUCT_CREATE = 'prod';
		const COMMAND_PRODUCT_DELETE = 'prod';
		const COMMAND_PRODUCT_QUERY = 'prod';
		const COMMAND_PRODUCT_PRODUCTBATCH = 'prodbatch';
		protected $prepared = false;
		protected $config = array();
		public function hasErrors() {
			$errors = self::getErrors();
			return !empty($errors);
		}
		public function isPrepared() {
			return $this->prepared;
		}
		public function setConfig($config = null) {
			if (null !== $config and !empty($config)) {
				if (is_array($config)) {
					$this->config = json_decode(json_encode($config));
				} elseif (is_object($config)) {
					$this->config = $config;
				}
			}
			return $this;
		}
		abstract public function prepare();
	}	