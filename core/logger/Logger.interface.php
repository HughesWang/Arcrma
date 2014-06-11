<?php

	interface Logger {
		public function addMessage();
		public function getMessages();
		public function removeMessages();
	}	