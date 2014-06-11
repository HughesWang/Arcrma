<?php

	interface IConnector {
		public function setConfig($config);
		public function setData($data);
		public function prepare();
		public function request();
	}	