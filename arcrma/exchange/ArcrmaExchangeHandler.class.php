<?php

	require_once CORE_PATH.'/Http.class.php';
	require_once CORE_PATH.'/view/ApiView.class.php';
	require_once CORE_PATH.'/connector/ApiConnector.class.php';

	require_once ARCRMA_PATH.'/ArcrmaHandler.class.php';
	require_once ARCRMA_PATH.'/exchange/ArcrmaExchangeObject.class.php';

	class ArcrmaExchangeHandler extends ArcrmaHandler {
		public function prepare() {
			return $this;
		}
		public function rate() {
			$config = new stdClass();
			$config->request_type = Http::GET;
			$config->action = ArcrmaHandler::COMMAND_EXCHANGE_RATE_QUERY;
			$config->api_url = $this->config->api_url;
			$config->data_type = ApiView::STRING_QUERY;
			
			$connector = ApiConnector::getInstance('curl')->setConfig($config);

			$exchange_conds = new ArcrmaExchangeObject();
			$exchange_conds->store = $this->config->store;
			$exchange_conds->validate();
			
			try {
				$view = new ApiView();
				$output = $view->output((array) $exchange_conds->getValues(true), ApiView::STRING_QUERY);

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