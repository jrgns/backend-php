<?php
/**
 * The class file for Parser
 *
 * @author J Jurgens du Toit (JadeIT cc) <jurgens.dutoit@gmail.com> - initial API and implementation
 * @copyright Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available {@link http://www.eclipse.org/legal/epl-v10.html here}
 * @license http://www.eclipse.org/legal/epl-v10.html Eclipse Public License v1.0
 * @package Utilities
 */
/**
 * Base class to handle parsing
 */
class Parser {
	public static function accept_header($header = false) {
		$toret = null;
		$header = $header ? $header : (array_key_exists('HTTP_ACCEPT', $_SERVER) ? $_SERVER['HTTP_ACCEPT']: false);
		if ($header) {
			$types = explode(',', $header);
			$types = array_map('trim', $types);
			foreach ($types as $one_type) {
				$one_type = explode(';', $one_type);
				$type = array_shift($one_type);
				if ($type) {
					list($precedence, $tokens) = self::accept_header_options($one_type);
					if (strpos($type, '/') !== false) {
						list($main_type, $sub_type) = array_map('trim', explode('/', $type));
					} else {
						$main_type = $type;
						$sub_type  = '*';
					}
					$toret[] = array('main_type' => $main_type, 'sub_type' => $sub_type, 'precedence' => $precedence, 'tokens' => $tokens);
				}
			}
			usort($toret, array('Parser', 'compare_media_ranges'));
		}
		return $toret;
	}
	
	public static function accept_header_options($type_options) {
		$precedence = (float)1;
		$tokens = array();
		if (is_string($type_options)) {
			$type_options = explode(';', $type_options);
		}
		$type_options = array_map('trim', $type_options);
		foreach ($type_options as $option) {
			$option = explode('=', $option);
			$option = array_map('trim', $option);
			if ($option[0] == 'q') {
				$precedence = (float)$option[1];
			} else {
				$tokens[$option[0]] = $option[1];
			}
		}
		$tokens = count($tokens) ? $tokens : false;
		return array($precedence, $tokens);
	}

	private static function compare_media_ranges($one, $two) {
		if ($one['main_type'] != '*' && $two['main_type'] != '*') {
			if ($one['sub_type'] != '*' && $two['sub_type'] != '*') {
				if ($one['precedence'] == $two['precedence']) {
					if (count($one['tokens']) == count($two['tokens'])) {
						return 0;
					} else if (count($one['tokens']) < count($two['tokens'])) {
						return 1;
					} else {
						return -1;
					}
				} else if ($one['precedence'] < $two['precedence']) {
					return 1;
				} else {
					return -1;
				}
			} else if ($one['sub_type'] == '*') {
				return 1;
			} else {
				return -1;
			}
		} else if ($one['main_type'] == '*') {
			return 1;
		} else {
			return -1;
		}
	}

}
