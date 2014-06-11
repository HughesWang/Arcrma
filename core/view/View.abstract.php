<?php

	abstract class AbstractView {
		protected $_render = null;
		abstract public function output($output, $type);
	}	