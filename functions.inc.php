<?php
/**
 * Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * @license http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors:
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 */
function print_stacktrace() {
	$bt = array_reverse(debug_backtrace());
	//Remove the call to print_backtrace :)
	array_pop($bt);
	print('<ol>');
	foreach($bt as $item) {
		$to_print = '';
		if (isset($item['file'])) $to_print .= $item['file'];
		if (isset($item['line'])) $to_print .= '('.$item['line'].') called ';
		if (isset($item['class'])) $to_print .= '<strong>'.$item['class'].'</strong>->';
		if (isset($item['function'])) $to_print .= '<i>'.$item['function'].'</i>';
		print('<li>'.$to_print.'</li>');
	}
	print('</ol>');
}

function is_post() {
	return strtoupper(array_key_exists('REQUEST_METHOD', $_SERVER) ? $_SERVER['REQUEST_METHOD'] : 'GET') == 'POST';
}

function is_get() {
	return strtoupper(array_key_exists('REQUEST_METHOD', $_SERVER) ? $_SERVER['REQUEST_METHOD'] : 'GET') == 'GET';
}

function is_put() {
	return strtoupper(array_key_exists('REQUEST_METHOD', $_SERVER) ? $_SERVER['REQUEST_METHOD'] : 'GET') == 'PUT';
}

function is_delete() {
	return strtoupper(array_key_exists('REQUEST_METHOD', $_SERVER) ? $_SERVER['REQUEST_METHOD'] : 'GET') == 'DELETE';
}

function update_links($content, $new_vars) {
	$toret = $content;
	if (count($new_vars)) {
		preg_match_all('/(<a\s+.*?href=[\'"]|<form\s+.*?action=[\'"]|<link\s+.*?href=[\'"])(.*?)[\'"]/', $toret, $matches);
		if (count($matches) == 3) {
			$matched = $matches[0];
			$links = $matches[1];
			$urls = $matches[2];
			$replacements = array();
			foreach ($urls as $key => $url) {
				$query = parse_url($url);
				if (array_key_exists('scheme', $query)) {
					$query['scheme'] = $query['scheme'] . '://';
				}
				if (array_key_exists('query', $query)) {
					parse_str($query['query'], $vars);
				} else {
					$vars = array();
				}
				$query['query'] = '?' . http_build_query(array_merge($new_vars, $vars));
				$to_rep = $links[$key] . implode('', $query) . '"';
				$replacements[] = $to_rep;
			}
			$toret = str_replace($matched, $replacements, $toret);
		}
	}
	return $toret;
}

function redirect($where_to = false, $dont_die = false) {
	if (!headers_sent()) {
		if ($where_to) {
			header('Location: ' . $where_to);
		} else {
			header('Location: ' . $_SERVER['REQUEST_URI']);
		}
		if (!$dont_die) {
			die('redirecting');
		}
	} else {
		throw new Exception('Cannot redirect after headers have been sent');
	}
}

function get_current_url() {
	$protocol = 'http';
	if ($_SERVER['SERVER_PORT'] == 443 || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) {
		$protocol .= 's';
		$protocol_port = $_SERVER['SERVER_PORT'];
	} else {
		$protocol_port = 80;
	}
	$host = $_SERVER['HTTP_HOST'];
	$port = $_SERVER['SERVER_PORT'];
	$request = $_SERVER['PHP_SELF'];
	$query = !empty($_SERVER['argv'][0]) ? substr($_SERVER['argv'][0], strpos($_SERVER['argv'][0], ';') + 1) : '';
	$toret = $protocol . '://' . $host . ($port == $protocol_port ? '' : ':' . $port) . $request . (empty($query) ? '' : '?' . $query);
	return $toret;
}

function get_random($options = array()) {
	$toret = false;
	if (is_string($options)) {
		$options = array('type' => $options);
	}
	$type = array_key_exists('type', $options) ? $options['type'] : false;
	$length = array_key_exists('length', $options) ? $options['length'] : 12;
	$lowercase = array_key_exists('lowercase', $options);
	$uppercase = array_key_exists('uppercase', $options);
	switch (strtolower($type)) {
	case 'number':
		break;
	case 'numeric':
		$characters = '0123456789';
		break;
	case 'alpha':
	case 'alphanumeric':
	default:
		if ($lowercase) {
			$characters = 'abcdefghijklmnopqrstuvwxyz';
		} else if ($uppercase) {
			$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		} else {
			$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		}
		if ($type == 'alphanumeric') {
			$characters .= '0123456789';
		}
		break;
	}
	if (!$toret) {
		reseed();
		$toret = '';
		for ($i = 0; $i < $length; $i++) {
			$toret .= $characters[mt_rand(0, strlen($characters) - 1)];
		}
	}
	return $toret;
}

function reseed() {
	//Get and generate new seed
	$seed = Value::get('seed', mt_rand());
	srand($seed);
	//$new_seed = floor($seed / 4) . floor(mt_rand() / 4);
	$new_seed = mt_rand();
	Value::set('seed', $new_seed);
}

/**
 * Wrapper for sending emails
 *
 * @todo Extend this to check the recipient formats, handle recipients as an array, etc.
 */
function send_email($recipient, $subject, $message, array $headers = array()) {
	return mail($recipient, $subject, $message, $headers);
}

function array_flatten(&$array, $key_field = null, $value_field = null) {
	$toret = false;
	if (is_array($array) && is_array(current($array))) {
		$toret = array();
		if (is_null($value_field) && is_null($key_field)) {
			foreach($array as $row) {
				$toret[] = current($row);
			}
		} else if ($value_field === true && $key_field == true) {
			foreach($array as $row) {
				$toret[array_shift($row)] = array_shift($row);
			}
		} else if ($value_field === true && $key_field == true && array_key_exists($value_field, current($array)) && array_key_exists($key_field, current($array))) {
			foreach($array as $row) {
				$toret[$row[$key_field]] = $row[$value_field];
			}
		} else if ($value_field && is_null($key_field) && array_key_exists($value_field, current($array))) {
			foreach($array as $row) {
				$toret[] = $row[$value_field];
			}
		} else if (is_null($value_field) && $key_field && array_key_exists($key_field, current($array))) {
			foreach($array as $row) {
				$toret[$row[$key_field]] = $row;
			}
		} else if ($value_field && array_key_exists($value_field, current($array)) && $key_field && array_key_exists($key_field, current($array))) {
			foreach($array as $row) {
				$toret[$row[$key_field]] = $row[$value_field];
			}
		}
	}
	return $toret;
}

function files_from_folder($folder, array $options = array()) {
	$toret = array();
	$prepend_folder = array_key_exists('prepend_folder', $options) ? $options['prepend_folder'] : false;
	if (is_dir($folder)) {
		$dh = opendir($folder);
		while (($file = readdir($dh)) !== false) {
			if (filetype($folder . $file) == 'file') {
				if ($prepend_folder) {
					$toret[] = $folder . $file;
				} else {
					$toret[] = $file;
				}
			}
		}
	}
	return array_unique($toret);
}

