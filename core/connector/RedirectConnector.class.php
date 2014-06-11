<?php

	require_once CORE_PATH.'/connector/Connector.interface.php';

	class RedirectConnector extends ApiConnector implements IConnector {
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
				$response->message = sprintf('%s is not prepared.', __CLASS__);
				return $response;
			}
			try {
				switch (strtoupper($this->config->request_type)) {
					case RedirectConnector::GET:
						$response->result = $this->get();
						$response->status = true;
						break;
					case RedirectConnector::POST:
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
			header('Location: '.self::generate_get_url());
			exit;
		}
		private function generate_get_url() {
			$format = '%s/%s?%s';
			return sprintf($format, $this->config->api_url, $this->config->action, $this->data);
		}
		private function generate_post_form() {
			$uid = uniqid('send_');
			$form_genrator = $script_genrator = array();
			$form_genrator[] = sprintf('<form id="%s" method="post" action="%s">', $uid, $this->config->api_url.'/'.$this->config->action);
			foreach ($this->data as $field_name => $field_value) {
//				$form_genrator[] = sprintf('<label>%s : </label>', $field_name);
				$form_genrator[] = sprintf('<input type="hidden" name="%s" value="%s" />', $field_name, $field_value);
			}
			$form_genrator[] = sprintf('<input type="submit" />', $field_name, $field_value);
			$form_genrator[] = '</form>';
			$script_genrator[] = '<script type="text/javascript">';
			$script_genrator[] = sprintf('document.forms.%s.submit();', $uid);
			$script_genrator[] = '</script>';
			$genrator = array_merge($form_genrator, $script_genrator);
			return implode('', $genrator);
		}
		private function post() {
			echo self::generate_post_form();
			exit;
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