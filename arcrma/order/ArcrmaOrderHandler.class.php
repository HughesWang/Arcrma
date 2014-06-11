<?php

	require_once CORE_PATH.'/Http.class.php';
	require_once CORE_PATH.'/view/ApiView.class.php';
	require_once CORE_PATH.'/connector/ApiConnector.class.php';

	require_once ARCRMA_PATH.'/ArcrmaHandler.class.php';
	require_once ARCRMA_PATH.'/order/ArcrmaOrderObject.class.php';
	require_once ARCRMA_PATH.'/order/ArcrmaOrderQueryObject.class.php';

	class ArcrmaOrderHandler extends ArcrmaHandler {
		private $_orders = array();
		private $confirm_data = array();
		public function addOrder(ArcrmaOrderObject $order) {
			$this->_orders[$order->pno] = $order;
			return $this;
		}
		public function getOrders() {
			return $this->_orders;
		}
		public function removeOrder(ArcrmaOrderObject $order) {
			if (isset($this->_orders[$order->pno])) {
				unset($this->_orders[$order->pno]);
			}
			return $this;
		}
		public function prepare() {
			if (empty($this->_orders)) {
				throw new Exception('You do not have any order to be prepared.');
				return $this;
			}
			$errors = array();
			foreach ((array) $this->getOrders() as $order) {
				if (!$order->isValided()) {
					$order->validate();
				}
				if ($order->hasErrors()) {
					$errors[$order->pno] = $order->getErrors();
				}
			}
			$this->prepared = empty($errors);
			return $this;
		}
		public function create() {
			if (!$this->isPrepared()) {
				throw new Exception('Before create you need to be proccessing prepare function first.');
			}

			$config = new stdClass();
			$config->request_type = Http::GET;
			$config->action = ArcrmaHandler::COMMAND_ORDER_CREATE;
			$config->api_url = $this->config->order_url;
			$config->data_type = ApiView::STRING_QUERY;

			$connector = ApiConnector::getInstance('redirect')->setConfig($config);

			$response = array();
			try {

				$view = new ApiView();
				$responses = array();
				foreach ((array) $this->getOrders() as $order) {
					$output = $view->output($order->getValues(true), ApiView::STRING_QUERY);
//					Hughes('Note：修改訂單號碼，然後將 pcode 貼至 confirm 再送出 request');
//					Hughes($output);
					$responses[$order->pno] = $connector->setData($output)->prepare()->request();
				}

				/*
				 * for single response
				 * 
				 * if want to use multiple then
				 * use array $responses will be fine.
				 */

				foreach ($responses as $use_single_not_multiple) {
					$response = $use_single_not_multiple;
				}
			} catch (Exception $e) {
				$response = new stdClass();
				$response->status = false;
				$response->message = $e->getMessage();
			}
			return $response;
		}
		public function query(ArcrmaOrderQueryObject $object = null) {

			if (!$object->isValided()) {
				throw new Exception(sprintf('[ %s >> %s ] Valid Fault.', __CLASS__, get_class($object)));
			}

			$config = new stdClass();
			$config->request_type = Http::GET;
			$config->action = ArcrmaHandler::COMMAND_ORDER_QUERY;
			$config->api_url = $this->config->api_url;
			$config->data_type = ApiView::STRING_QUERY;

			$connector = ApiConnector::getInstance('curl')->setConfig($config);

			try {
				$view = new ApiView();
				$output = $view->output((array) $object->getValues(true), ApiView::STRING_QUERY);

				$response = $connector->setData($output)
					->prepare()
					->request();
			} catch (Exception $e) {
				$response = new stdClass();
				$response->status = false;
				$response->message = $e->getMessage();
			}
			return $response;
		}
		public function refund(ArcrmaOrderRefundObject $object = null) {

			if (!$object->isValided()) {
				throw new Exception(sprintf('[ %s >> %s ] Valid Fault.', __CLASS__, get_class($object)));
			}

			$config = new stdClass();
			$config->request_type = Http::POST;
			$config->action = ArcrmaHandler::COMMAND_ORDER_REFUND;
			$config->api_url = $this->config->api_url;
			$config->data_type = ApiView::JSON;

			$connector = ApiConnector::getInstance('curl')->setConfig($config);

			try {
				$view = new ApiView();
				$output = $view->output((array) $object->getValues(true), ApiView::JSON);

				$response = $connector->setData($output)
					->prepare()
					->request();
			} catch (Exception $e) {
				$response = new stdClass();
				$response->status = false;
				$response->message = $e->getMessage();
			}
			return $response;
		}
		public function refund_query(ArcrmaOrderRefundQueryObject $object = null) {

			if (!$object->isValided()) {
				throw new Exception(sprintf('[ %s >> %s ] Valid Fault.', __CLASS__, get_class($object)));
			}

			$config = new stdClass();
			$config->request_type = Http::GET;
			$config->action = ArcrmaHandler::COMMAND_ORDER_REFUND_QUERY;
			$config->api_url = $this->config->api_url;
			$config->data_type = ApiView::STRING_QUERY;

			$connector = ApiConnector::getInstance('curl')->setConfig($config);

			try {
				$view = new ApiView();
				$output = $view->output((array) $object->getValues(true), ApiView::STRING_QUERY);

				$response = $connector->setData($output)
					->prepare()
					->request();
			} catch (Exception $e) {
				$response = new stdClass();
				$response->status = false;
				$response->message = $e->getMessage();
			}
			return $response;
		}
		public function confirm() {
			if (!$this->isPrepared()) {
//				throw new Exception('Before confirm you need to be proccessing prepare function first.');
			}
			$order = array_shift($this->_orders);
			$this->confirm_data = array(
					'pno' => $order->pno,
					'amt' => $order->amt,
					'ttime' => $order->ttime,
					'pcode' => $order->pcode
			);
//			$this->confirm_data = array(
//					'pno' => 'O2014010701261',
//					'amt' => 6720,
//					'ttime' => '20140104164347',
//					'pcode' => 'a5c6bf6faa4f2fff51c41fddc55c2a8b'
//			);
			return $this;
		}
		public function dump() {
			if (empty($this->confirm_data)) {
				throw new Exception('Before dump you need to be proccessing confirm function first.');
			}
			$view = new ApiView();
			header('Content-Type: application/xml; charset=utf-8;');
			echo $view->output((array) $this->confirm_data, ApiView::XML, 'ConfirmCheck');
		}
	}	