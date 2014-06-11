<?php

	require_once CORE_PATH.'/Xml.class.php';

	require_once CORE_PATH.'/render/Render.abstract.php';

	class XmlRender extends AbstractRender {
		public function render(array $output, $name = null) {

			if (null === $name) {
				$name = 'AcramaOrderRequest';
			}

			$major_xml = new XmlObject($name);
			
			foreach ((array) $output as $tag_name => $value) {
				$$tag_name = new XmlObject($tag_name);
				if (is_array($value)) {
					$xml_queue = $this->proccess($value, 'product');
					foreach($xml_queue as $queue){
						$$tag_name->add($queue);
					}
				} else {
					$$tag_name->text($value);
				}
				$major_xml->add($$tag_name);
			}
			$xml = new Xml();
			return $xml->write($major_xml);
		}
		private function proccess(array $tags = array(), $name = null) {
			$xml = new Xml();
			$xml_queue = array();
			foreach ($tags as $tag) {
				$$name = new XmlObject($name);
				foreach ($tag as $tag_name => $tag_value) {
					$xo = new XmlObject($tag_name);
					$xo->text($tag_value);
					$$name->add($xo);
				}
				$xml_queue[] = $$name;
			}
			return $xml_queue;
		}
	}	