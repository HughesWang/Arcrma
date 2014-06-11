<?php

	require_once CORE_PATH.'/Validator.php';

	require_once ARCRMA_PATH.'/delivery/ArcrmaDeliveryHandler.class.php';
	require_once ARCRMA_PATH.'/exchange/ArcrmaExchangeHandler.class.php';
	require_once ARCRMA_PATH.'/order/ArcrmaOrderHandler.class.php';
	require_once ARCRMA_PATH.'/product/ArcrmaProductHandler.class.php';

	class ArcrmaProccessor {
		private $store_id = null;
		private $mode = 'test';
		private $_product = null;
		private $_order = null;
		private $_exchange = null;
		private $_delivery = null;
		private $settings = array(
				'test' => array(
						'store_url' => 'https://tstore.firstepay.com',
						'api_url' => 'https://tapi.firstepay.com',
						'order_url' => 'https://torder.firstepay.com',
				),
				'online' => array(
						'store_url' => 'https://store.firstepay.com',
						'api_url' => 'https://api.firstepay.com',
						'order_url' => 'https://order.firstepay.com',
				)
		);
		public function __construct($store_id = null) {
			$this->setStore($store_id);
			return $this;
		}
		protected function setStore($store_id = null) {
			if (is_string($store_id) and Validator::maxLength($store_id, 12)) {
				$this->store_id = $store_id;
			}
			return $this;
		}
		protected function getStore() {
			return $this->store_id;
		}
		protected function getApiUrl() {
			return $this->settings[$this->mode]['api_url'];
		}
		public function getInstance($type = null) {
			$instance = null;
			$config = $this->settings[$this->mode];
			$config['store'] = $this->store_id;
			switch ($type) {
				case 'product':
					if (null === $this->_product) {
						$this->_product = new ArcrmaProductHandler();
						$this->_product->setConfig($config);
					}
					$instance = $this->_product;
					break;
				case 'order':
					if (null === $this->_order) {
						$this->_order = new ArcrmaOrderHandler();
						$this->_order->setConfig($config);
					}
					$instance = $this->_order;
					break;
				case 'exchange':
					if (null === $this->_exchange) {
						$this->_exchange = new ArcrmaExchangeHandler();
						$this->_exchange->setConfig($config);
					}
					$instance = $this->_exchange;
					break;
				case 'delivery':
					if (null === $this->_delivery) {
						$this->_delivery = new ArcrmaDeliveryHandler();
						$this->_delivery->setConfig($config);
					}
					$instance = $this->_delivery;
					break;
			}
			if (null === $instance) {
				throw new Exception(sprintf('Can not find %s instance', $type));
			}
			return $instance;
		}
	}	