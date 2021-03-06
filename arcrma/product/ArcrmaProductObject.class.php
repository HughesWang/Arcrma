<?php

	require_once ARCRMA_PATH.'/product/ArcrmaAbstractProduct.class.php';

	/**
	 * arcrma product basic object
	 *
	 * store string 12 特店ID Y 
	 * prod string 12 商品编號 Y 
	 * name string 60 商品名稱 Y 
	 * unit_price int 商品價格 Y 台幣的價格，單位：元。 
	 * desc string 1000 商品描述 N 
	 * tags string 128 搜尋標籤 N 最多5個，以逗號分隔 
	 * image string 128 商品圖檔URL N 圖片類型：jpg、jpeg、png，不支持gif；最大：500k
	 * scids string 128 商品分類代號 N 商品類別
	 * weight float 重量 Y 單位: 公斤 //加入格式範例 ex：3.5
	 * length int 長度 Y 單位: 公分 //加入格式範例 ex：12
	 * width int 寬度 Y 單位: 公分 //加入格式範例 ex：12
	 * height int 高度 Y 單位: 公分 //加入格式範例 ex：12
	 * ship_fee_mode int 運費計算 Y 0: 免運送, 1:價格不含運費, 2:價格已含運費
	 * props string 128 屬性值 N 屬性項、屬性值。不同屬性項間以分號分隔，不同屬性值間以逗號分隔。如：顏色:红色,白色;尺寸:35,36,37;
	 * showcase int 商家推薦 N 0: 不推薦, 1推薦 無此欄位視為不推薦
	 * href string 128 商品連結網址 N 商品連結絕對位址
	 * stock_addr string 128 預設取貨地址 N 商品可設定預設取貨地址,若不填則會以特店之預設取貨地址為訂單取貨地址作預設值
	 */
	class ArcrmaProductObject extends ArcrmaAbstractProduct {
		public $store = null;
		public $prod = null;
		public $name = null;
		public $unit_price = null;
		public $desc = null;
		public $tags = null;
		public $image = null;
		public $scids = null;
		public $weight = null;
		public $length = null;
		public $width = null;
		public $height = null;
		public $ship_fee_mode = null;
		public $props = null;
		public $showcase = null;
		public $href = null;
		public $stock_addr = null;
		private $_valided = false;
		private $required_fields = array('store', 'prod', 'name', 'unit_price', 'weight', 'length', 'width', 'height', 'ship_fee_mode');
		public function __construct($product = null) {
			parent::__construct();
			if (null !== $product) {
				$reflect = new ReflectionClass($this);
				$properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
				$allow_fields = array();
				foreach ($properties as $property) {
					$allow_fields[] = $property->getName();
				}
				foreach ($product as $index => $value) {
					if (in_array($index, $allow_fields)) {
						if ($value == 'true' || $value == 'false') {
							$value = $value == 'true';
						}
						switch ($index) {
							case 'tags':
								$this->$index = explode(',', $value);
								break;
							case 'props':
								$array = array();
								if (empty($value)) {
									$this->$index = $array;
									break;
								}
								$cond = substr($value, -1, 1);
								if ($cond == ';') {
									$value = substr($value, 0, (strlen($value) - 1));
								}
								$value = explode(';', $value);
								foreach ($value as $category) {
									list($category_name, $category_value) = explode(':', $category);
									$array[$category_name] = explode(',', $category_value);
								}
								$this->$index = $array;
								break;
							default :
								$this->$index = $value;
								break;
						}
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
				if ($output_format) {
					switch ($field_name) {
						case 'tags':
							$value = implode(',', $value);
							break;
						case 'props':
							$temp = array();
							foreach ((array) $value as $category_name => $category) {
								$temp[] = sprintf('%s:%s', $category_name, implode(',', $category));
//								$temp[] = sprintf('%s', implode(',', $category));
							}
							$value = implode(';', $temp);
							$value .= ';';
							break;
					}
				}
			}
			return $value;
		}
		public function getValues($output_format = false) {
			$reflect = new ReflectionClass($this);
			$properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
			$output_vars = array();
			foreach ($properties as $property) {
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
		public function validate($on = 'create') {
			$this->resetValidate();
			switch ($on) {
				case 'create':
					$reflect = new ReflectionClass($this);
					$properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
					foreach ($properties as $property) {
						if ($property->class !== get_class($this)) {
							continue;
						}
						if ((!is_null($this->{$property->name}) and $this->{$property->name} != '') or in_array($property->name, (array) $this->required_fields)) {
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
					break;
				case 'remove':
					$require_fields = array('store', 'prod');
					foreach ($require_fields as $field) {
						$valid_func_name = 'valid'.nameToUpper($field);
						if (method_exists($this, $valid_func_name)) {
							$result = call_user_func_array(array($this, $valid_func_name), array($this->$field));
							if (!$result) {
								$this->addError(sprintf('%s had been valided fault.', $field));
							}
						} else {
							throw new Exception(sprintf('Error : %s method is not exists.', $valid_func_name));
						}
					}
					break;
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
		private function validProd($check) {
			return (boolean) (is_string($check) and Validator::maxLength($check, 12));
		}
		private function validName($check) {
			return (boolean) (is_string($check) and Validator::maxLength($check, 60));
		}
		private function validUnitPrice($check) {
			return (boolean) (Validator::numeric($check) and intval($check) >= 0);
		}
		private function validDesc($check) {
			return (boolean) (is_string($check) and Validator::maxLength($check, 1000));
		}
		private function validTags($check) {
			return (boolean) (count($check) <= 5 and Validator::maxLength(implode(',', $check), 128));
		}
		private function validImage($check) {
			//@todo 圖片類型：jpg、jpeg、png，不支持gif；最大：500k
			return (boolean) (Validator::maxLength($check, 128) and Validator::url($check));
		}
		private function validScids($check) {
			return (boolean) (is_string($check) and Validator::maxLength($check, 128));
		}
		private function validWeight($check) {
			return (boolean) (floatval($check) >= 0);
		}
		private function validLength($check) {
			return (boolean) (Validator::numeric($check) and intval($check) >= 0);
		}
		private function validWidth($check) {
			return (boolean) (Validator::numeric($check) and intval($check) >= 0);
		}
		private function validHeight($check) {
			return (boolean) (Validator::numeric($check) and intval($check) >= 0);
		}
		private function validShipFeeMode($check) {
			return (boolean) Validator::inList($check, array(0, 1, 2));
		}
		private function validProps($check) {
			$temp = array();
			foreach ($check as $category_name => $category) {
				$temp[] = sprintf('%s:%s', $category_name, implode(',', $category));
			}
			$check = implode(';', $temp);
			return (boolean) Validator::maxLength($check, 128);
		}
		private function validShowcase($check) {
			return (boolean) Validator::inList(intval($check), array(0, 1));
		}
		private function validHref($check) {
			return (boolean) Validator::url($check);
		}
		private function validStockAddr($check) {
			return (boolean) Validator::maxLength($check, 128);
		}
	}	