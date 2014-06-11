<?php

	require_once CORE_PATH.'/connector/Connector.interface.php';

	class CurlConnector extends ApiConnector implements IConnector {
		const POST = Http::POST;
		const GET = Http::GET;
		const DELETE = Http::DELETE;
		protected $config = array();
		protected $data = array();
		protected $header = array();
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
		public function setData($data = array()) {
			$this->data = $data;
			return $this;
		}
		public function request() {

			$response = new stdClass();
			$response->status = false;
			$response->result = null;
			$response->message = null;

			if (!$this->isPrepared()) {
				$response->message = 'CurlConnector is not prepared.';
				return $response;
			}
			try {
				switch (strtoupper($this->config->request_type)) {
					case CurlConnector::GET:
						$response->result = $this->get();
						$response->status = true;
						break;
					case CurlConnector::POST:
						$response->result = $this->post();
						$response->status = true;
						break;
					case CurlConnector::DELETE:
						$response->result = $this->post();
						$response->status = true;
						break;
					default :
						$message = 'Can not find this type[ %s ] to send request.';
						throw new Exception(sprintf($message, $this->config->request_type));
						break;
				}
			} catch (Exception $e) {
				$response->message = $e->getMessage();
			}
			return $response;
		}
		private function get() {
			$format = '%s/%s?%s';
			$url = sprintf($format, $this->config->api_url, $this->config->action, $this->data);
			$curl = curl_init();
			if (preg_match("/^https/", $url)) {
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			}
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($this->config->request_type));
			$response_data = curl_exec($curl);
			curl_close($curl);
			return $response_data;
		}
		private function post() {
			$format = '%s/%s';
			$url = sprintf($format, $this->config->api_url, $this->config->action);
			$curl = curl_init();
			if (preg_match("/^https/", $url)) {
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			}
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($this->config->request_type));
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $this->data);
			$response_data = curl_exec($curl);
			curl_close($curl);
			return $response_data;
		}
		public function prepare() {

			if (is_object($this->config) and !empty($this->config)) {
				$require_fields = array('action', 'api_url', 'request_type', 'data_type');
				$errors = array();
				foreach ($require_fields as $field) {
					if (!isset($this->config->{$field}) || empty($this->config->{$field})) {
						$errors[] = $field.' has error.';
						break;
					}
				}
				$this->_prepared = (empty($errors) and parent::validDataType($this->config->data_type));
			}

			if ($this->isPrepared()) {
				$allow_type = array(
						'json' => 'application/json',
						'xml' => 'application/xml',
						'html' => 'text/html'
				);
				$content_type = null;
				if (isset($allow_type[strtolower($this->config->data_type)])) {
					$content_type = $allow_type[strtolower($this->config->data_type)];
				} else {
					$content_type = $allow_type['html'];
				}
				$this->header = array('Content-Type: '.$content_type.'; charset=utf-8');
			}

			return $this;
		}
	}	