<?php

	require_once CORE_PATH.'/Http.class.php';
	require_once CORE_PATH.'/view/ApiView.class.php';
	require_once CORE_PATH.'/connector/ApiConnector.class.php';

	require_once ARCRMA_PATH.'/ArcrmaHandler.class.php';
	require_once ARCRMA_PATH.'/delivery/ArcrmaDeliveryObject.class.php';

	class ArcrmaDeliveryHandler extends ArcrmaHandler {
		public function prepare() {
			return $this;
		}
		public function notify(ArcrmaDeliveryObject $object = null) {
			
			if (!$object->isValided()) {
				throw new Exception(sprintf('[ %s >> %s ] Valid Fault.', __CLASS__, get_class($object)));
			}
			
			$config = new stdClass();
			$config->request_type = Http::POST;
			$config->action = ArcrmaHandler::COMMAND_DELIVERY_NOTIFY;
			$config->api_url = $this->config->api_url;
			$config->data_type = ApiView::JSON;
			
			$connector = ApiConnector::getInstance('curl')->setConfig($config);
			
			try {
				$view = new ApiView();
				$output = $view->output((array) $object->getValues(true), ApiView::JSON);
				// Hughes($output);
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
	}	