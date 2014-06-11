<?php

	function pre($obj, $title = 'Debug') {
		static $call_count = 1;
		echo '<pre class="ui-widget-content" style="border: 1px solid #F00; padding: 10px; text-align: left;">';
		echo '<div style="font-weight: bold;">'.$title.'</div>';
		echo '<div style="font-weight: bold;">Call:<span style="color: #F00;">'.$call_count.'</span></div>';
		print_r($obj);
		echo '</pre>';
		$call_count++;
	}
	function Hughes($obj, $title = '') {
		$title = $title != '' ? $title : 'Hughes Debugging';
		pre($obj, $title);
	}
	function nameToUpper($str, $firstLower = false) {
		$str = ucwords(str_replace('_', ' ', $str));
		$str = str_replace(' ', '', $str);
		if ($firstLower) {
			$str = strtolower(substr($str, 0, 1)).substr($str, 1);
		}
		return $str;
	}
	function nameToUnderline($name) {
		$part = splitUpper($name);
		return strtolower(join('_', $part));
	}
	function splitUpper($str, $allowFirstLower = true) {
		$arr = str_split($str);
		$cnt = count($arr);
		$active = false;
		$rtval = array();
		$temp = '';
		for ($i = 0; $i < $cnt; $i++) {
			if (ord($arr[$i]) >= 65 && ord($arr[$i]) <= 90) {
				$active = true;
				if ($temp != '') {
					$rtval[] = $temp;
					$temp = '';
				}
			}
			if ($allowFirstLower) {
				$temp .= $arr[$i];
			} elseif ($active) {
				$temp .= $arr[$i];
			}
		}
		if ($temp != '')
			$rtval[] = $temp;
		return $rtval;
	}
	