<?php

	require_once CORE_PATH.'/render/Render.abstract.php';

	class QueryRender extends AbstractRender {
		public function render(array $output, $name = null) {
			$render = array();
			foreach ((array) $output as $tag_name => $value) {
				if ($value == null || $value == '') {
					continue;
				}
				$render[$tag_name] = $value;
			}
			return http_build_query($render);
		}
	}	