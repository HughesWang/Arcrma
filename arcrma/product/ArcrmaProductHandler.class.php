<?php

	require_once CORE_PATH.'/Http.class.php';
	require_once CORE_PATH.'/view/ApiView.class.php';
	require_once CORE_PATH.'/connector/ApiConnector.class.php';

	require_once ARCRMA_PATH.'/ArcrmaHandler.class.php';
	require_once ARCRMA_PATH.'/product/ArcrmaProductObject.class.php';
	require_once ARCRMA_PATH.'/product/ArcrmaProductQueryObject.class.php';

	class ArcrmaProductHandler extends ArcrmaHandler {
		const STATUS_PREPARE = 'prepare';
		const STATUS_PROCCESS = 'proccess';
		const STATUS_SUCCESS = 'success';
		const STATUS_FAULT = 'fault';
		const STATUS_REMOVE = 'remove';
		private $_products = array(
				ArcrmaProductHandler::STATUS_PREPARE => array(),
				ArcrmaProductHandler::STATUS_PROCCESS => array(),
				ArcrmaProductHandler::STATUS_FAULT => array(),
				ArcrmaProductHandler::STATUS_SUCCESS => array(),
				ArcrmaProductHandler::STATUS_REMOVE => array()
		);
		private function clearProducts($type = null) {
			if ($type === null) {
				foreach ($this->_products as &$product_group) {
					$product_group = array();
				}
			} elseif (isset($this->_products[$type])) {
				$this->_products[$type] = array();
			}
			return $this;
		}
		public function addProduct(ArcrmaProductObject $product = null, $type = ArcrmaProductHandler::STATUS_PREPARE) {
			if ($type == ArcrmaProductHandler::STATUS_PREPARE) {
				$this->prepared = false;
			}
			$this->_products[$type][] = $product;
			return $this;
		}
		public function removeProduct(ArcrmaProductDeleteObject $product = null, $type = ArcrmaProductHandler::STATUS_REMOVE) {
			$this->prepared = false;
			$this->_products[$type][] = $product;
			return $this;
		}
		public function prepare() {
			$this->prepared = true;
			foreach ($this->_products[ArcrmaProductHandler::STATUS_PREPARE] as $flag => $product) {
				$product->validate();
				if ($product->isValided()) {
					$this->addProduct($product, ArcrmaProductHandler::STATUS_PROCCESS);
				} else {
					$this->prepared = $this->prepared and false;
					$this->addProduct($product, ArcrmaProductHandler::STATUS_FAULT);
				}
				unset($this->_products[ArcrmaProductHandler::STATUS_PREPARE][$flag]);
			}
			foreach ($this->_products[ArcrmaProductHandler::STATUS_REMOVE] as $flag => $product) {
				$product->validate();
				if (!$product->isValided()) {
					$this->prepared = $this->prepared and false;
					$this->addProduct($product, ArcrmaProductHandler::STATUS_FAULT);
					unset($this->_products[ArcrmaProductHandler::STATUS_REMOVE][$flag]);
				}
			}
			return $this;
		}
		private function proccess($response = null) {
			$proccess = array();
			if (null !== $response and $response->status === true) {
				$result = json_decode($response->result);
				if (strtoupper($result->result_code) === 'OK') {
					foreach ($result->products as $product) {
						$proccess[] = new ArcrmaProductObject($product);
					}
				}
			}
			return $proccess;
		}
		public function getProducts($type = ArcrmaProductHandler::STATUS_SUCCESS) {
			$products = array();
			if (isset($this->_products[$type])) {
				$products = $this->_products[$type];
			}
			return $products;
		}
		public function query($object = null) {

			$config = new stdClass();
			$config->request_type = Http::GET;
			$config->action = ArcrmaHandler::COMMAND_PRODUCT_QUERY;
			$config->api_url = $this->config->api_url;
			$config->data_type = ApiView::STRING_QUERY;

			$data = new stdClass();
			$data->store = $this->config->store;
			if (get_class($object) === 'ArcrmaProductQueryObject' and $object->isValided()) {
				$data->prod = $object->prod;
			}

			$view = new ApiView();
			$output = $view->output((array) $data, ApiView::STRING_QUERY);

			try {
				$response = ApiConnector::getInstance('curl')
					->setConfig($config)
					->setData($output)
					->prepare()
					->request();

				$products = $this->proccess($response);
				foreach ($products as $product) {
					$this->addProduct($product, ArcrmaProductHandler::STATUS_SUCCESS);
				}
			} catch (Exception $e) {
				
			}
			return $this;
		}
		public function create() {
			if (!$this->isPrepared()) {
				throw new Exception('Before create you need to be proccessing prepare function first.');
			}
			$config = new stdClass();
			$config->request_type = Http::POST;
			$config->action = ArcrmaHandler::COMMAND_PRODUCT_CREATE;
			$config->api_url = $this->config->api_url;
			$config->data_type = ApiView::JSON;

			$connector = ApiConnector::getInstance('curl')->setConfig($config);
			$proccess_products = $this->getProducts(ArcrmaProductHandler::STATUS_PROCCESS);

			$view = new ApiView();

			$response = array();
			foreach ($proccess_products as $product) {
				$output = $view->output($product->getValues(true), ApiView::JSON);
				try {
					$result = $connector->setData($output)
						->prepare()
						->request();
				} catch (Exception $e) {
					$result = new stdClass();
					$result->status = false;
					$result->message = $e->getMessage();
				}
				$response[$product->prod] = $result;
			}
			return $response;
		}
		public function delete() {
			if (!$this->isPrepared()) {
				throw new Exception('Before delete you need to be proccessing prepare function first.');
			}
			$config = new stdClass();
			$config->request_type = Http::DELETE;
			$config->action = ArcrmaHandler::COMMAND_PRODUCT_DELETE;
			$config->api_url = $this->config->api_url;
			$config->data_type = ApiView::STRING_QUERY;

			$connector = ApiConnector::getInstance('curl')->setConfig($config);
			$remove_products = $this->getProducts(ArcrmaProductHandler::STATUS_REMOVE);

			$view = new ApiView();

			$response = array();
			foreach ($remove_products as $product) {
				$output = $view->output((array) $product->getValues(), ApiView::STRING_QUERY);
				try {
					$result = $connector->setData($output)
						->prepare()
						->request();
				} catch (Exception $e) {
					$result = new stdClass();
					$result->status = false;
					$result->message = $e->getMessage();
				}
				$response[$product->prod] = $result;
			}
			return $response;
		}
		public function product_batch() {
			if (!$this->isPrepared()) {
				throw new Exception('Before batch products you need to be proccessing prepare function first.');
			}
			$config = new stdClass();
			$config->request_type = Http::POST;
			$config->action = ArcrmaHandler::COMMAND_PRODUCT_PRODUCTBATCH;
			$config->api_url = $this->config->api_url;
			$config->data_type = ApiView::XML;

			$connector = ApiConnector::getInstance('curl')->setConfig($config);

			$create_products = $this->getProducts(ArcrmaProductHandler::STATUS_PROCCESS);
			$remove_products = $this->getProducts(ArcrmaProductHandler::STATUS_REMOVE);

			$response = array();
			if (empty($create_products) and empty($remove_products)) {
				return $response;
			}
			
			$request_queue = array(
					'store' => $this->config->store
			);
			foreach ($remove_products as $product) {
				$request_queue['products'][] = array_merge(array('action' => 'delete'), $product->getValues(true));
			}
			foreach ($create_products as $product) {
				$request_queue['products'][] = array_merge(array('action' => 'new'), $product->getValues(true));
			}

			$this->clearProducts(ArcrmaProductHandler::STATUS_PROCCESS)
				->clearProducts(ArcrmaProductHandler::STATUS_REMOVE);

			try {
				$view = new ApiView();
				$output = $view->output((array) $request_queue, ApiView::XML, 'store_products');

				$response = $connector->setData($output)
					->prepare()
					->request();

				/* 回傳的 response 待處理 */
			} catch (Exception $e) {
				$response = new stdClass();
				$response->status = false;
				$response->message = $e->getMessage();
			}
			return $response;
		}
	}	