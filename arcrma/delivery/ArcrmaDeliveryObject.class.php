<?php

	require_once ARCRMA_PATH.'/delivery/ArcrmaAbstractDelivery.class.php';

	/**
	 * arcrma order exchange rate object
	 *
	 * store			string	12	特店ID				Y
	 * pno				string	16	特店ID				Y
	 * delv_addr		string	256	特店ID				Y
	 * delv_time		string	12	特店ID				Y
	 *								format: yyyyMMddhhmm
	 * count			int			特店ID				Y
	 */
	class ArcrmaDeliveryObject extends ArcrmaAbstractDelivery {
		public $store = null;
		public $pno = null;
		public $delv_addr = null;
		public $delv_time = null;
		public $count = null;
		private $_valided = false;
		private $required_fields = array('store', 'pno', 'delv_addr', 'delv_time', 'count');
		public function __construct($order = null) {
			parent::__construct();
			if (null !== $order) {
				$reflect = new ReflectionClass($this);
				$properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
				$allow_fields = array();
				foreach ($properties as $property) {
					$allow_fields[] = $property->getName();
				}
				foreach ($order as $index => $value) {
					if (in_array($index, $allow_fields)) {
						if ($value == 'true' || $value == 'false') {
							$value = $value == 'true';
						}
						$this->$index = $value;
					}
				}
			}
		}
		public function setData(array $params = array()) {
			if (is_array($params) and !empty($params)) {
				foreach ($params as $index => $value) {
					if ($value === null or $value === '') {
						continue;
					}
					$this->{$index} = $value;
				}
			}
			return $this;
		}
		public function getValue($field_name, $output_format = false) {
			$value = null;
			if (isset($this->$field_name)) {
				$value = $this->$field_name;
			}
			return $value;
		}
		public function getValues($output_format = false) {
			$reflect = new ReflectionClass($this);
			$public_properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
			$output_vars = array();
			foreach ($public_properties as $property) {
				if ($property->class !== __CLASS__) {
					continue;
				}
				$field_name = $property->getName();
				$output_vars[$field_name] = $this->getValue($field_name, $output_format);
			}
			$use_extra_field = false;
			if ($use_extra_field) {
				$properties = $reflect->getProperties();
				$original_vars = array();
				foreach ($properties as $property) {
					if ($property->class !== __CLASS__) {
						continue;
					}
					$field_name = $property->getName();
					$original_vars[$field_name] = $this->getValue($field_name, $output_format);
				}
				$extra_vars = array_diff_assoc(get_object_vars($this), $original_vars);
				$output_vars = array_merge($output_vars, $extra_vars);
			}
			return $output_vars;
		}
		private function resetValidate() {
			$this->_valided = false;
			$this->removeErrors();
		}
		public function validate() {
			$this->resetValidate();
			$reflect = new ReflectionClass($this);
			$properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
			foreach ($properties as $property) {
				if ($property->class !== get_class($this)) {
					continue;
				}
				if (!is_null($this->{$property->name}) || in_array($property->name, (array) $this->required_fields)) {
					$valid_func_name = 'valid'.nameToUpper($property->name);
					if (method_exists($this, $valid_func_name)) {
						$result = call_user_func_array(array($this, $valid_func_name), array($this->{$property->name}));
						if (!$result) {
							$this->addError(sprintf('%s had been valided fault.', $property->name));
						}
					} else {
						throw new Exception(sprintf('Error : %s method is not exists.', $valid_func_name));
					}
				}
			}
			$this->_valided = !$this->hasErrors();
			return $this;
		}
		public function isValided() {
			return (boolean) $this->_valided;
		}
		private function validStore($check) {
			return (boolean) (is_string($check) and Validator::maxLength($check, 12));
		}
		private function validPno($check) {
			return (boolean) (is_string($check) and Validator::maxLength($check, 16));
		}
		private function validDelvAddr($check) {
			return (boolean) (is_string($check) and Validator::maxLength($check, 256));
		}
		private function validDelvTime($check) {
			return (boolean) (is_string($check) and Validator::maxLength($check, 12));
		}
		private function validCount($check) {
			return (boolean) (Validator::numeric($check) and intval($check) >= 0);
		}
	}	