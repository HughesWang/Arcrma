<?php

	abstract class UnitTest {
		protected $_handler = null;
		protected $_prefix = 'Unit_';
		protected function render($content, $title = null) {
			Hughes($content, $title);
		}
		abstract protected function output($content, $title = null);
	}	