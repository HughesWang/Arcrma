<?php

	require_once CORE_PATH.'/XmlObject.class.php';

	class Xml {
		protected $reader = null;
		protected $writer = null;
		protected $object = null;
		public function __construct() {
			
		}
		public function schemaValidate($xml, $xsd_file) {
			$dom = new DOMDocument();
			$dom->loadXML(stripslashes($xml));
			return $dom->schemaValidate($xsd_file);
		}
		public function read($xml = null) {
			if ($this->reader === null) {
				$this->reader = new XMLReader();
			}
			if (preg_match('/^'.preg_replace('/\//', '\/', App::conf()->BASE_PATH).'.*/', $xml) && file_exists($xml)) {
				$this->reader->open($xml);
			} else if (is_string($xml)) {
				$xml = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', stripslashes($xml));
				$this->reader->XML($xml);
			}
			$object = new XmlObject();
			while ($this->reader->read()) {
				switch ($this->reader->nodeType) {
					case XMLReader::END_ELEMENT: return $object;
					case XMLReader::ELEMENT:
						$tag_name = $this->reader->name;
						$element = $this->reader->isEmptyElement ? new XmlObject() : $this->read();
						$element->tagName($tag_name);
						if ($this->reader->hasAttributes) {
							while ($this->reader->moveToNextAttribute()) {
								$element->attr($this->reader->name, $this->reader->value);
							}
						}
						if ($object->$tag_name === null) {
							$object->$tag_name = $element;
						} else {
							$object->add($element);
						}
						break;
					case XMLReader::TEXT:
					case XMLReader::CDATA:
						$object->text($this->reader->value);
						break;
				}
			}
			return $object;
		}
		public function write(XmlObject $xml_object, $encoding = 'UTF-8') {
			$first = false;
			if ($this->writer === null) {
				$this->writer = new XMLWriter();
				$this->writer->openMemory();
				$this->writer->setIndent(true);
				$this->writer->setIndentString("\t");
				$this->writer->startDocument('1.0', $encoding);
				$this->writer->startElement($xml_object->tagName());
				foreach ($xml_object->attr() as $attr => $value) {
					$this->writer->startAttribute($attr);
					$this->writer->text($value);
					$this->writer->endAttribute();
				}
				$text = $xml_object->text();
				if (is_string($text) || is_numeric($text)) {
					$this->writer->text($text);
				}
				$first = true;
			}
			foreach ($xml_object->elements() as $object) {
				if (is_array($object)) {
					foreach ($object as $object) {
						$this->writer->startElement($object->tagName());
						foreach ($object->attr() as $attr => $value) {
							$this->writer->startAttribute($attr);
							$this->writer->text($value);
							$this->writer->endAttribute();
						}
						if (is_array($object->elements()) && count($object->elements()) > 0) {
							$this->write($object);
						}
						$text = $object->text();
						if (is_string($text) || is_numeric($text)) {
							$this->writer->text($text);
						}
						$this->writer->endElement();
					}
				} else {
					$this->writer->startElement($object->tagName());
					foreach ($object->attr() as $attr => $value) {
						$this->writer->startAttribute($attr);
						$this->writer->text($value);
						$this->writer->endAttribute();
					}
					if (is_array($object->elements()) && count($object->elements()) > 0) {
						$this->write($object);
					}
					$text = $object->text();
					if (is_string($text) || is_numeric($text)) {
						$this->writer->text($text);
					}
					$this->writer->endElement();
				}
			}
			if ($first === true) {
				$this->writer->endElement();
				$this->writer->endDocument();
				return $this->writer->outputMemory();
			}
		}
	}	