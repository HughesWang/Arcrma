<?php

	class XMLRender extends XMLWriter {
		/**
		 * Constructor
		 * @param string $root_name A root element's name of a current xml document.
		 * @param string $xslt_path Path of a XSLT file.
		 */
		public function __construct() {
			$this->openMemory();
			$this->setIndent(true);
			$this->setIndentString('	');
			$this->startDocument('1.0', 'UTF-8');
//			$this->startElement($root_name);
		}
		/**
		 * Set an element with a text to a current xml document.
		 * @param string $name An element's name
		 * @param string $text An element's text
		 * @param array $attr An element's attributes
		 */
		public function setElement($name, $text, $attrs = null) {
			$this->startElement($name);
			if (is_array($attrs)) {
				foreach ($attrs as $attr => $value) {
					$this->setAttribute($attr, $value);
				}
			}
			if (is_bool($text)) {
				$text = $text ? 'true' : 'false';
			}
			$this->text($text);
			$this->endElement();
		}
		/**
		 * Set an attribute to element
		 * @param string $name An attribute's name
		 * @param mixed $value An attribute's value
		 */
		public function setAttribute($name, $value) {
			$this->startAttribute($name);
			$this->text($value);
			$this->endAttribute();
		}
		/**
		 * Construct elements and texts from an array.
		 * The array should contain an attribute's name in index part
		 * and a attribute's text in value part.
		 * @param array $array Contains attributes and texts
		 */
		public function fromArray($array) {
			if (is_array($array)) {
				$current_tag = false;
				foreach ($array as $key => $value) {
					if ($key == 'TagName') {
						$this->startElement($value);
						$current_tag = true;
					} else if ($key == 'Attrs') {
						if (is_array($value)) {
							foreach ($value as $name => $value) {
								$this->setAttribute($name, $value);
							}
						}
					} else if ($key == 'Content') {
						if (is_array($value)) {
							foreach ($value as $array) {
								$this->fromArray($array);
							}
						} else if (is_string($value) || is_numeric($value)) {
							$this->text($value);
						}
					}
				}
				if ($current_tag) {
					$this->endElement();
				}
			}
		}
		/**
		 * Return the content of a current xml document.
		 * @return string Xml document
		 */
		public function render() {
			$this->endElement();
			$this->endDocument();
			return $this->outputMemory();
		}
	}	