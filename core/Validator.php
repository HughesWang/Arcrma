<?php

	class Validator {
		private function __construct() {
			
		}
		public static function date($check) {
			$timestamp = strtotime($check);
			return checkdate(date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp));
		}
		public static function time($check) {
			return (boolean) preg_match('%^((0?[1-9]|1[012])(:[0-5]\d){0,2}([AP]M|[ap]m))$|^([01]\d|2[0-3])(:[0-5]\d){0,2}$%', $check);
		}
		public static function datetime($check, $dateFormat = 'ymd', $regex = null) {
			$valid = false;
			$parts = explode(' ', $check);
			if (!empty($parts) && count($parts) > 1) {
				$time = array_pop($parts);
				$date = implode(' ', $parts);
				$valid = Validator::date($date) && Validator::time($time);
			}
			return $valid;
		}
		public static function phone($check, $country = 'taiwan') {
			$valid = false;
			switch ($country) {
				case 'taiwan':
				default:
					$valid = (boolean) preg_match('/^[0-9]{6,10}$/', $check, $m);
					break;
			}
			return $valid;
		}
		public static function mobile($check, $country = 'taiwan') {
			$valid = false;
			switch ($country) {
				case 'taiwan':
				default:
					$valid = (boolean) preg_match('/^09[0-9]{8}$/', $check, $m);
					break;
			}
			return $valid;
		}
		public static function strlen($check, $min = 0, $max = 0) {
			return mb_strlen($check) >= $min && ($max == 0 || mb_strlen($check) <= $max);
		}
		public static function inList($check, array $list) {
			return in_array($check, $list);
		}
		public static function minLength($check, $min) {
			return mb_strlen($check) >= $min;
		}
		public static function maxLength($check, $max) {
			return mb_strlen($check) <= $max;
		}
		public static function range($check, $lower = null, $upper = null) {
			if (!is_numeric($check)) {
				return false;
			}
			if (isset($lower) && isset($upper)) {
				return ($check >= $lower && $check <= $upper);
			}
			if (isset($lower)) {
				return ($check >= $lower);
			}
			if (isset($upper)) {
				return ($check <= $upper);
			}
			return is_finite($check);
		}
		public static function notEmpty($check) {
			if (empty($check) && $check != '0') {
				return false;
			}
			return (boolean) preg_match('/[^\s]+/m', $check);
		}
		public static function numeric($check) {
			return is_numeric($check);
		}
		public static function alphaNumeric($check) {
			if (empty($check) && $check != '0') {
				return false;
			}
			return (boolean) preg_match('/^[\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}\p{Nd}]+$/mu', $check);
		}
		public static function boolean($check) {
			return in_array($check, array(0, 1, '0', '1', true, false), true);
		}
		public static function comparison($check1, $operator = null, $check2 = null) {
			if (is_array($check1)) {
				extract($check1, EXTR_OVERWRITE);
			}
			$operator = str_replace(array(' ', "\t", "\n", "\r", "\0", "\x0B"), '', strtolower($operator));
			switch ($operator) {
				case 'isgreater':
				case '>':
					if ($check1 > $check2) {
						return true;
					}
					break;
				case 'isless':
				case '<':
					if ($check1 < $check2) {
						return true;
					}
					break;
				case 'greaterorequal':
				case '>=':
					if ($check1 >= $check2) {
						return true;
					}
					break;
				case 'lessorequal':
				case '<=':
					if ($check1 <= $check2) {
						return true;
					}
					break;
				case 'equalto':
				case '==':
					if ($check1 == $check2) {
						return true;
					}
					break;
				case 'notequal':
				case '!=':
					if ($check1 != $check2) {
						return true;
					}
					break;
				default:
					throw new Exception('You must define the $operator parameter for Validator::comparison()');
					break;
			}
			return false;
		}
		public static function decimal($check, $places = null, $regex = null) {
			if (is_null($regex)) {
				if (is_null($places)) {
					$regex = '/^[-+]?[0-9]*\\.{1}[0-9]+(?:[eE][-+]?[0-9]+)?$/';
				} else {
					$regex = '/^[-+]?[0-9]*\\.{1}[0-9]{'.$places.'}$/';
				}
			}
			return (boolean) preg_match($regex, $check);
		}
		public static function ip($check, $type = 'both') {
			$type = strtolower($type);
			$flags = array();
			if ($type === 'ipv4' || $type === 'both') {
				$flags[] = FILTER_FLAG_IPV4;
			}
			if ($type === 'ipv6' || $type === 'both') {
				$flags[] = FILTER_FLAG_IPV6;
			}
			return (boolean) filter_var($check, FILTER_VALIDATE_IP, array('flags' => $flags));
		}
		public static function url($check, $strict = false) {
			$ipv6 = '((([0-9A-Fa-f]{1,4}:){7}(([0-9A-Fa-f]{1,4})|:))|(([0-9A-Fa-f]{1,4}:){6}';
			$ipv6 .= '(:|((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})';
			$ipv6 .= '|(:[0-9A-Fa-f]{1,4})))|(([0-9A-Fa-f]{1,4}:){5}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})';
			$ipv6 .= '(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:)';
			$ipv6 .= '{4}(:[0-9A-Fa-f]{1,4}){0,1}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2}))';
			$ipv6 .= '{3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:){3}(:[0-9A-Fa-f]{1,4}){0,2}';
			$ipv6 .= '((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|';
			$ipv6 .= '((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:){2}(:[0-9A-Fa-f]{1,4}){0,3}';
			$ipv6 .= '((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2}))';
			$ipv6 .= '{3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:)(:[0-9A-Fa-f]{1,4})';
			$ipv6 .= '{0,4}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)';
			$ipv6 .= '|((:[0-9A-Fa-f]{1,4}){1,2})))|(:(:[0-9A-Fa-f]{1,4}){0,5}((:((25[0-5]|2[0-4]';
			$ipv6 .= '\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|((:[0-9A-Fa-f]{1,4})';
			$ipv6 .= '{1,2})))|(((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})))(%.+)?';
			$ipv4 = '(?:(?:25[0-5]|2[0-4][0-9]|(?:(?:1[0-9])?|[1-9]?)[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|(?:(?:1[0-9])?|[1-9]?)[0-9])';
			$pattern = '(['.preg_quote('!"$&\'()*+,-.@_:;=~[]').'\/0-9a-z\p{L}\p{N}]|(%[0-9a-f]{2}))';
			$regex = '/^(?:(?:https?|ftps?|file|news|gopher):\/\/)'.(!empty($strict) ? '' : '?');
			$regex .= '(?:'.$ipv4.'|\['.$ipv6.'\]|(?:[a-z0-9][-a-z0-9]*\.)*(?:[a-z0-9][-a-z0-9]{0,62})\.(?:(?:[a-z]{2}\.)?[a-z]{2,4}|museum|travel)'.')(?::[1-9][0-9]{0,4})?';
			$regex .= '(?:\/?|\/'.$pattern.'*)?';
			$regex .= '(?:\?'.$pattern.'*)?';
			$regex .= '(?:#'.$pattern.'*)?$/iu';
			return (boolean) preg_match($regex, $check);
		}
		public static function email($check) {
			return (boolean) preg_match('/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])(([a-z0-9-])*([a-z0-9]))+(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)+$/i', $check);
		}
		public static function equalTo($check, $compared) {
			return ($check === $compared);
		}
		public static function identifier($check) {
			$head = array(
					'A' => 01, 'I' => 39, 'O' => 48, 'B' => 10, 'C' => 19, 'D' => 28,
					'E' => 37, 'F' => 46, 'G' => 55, 'H' => 64, 'J' => 73, 'K' => 82,
					'L' => 02, 'M' => 11, 'N' => 20, 'P' => 29, 'Q' => 38, 'R' => 47,
					'S' => 56, 'T' => 65, 'U' => 74, 'V' => 83, 'W' => 21, 'X' => 03,
					'Y' => 12, 'Z' => 30);
			$multiply = array(8, 7, 6, 5, 4, 3, 2, 1);
			if (ereg('^[a-zA-Z][1-2][0-9]+$', $check) && strlen($check) == 10) {
				for ($i = 0; $i < strlen($check); $i++) {
					$str_array[$i] = substr($check, $i, 1);
				}
				$total = $head[strtoupper(array_shift($str_array))];
				$point = array_pop($str_array);
				for ($j = 0; $j < count($str_array); $j++) {
					$total += $str_array[$j] * $multiply[$j];
				}
				if (($total % 10 == 0 ) ? 0 : 10 - $total % 10 != $point) {
					return false;
				} else {
					return true;
				}
			} else {
				return false;
			}
		}
		private function __clone() {
			
		}
	}	