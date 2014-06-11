<?php
	
	require_once ARCRMA_PATH.'/order/ArcrmaAbstractOrder.class.php';

	/**
	 * arcrma order basic object
	 *
	 * store		string	12	特店ID Y 
	 * channel		string	30	tenpay(財付通) default: tenpay	N
	 * bank			string	8	如為財付通可使用下列銀行付款：		N
	 * 							BOC_FP	中國銀行
	 * 							ABC_FP	中國農業銀行
	 * 							ICBC_FP	中國工商銀行
	 * 							CCB_FP	中國建設銀行
	 * 							PAB_FP	平安銀行
	 * 							CEB_FP	中國光大銀行
	 * pno			string	16	特店訂單編號(該特店中是唯一值)				Y
	 * amt			integer		訂單總額(新台幣金額)						Y
	 * ship_free	integer		運費計算	0: 一般 1:強制免運費(運費由特店支付)	N
	 * return_url	string	128	Return URL(交易成功要導回的頁面)			Y
	 * pcode		string		md5 將 全部參數依順序進行編碼				Y
	 * ttime		string	14	伺服器交易時間 format:yyyyMMddHHmmss		Y
	 * pdesc		string	128	特店對於此訂單的商品描述說明					N
	 * tel			string	16	收貨人聯絡電話								N
	 * tel2			string	16	收貨人聯絡電話2							N
	 * receiver		string	12	收貨人姓名								N
	 * email		string	96	收貨人 email								N
	 * area			string	10	送貨地區代碼								N
	 * addr			string	128	送貨地區【送貨區域 省/市/區】+ 送貨地址		N
	 * timeout		string	4	設定交易截止時間,預設為 12 小時				N
	 * 							5m:5 分鐘
	 * 							10m:10 分鐘
	 * 							15m:15 分鐘
	 * 							30m:30 分鐘
	 * 							1h :1 小時
	 * 							2h:2 小時
	 * 							3h:3 小時
	 * 							5h:5 小時
	 * 							10h:10 小時
	 * 							12h: 12 小時
	 * count		integer		商品明細項								Y
	 * 							例如 count 為 2,應有 pid0,qty0,pid1,qty1
	 * pid0			string	20	商品 1 編號								Y
	 * qty0			integer		商品 1 數量								Y
	 * pid1			string	20	商品 2 編號								Y
	 * qty1			integer		商品 2 數量								Y
	 * sso_ref		string	64	唯一的快捷登入交易代號						N
	 * 							用來回特店平台確認是否為特店發起的登入交易
	 * 							註:該參數使用取決財於付通是否支援此功能。
	 */
	class ArcrmaOrderObject extends ArcrmaAbstractOrder {
		public $store = null;
		public $channel = null;
		public $bank = Bank::Tenpay;
		public $pno = null;
		public $amt = 0;
		public $ship_free = null;
		public $return_url = null;
		public $pcode = null;
		public $ttime = null;
		public $pdesc = null;
		public $tel = null;
		public $tel2 = null;
		public $receiver = null;
		public $email = null;
		public $area = null;
		public $addr = null;
		public $timeout = ArcrmaTrade::TIME_LIMIT_12H;
//		public $count = null;
		public $sso_ref = null;
		private $_valided = false;
		private $required_fields = array('store', 'pno', 'amt', 'return_url', 'pcode', 'ttime', 'count');
		private $_orders = array();
		private $_order_valided = false;
		private $_products = array();
		private $_product_valided = false;
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
			$extra_vars = array('count');
			if ($output_format and in_array($field_name, $extra_vars)) {
				switch ($field_name) {
					case 'count':
						$value = array();
						foreach ((array) $this->getProducts() as $prod => $products) {
							$value[] = array(
									'pid' => $prod,
									'qty' => count($products)
							);
						}
						break;
					default:
						break;
				}
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
			if ($output_format) {
				$extra_field_count = 'count';
				$result = $this->getValue($extra_field_count, $output_format);
				$count = count($result);
				if ($count > 0) {
					$output_vars[$extra_field_count] = $count;
					foreach ((array) $result as $index => $product_info) {
						$output_vars['pid'.$index] = $product_info['pid'];
						$output_vars['qty'.$index] = $product_info['qty'];
					}
				} else {
					$message = sprintf('[ pno : %s ] This order do not have any product can be dump for send a request.', $this->pno);
					throw new Exception($message);
				}
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
		public function addProduct(ArcrmaProductObject $product = null) {
			$this->_products[$product->prod][] = $product;
		}
		public function hasProducts() {
			return !empty($this->_products);
		}
		public function getProducts() {
			return $this->_products;
		}
		public function addOrder(ArcrmaOrderObject $order = null) {
			$this->_orders[$order->pno] = $order;
		}
		public function hasOrders() {
			return !empty($this->_orders);
		}
		public function getOrders() {
			return $this->_orders;
		}
		private function resetValidate() {
			$this->_valided = false;
			$this->_order_valided = false;
			$this->_product_valided = false;
			$this->removeErrors();
		}
		public function validate($on = 'create') {
			$this->resetValidate();
			switch ($on) {
				case 'create':

					$this->prepareProducts();
					$this->prepareOrders();
					$this->preparePcode();

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
					break;
				default:
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
		private function validChannel($check) {
			return (boolean) (is_string($check) and Validator::maxLength($check, 30));
		}
		private function validBank($check) {
			return (boolean) (is_string($check) and Validator::maxLength($check, 8));
		}
		private function validPno($check) {
			/* 唯一值 */
			return (boolean) (is_string($check) and Validator::maxLength($check, 16));
		}
		private function validAmt($check) {
			return (boolean) (Validator::numeric($check) and intval($check) >= 0);
		}
		private function validShipFree($check) {
			return (boolean) Validator::inList($check, array(0, 1));
		}
		private function validReturnUrl($check) {
			return (boolean) Validator::url($check);
		}
		private function validPcode($check) {
			return (boolean) is_string($check);
		}
		private function validTtime($check) {
			return (boolean) Validator::date($check);
		}
		private function validPdesc($check) {
			return (boolean) (is_string($check) and Validator::maxLength($check, 128));
		}
		private function validTel($check) {
			return (boolean) (is_string($check) and Validator::maxLength($check, 16));
		}
		private function validTel2($check) {
			return (boolean) (is_string($check) and Validator::maxLength($check, 16));
		}
		private function validReceiver($check) {
			return (boolean) (is_string($check) and Validator::maxLength($check, 12));
		}
		private function validEmail($check) {
			return (boolean) (is_string($check) and Validator::maxLength($check, 96) and Validator::email($check));
		}
		private function validArea($check) {
			return (boolean) (is_string($check) and Validator::maxLength($check, 10));
		}
		private function validAddr($check) {
			return (boolean) (is_string($check) and Validator::maxLength($check, 128));
		}
		private function validTimeout($check) {
			$reflect = new ReflectionClass(new ArcrmaTrade());
			return (boolean) Validator::inList($check, $reflect->getConstants());
		}
		private function validSsoRef($check) {
			return (boolean) (is_string($check) and Validator::maxLength($check, 64));
		}
		private function prepareProducts() {
			$status = false;
			if (!$this->hasProducts()) {
				$this->addError(sprintf('[ pno : %s ] This order did not have any products', $this->pno));
				return $status;
			}
			try {
				$valid_queue = array();
				foreach ((array) $this->getProducts() as $group_name => $product_groups) {
					foreach ($product_groups as $index => $product) {

						/* proccess amount start */
						$this->amt += intval($product->getValue('unit_price'));
						/* proccess amount end */

						array_push($valid_queue, $product);
					}
				}
				if (empty($valid_queue)) {
					$this->addError(sprintf('[ pno : %s ] This order did not have any products to be valided', $this->pno));
					return $status;
				}

				foreach ($valid_queue as $product) {
					if (!$product->isValided()) {
						$product->validate();
					}
					if ($product->hasErrors()) {
						$message = '[ prod : %s ] was not valided.';
						$this->addError(sprintf($message, $product->prod));
						continue;
					}
				}
				$status = (boolean) !$this->hasErrors();
			} catch (Exception $e) {
				$status = false;
				$this->addError('[ pno : %s ] Exception : '.$e->getMessage(), $this->pno);
			}
			return $status;
		}
		private function prepareOrders() {
			$status = false;
			if (!$this->hasOrders()) {
				return $status;
			}
			try {
				foreach ((array) $this->getOrders() as $order_pno => $order) {
					if (!$order->isValided()) {
						$order->validate('create');
					}
					if ($order->hasErrors()) {
						$this->addError('[ %s >> %s ] was not valided.'.$this->pno, $order->pno);
					}
				}
				$status = (boolean) !$this->hasErrors();
			} catch (Exception $e) {
				$status = false;
				$this->addError('[ pno : %s ] Exception : '.$e->getMessage(), $this->pno);
			}
			return $this;
		}
		private function preparePcode() {
			$values = $this->getValues(true);
			ksort($values);
			$this->pcode = md5(implode('', $values));
			return $this;
		}
	}	