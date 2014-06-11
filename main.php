<?php

	error_reporting(E_ALL);

	date_default_timezone_set('Asia/Taipei');

	define('PHT_BASE_PATH', getcwd());

	define('CORE_PATH', PHT_BASE_PATH.'/core');

	define('ARCRMA_PATH', PHT_BASE_PATH.'/arcrma');
	
	require_once PHT_BASE_PATH.'/Functions.php';

	require_once ARCRMA_PATH.'/test/ArcrmaUnitTest.class.php';


	try {
		$tester = new ArcrmaUnitTest();
		/*
		$tester->product('create');
		$tester->product('delete');
		$tester->product('query');
		$tester->product('batch');
		$tester->order('create');
		$tester->order('query');
		$tester->order('refund');
		$tester->order('refund_query');
		$tester->exchange('rate');
		$tester->delivery('notify');
		*/
	} catch (Exception $e) {
		$report = array(
				'File' => $e->getFile(),
				'Line' => $e->getLine(),
				'Code' => $e->getCode(),
				'Message' => $e->getMessage()
		);
	}

	