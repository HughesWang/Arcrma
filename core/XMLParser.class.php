<?php

	class XMLParser {
		/**
		 * @var array
		 */
		private $result = array();

		/**
		 * @var
		 */
		private $parser;

		/**
		 * @var
		 */
		private $xml;
		/**
		 * Construct
		 * @param string $file[optional]
		 */
		public function __construct() {
			
		}
		/**
		 *
		 * @return
		 * @param string $file
		 */
		public function load($xml) {
			return $this->_parse($xml);
		}
		/**
		 *
		 * @return
		 * @param object $xml
		 */
		protected function _parse($xml) {
			$this->parser = xml_parser_create();
			xml_set_object($this->parser, $this);
			xml_set_element_handler($this->parser, '_tagOpen', '_tagClose');
			xml_set_character_data_handler($this->parser, '_tagData');
			$this->xml = xml_parse($this->parser, $xml);
			if (!$this->xml) {
				throw new Exception(sprintf('XML error: %s at line %d', xml_error_string(xml_get_error_code($this->parser)), xml_get_current_line_number($this->parser)));
			}
			xml_parser_free($this->parser);
			return $this->result;
		}
		/**
		 *
		 * @param object $parser
		 * @param string $nodename
		 * @param string $attributes
		 */
		protected function _tagOpen($parser, $nodename, $attributes) {
			$attrs = array();
			foreach ($attributes as $key => $value) {
				$attrs[strtolower($key)] = $value;
			}
			array_push($this->result, array('nodename' => strtolower($nodename), 'attributes' => $attrs));
		}
		/**
		 *
		 * @param object $parser
		 * @param string $nodevalue
		 */
		protected function _tagData($parser, $nodevalue) {
			if (null != $nodevalue) {
				if (is_string($nodevalue)) {
					$nodevalue = trim($nodevalue);
				} else if (is_int($nodevalue)) {
					$nodevalue = intval($nodevalue);
				}
				$index = count($this->result) - 1;
				if (isset($this->result[$index]['nodevalue'])) {
					$this->result[$index]['nodevalue'] .= $this->parseXMLValue($nodevalue);
				} else {
					$this->result[$index]['nodevalue'] = $this->parseXMLValue($nodevalue);
				}
			}
		}
		/**
		 *
		 * @param object $parser
		 * @param string $name
		 */
		protected function _tagClose($parser, $name) {
			$this->result[count($this->result) - 2]['childrens'][] = $this->result[count($this->result) - 1];
			array_pop($this->result);
		}
		/**
		 *
		 * @return string
		 * @param string $value
		 */
		protected function parseXMLValue($value) {
			$value = htmlentities($value, ENT_QUOTES, 'UTF-8');
			return $value;
		}
		/**
		 *
		 * @return
		 */
		protected function getResult() {
			return $this->result;
		}
	}	