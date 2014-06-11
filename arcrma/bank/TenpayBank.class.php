<?php

	require_once ARCRMA_PATH.'/bank/Bank.interface.php';

	class TenpayBank implements Bank {
		const BOC_FP = 'BOC_FP'; // 中國銀行
		const ABC_FP = 'ABC_FP'; // 中國農業銀行
		const ICBC_FP = 'ICBC_FP'; // 中國工商銀行
		const CCB_FP = 'CCB_FP'; // 中國建設銀行
		const PAB_FP = 'PAB_FP'; // 平安銀行
		const CEB_FP = 'CEB_FP'; // 中國光大銀行
	}	