<?php

	require_once CORE_PATH.'/UnitTest.class.php';

	require_once ARCRMA_PATH.'/ArcrmaProccessor.class.php';
	require_once ARCRMA_PATH.'/delivery/ArcrmaDeliveryObject.class.php';
	require_once ARCRMA_PATH.'/exchange/ArcrmaExchangeObject.class.php';
	require_once ARCRMA_PATH.'/order/ArcrmaOrderObject.class.php';
	require_once ARCRMA_PATH.'/order/ArcrmaOrderQueryObject.class.php';
	require_once ARCRMA_PATH.'/order/ArcrmaOrderRefundObject.class.php';
	require_once ARCRMA_PATH.'/order/ArcrmaOrderRefundQueryObject.class.php';
	require_once ARCRMA_PATH.'/product/ArcrmaProductObject.class.php';
	require_once ARCRMA_PATH.'/product/ArcrmaProductDeleteObject.class.php';
	require_once ARCRMA_PATH.'/product/ArcrmaProductQueryObject.class.php';

	require_once ARCRMA_PATH.'/bank/TenpayBank.class.php';

	class ArcrmaUnitTest extends UnitTest {
		private $store = '53723954A';
		private $_product_function = array(
				'create', 'delete', 'query'
		);
		public function product($unit_name = null) {
			self::execute($unit_name, $prefix = 'Product_');
		}
		public function order($unit_name = null) {
			self::execute($unit_name, $prefix = 'Order_');
		}
		public function delivery($unit_name = null) {
			self::execute($unit_name, $prefix = 'Delivery_');
		}
		public function exchange($unit_name = null) {
			self::execute($unit_name, $prefix = 'Exchange_');
		}
		private function Unit_Delivery_Notify() {
			$sample_query = new ArcrmaProccessor($this->store);
			$delivery_handler = $sample_query->getInstance('delivery');

			$delivery_conds = new ArcrmaDeliveryObject();
			$delivery_conds->store = $this->store;
			$delivery_conds->pno = 'O201312170000002';
			$delivery_conds->delv_addr = '台北市';
			$delivery_conds->delv_time = date('YmdHi');
			$delivery_conds->count = 2;
			$delivery_conds->validate();

			$result = $delivery_handler->notify($delivery_conds);
			$result->result = json_decode($result->result);
			self::output($result, '取貨通知');
		}
		private function Unit_Exchange_Rate() {
			$sample_query = new ArcrmaProccessor($this->store);
			$exchange_handler = $sample_query->getInstance('exchange');
			$result = $exchange_handler->rate();
			$result->result = json_decode($result->result);
			self::output($result, '查詢匯率');
		}
		private function Unit_Product_Create() {
			$ap_obj = new ArcrmaProductObject();
			$prod = sprintf('PS%010s', date('mdHis'));
			$params = array(
					'store' => $this->store,
					'prod' => $prod,
					'name' => sprintf('測試商品(%s)', $prod),
					'unit_price' => 10,
					'desc' => sprintf('測試商品(%s)的介紹', $prod),
					'tags' => array('facebook', 'google plus', 'plunk'),
					'image' => 'http://cnodejs.org/public/images/logo.png',
					'scids' => '測試類別',
					'weight' => 3.47,
					'length' => 13,
					'width' => 13,
					'height' => 15,
					'ship_fee_mode' => 0,
					'props' => array('color' => array('blue', 'red', 'green'), 'size' => array('x', 'l', 'm', 's')),
					'showcase' => 0,
					'href' => 'http://www.pht-studio.com',
					'stock_addr' => null
			);
			$ap_obj->setData($params);
			$sample_query = new ArcrmaProccessor($this->store);
			$product_result = $sample_query->getInstance('product')->addProduct($ap_obj)->prepare()->create();
			self::output($product_result, '新增產品');
		}
		private function Unit_Product_Delete() {
			//=========================================================================
			// UAT 無法刪除商品。亞克瑪的人說在正式機上是可以運作的，待查證
			//=========================================================================
			$sample_query = new ArcrmaProccessor($this->store);
			$product_conds = new ArcrmaProductDeleteObject();
			$product_conds->store = $this->store;
			$product_conds->prod = 'PM1218153354';
			$product_result = $sample_query->getInstance('product')->removeProduct($product_conds)->prepare()->delete();
			self::output($product_result, '產品下架');
		}
		private function Unit_Product_Batch() {
			$sample_query = new ArcrmaProccessor($this->store);
			$product_proccessor = $sample_query->getInstance('product');

			$create_obj = new ArcrmaProductObject();
			$params = array(
					'store' => $this->store,
					'prod' => '%s',
					'name' => '測試商品(%s)',
					'unit_price' => 10,
					'desc' => '測試商品(%s)的介紹',
					'tags' => array('facebook', 'google plus', 'plunk'),
					'image' => 'http://cnodejs.org/public/images/logo.png',
					'scids' => '測試類別',
					'weight' => 3.47,
					'length' => 13,
					'width' => 13,
					'height' => 15,
					'ship_fee_mode' => 0,
					'props' => array('color' => array('blue', 'red', 'green'), 'size' => array('x', 'l', 'm', 's')),
					'showcase' => 0,
					'href' => 'http://www.pht-studio.com',
					'stock_addr' => null
			);
			$create_obj->setData($params);
			static $num = 1;
			$amount = 1;
			$prod = sprintf('PM%010s', date('mdHis'));
			$products = array();
			do {
				$prod = 'PM'.(intval(substr($prod, 2, strlen($prod) - 2)) + 1);
				$product = clone $create_obj;
				$product->prod = sprintf($product->prod, $prod);
				$product->name = sprintf($product->name, $prod);
				$product->desc = sprintf($product->desc, $prod);
				$product_proccessor->addProduct($product);
				$products[] = $product;
			} while ($num++ < $amount);
			$product_result = $product_proccessor->prepare()->product_batch();
			$product_result->result = json_decode($product_result->result);
			self::output($product_result, '產品批次處理');
			foreach ($products as $product) {
				$d = new ArcrmaProductDeleteObject();
				$d->store = $product->store;
				$d->prod = $product->prod;
				$product_proccessor->removeProduct($d);
			}
			$product_result = $product_proccessor->prepare()->product_batch();
			$product_result->result = json_decode($product_result->result);
			self::output($product_result, '產品批次處理');
		}
		private function Unit_Product_Query() {
			$this->product_query_single();
			$this->product_query_all();
		}
		private function product_query_all() {
			$sample_query = new ArcrmaProccessor($this->store);
			$product_group = $sample_query->getInstance('product')->query()->getProducts();
			self::output($product_group, '查詢全部產品');
		}
		private function product_query_single() {
			$sample_query = new ArcrmaProccessor($this->store);
			$conds = new ArcrmaProductQueryObject();
			$conds->store = $this->store;
			$conds->prod = '111954000001';
			$conds->validate();
			$product_group = $sample_query->getInstance('product')->query($conds)->getProducts();
			self::output($product_group, '查詢單一產品');
		}
		private function Unit_Order_Create() {
			$ao_obj = new ArcrmaOrderObject();
			$params = array(
					'store' => $this->store,
					'channel' => Bank::Tenpay, //default : tenpay
					'bank' => TenpayBank::BOC_FP,
					'pno' => 'O2014010701261',
//					'amt' => 1680,
//					'ship_free' => 1,
					'return_url' => 'http://o2o.com.tw/arcrma/order_return.php',
//					'return_url' => 'http://o2o.com.tw/pht/payment/arcrma/return',
//					'pcode' => null,
					'ttime' => '20140104164347',
//				'pdesc' => null,
//				'tel' => null,
//				'tel2' => null,
//				'receiver' => null,
//				'email' => null,
//				'area' => null,
//				'addr' => null,
//				'timeout' => null,
//				'count' => 1,
				/*
				 * pid0			string	20	商品 1 編號								Y
				 * qty0			integer		商品 1 數量								Y
				 * pid1			string	20	商品 2 編號								Y
				 * qty1			integer		商品 2 數量								Y
				 */
//				'sso_ref' => null
			);
			$ao_obj->setData($params);

			$proccessor = new ArcrmaProccessor($this->store);
			$order_handler = $proccessor->getInstance('order');

			$product_conds = new ArcrmaProductQueryObject();
			$product_conds->store = $this->store;
			$product_conds->prod = '7b2d33fd8f13';

			$products = $proccessor->getInstance('product')->query($product_conds->validate())->getProducts();

			foreach ($products as $product) {
				$ao_obj->addProduct($product->validate());
			}

			$result = $order_handler->addOrder($ao_obj)->prepare()->create();

			self::output($result, '新建訂單');
		}
		private function order_query_all() {
			$proccessor = new ArcrmaProccessor($this->store);
			$order_handler = $proccessor->getInstance('order');
			$order_conds = new ArcrmaOrderQueryObject();
			$order_conds->store = $this->store;
//			$order_conds->pno = 'O201312130000012';
//			$order_conds->date;
			$order_conds->fdate = '20131201'; // 起
			$order_conds->tdate = '20131231'; // 迄
//			$order_conds->channel_order;
			$order_conds->validate();
			$result = $order_handler->query($order_conds);
			$result->result = json_decode($result->result);
			self::output($result, '查詢全部訂單');
		}
		private function order_query_single() {
			$proccessor = new ArcrmaProccessor($this->store);
			$order_handler = $proccessor->getInstance('order');

			$order_conds = new ArcrmaOrderQueryObject();
			$order_conds->store = $this->store;
			$order_conds->pno = 'O201312130000012';
//			$order_conds->date;
//			$order_conds->fdate = '20131201'; // 起
//			$order_conds->tdate = '20131231'; // 迄
//			$order_conds->channel_order;
			$order_conds->validate();
			$result = $order_handler->query($order_conds);
			$result->result = json_decode($result->result);
			self::output($result, '查詢單一訂單');
		}
		private function Unit_Order_Query() {
			self::order_query_all();
			self::order_query_single();
		}
		private function Unit_Order_Refund() {
			$proccessor = new ArcrmaProccessor($this->store);
			$order_handler = $proccessor->getInstance('order');

			$refund_conds = new ArcrmaOrderRefundObject();
			$refund_conds->store = $this->store;
			$refund_conds->pno = 'O201312130000012';
			$refund_conds->amt = 10;
			$refund_conds->desc = 'For test';
			$refund_conds->validate();

			$result = $order_handler->refund($refund_conds);
			$result->result = json_decode($result->result);
			self::output($result, '訂單退款');
		}
		private function Unit_Order_RefundQuery() {
			$proccessor = new ArcrmaProccessor($this->store);
			$order_handler = $proccessor->getInstance('order');
			$refund_conds = new ArcrmaOrderRefundQueryObject();
			$refund_conds->store = $this->store;
			$refund_conds->pno = 'O201312130000007';
//			$refund_conds->refund_no;
			$refund_conds->validate();
			$result = $order_handler->refund_query($refund_conds);
			$result->result = json_decode($result->result);
			self::output($result, '查詢退款訂單');
		}
		private function execute($unit_name = null, $prefix = null) {
			if (null === $unit_name) {
				throw new Exception('You should chose your unit test as you want !');
			}
			$func_name = $this->_prefix.$prefix.nameToUpper($unit_name);
			if (method_exists($this, $func_name)) {
				$result = call_user_func_array(array($this, $func_name), array());
			} else {
				throw new Exception(sprintf('Do not have this unit test [ %s ]', $func_name));
			}
		}
		protected function output($content, $title = null) {
			parent::render($content, $title);
		}
	}	