<?php

	require_once CORE_PATH.'/render/Render.abstract.php';

	class JsonRender extends AbstractRender {
		public function render(array $output, $name) {
			return json_encode($output);
		}
	}	