<?php

	interface IHandler {
		public function setConfig($config);
		public function isPrepared();
		public function hasErrors();
	}	