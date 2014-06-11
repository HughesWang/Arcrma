<?php

	class XmlObject extends stdClass {
		protected $elements = array();
		protected $tag_name;
		protected $text = '';
		protected $attrs = array();
		public function __construct($name = null, $text = null, $attrs = array()) {
			$this->tagName($name);
			$this->text($text);
			foreach ($attrs as $name => $value) {
				$this->attr($name, $value);
			}
		}
		public function tagName($name = null) {
			if (is_string($name) && $name != '') {
				$this->tag_name = $name;
				return $this;
			} else {
				return $this->tag_name;
			}
		}
		public function text($text = null) {
			if (is_string($text) || is_numeric($text)) {
				$this->text = $text;
				return $this;
			} else {
				return $this->text;
			}
		}
		public function attr($name = '', $value = null) {

			if (is_string($name) && $name != '') {
				$this->attrs[$name] = (string) $value;
				return $this;
			} else {
				return $this->attrs;
			}
		}
		public function add(XmlObject $xml_object) {
			if (isset($this->elements[$xml_object->tag_name])) {
				if (!is_array($this->elements[$xml_object->tag_name])) {
					$this->elements[$xml_object->tag_name] = array($this->elements[$xml_object->tag_name]);
				}
				$this->elements[$xml_object->tag_name][] = $xml_object;
			} else {
				$this->elements[$xml_object->tag_name][] = $xml_object;
			}
		}
		public function get($name) {
			$element = null;
			if (isset($this->elements[$name])) {
				$element = $this->elements[$name];
			}
			return $element;
		}
		public function elements() {
			return $this->elements;
		}
		public function __toString() {
			$string = '';
			if (isset($this->text)) {
				$string = $this->text;
			}
			return $string;
		}
		public function __get($name) {
			$value = null;
			if (isset($this->elements[$name])) {
				if ($this->text == '' && get_class((object) $this->elements[$name]) == __CLASS__ && $this->elements[$name]->text != '') {
					$value = $this->elements[$name]->text;
				} else {
					$value = $this->elements[$name];
				}
			} else if (isset($this->attrs[$name])) {
				$value = $this->attrs[$name];
			}
			return $value;
		}
		public function __set($name, $value) {
			$this->elements[$name] = $value;
		}
		public function __isset($name) {
			return isset($this->elements[$name]);
		}
	}	