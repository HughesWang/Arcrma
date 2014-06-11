<?php

	require_once CORE_PATH.'/view/View.abstract.php';

	require_once CORE_PATH.'/render/JsonRender.class.php';
	require_once CORE_PATH.'/render/XmlRender.class.php';
	require_once CORE_PATH.'/render/QueryRender.class.php';

	class ApiView extends AbstractView {
		const JSON = 'json';
		const XML = 'xml';
		const STRING_QUERY = 'query';
		public function output($output, $type = ApiView::JSON, $name = null) {
			$class_name = ucfirst(strtolower($type)).'Render';
			if (!class_exists($class_name)) {
				// @todo throw exception;
			}
			$this->_render = new $class_name();
			return $this->_render->render($output, $name);
		}
	}	