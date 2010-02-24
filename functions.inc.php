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
function print_stacktrace($return = false) {
	$bt = array_reverse(debug_backtrace());
	//Remove the call to print_backtrace :)
	array_pop($bt);
	if ($return) {
		return $bt;
	} else {
		$to_print = '<ol>';
		foreach($bt as $item) {
			if ($return) {
			
			} else {
				$to_print .= '<li>';
				if (isset($item['file'])) $to_print .= $item['file'];
				if (isset($item['line'])) $to_print .= '('.$item['line'].') called ';
				if (isset($item['class'])) $to_print .= '<strong>'.$item['class'].'</strong>->';
				if (isset($item['function'])) $to_print .= '<i>'.$item['function'].'</i>';
				$to_print .= '</li>';
			}
		}
		$to_print .= '</ol>';
		echo $to_print;
	}
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
				$query['query'] = '?' . http_build_query(array_merge($vars, $new_vars));
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

function get_previous_($what, $mode = 'html') {
	if (!empty($_SESSION) && array_key_exists('previous_' . $what, $_SESSION) && array_key_exists($mode, $_SESSION['previous_' . $what])) {
		return $_SESSION['previous_' . $what][$mode];
	}
	return null;
}

function get_previous_area($mode = 'html') {
	return get_previous_('area', $mode);
}

function get_previous_action($mode = 'html') {
	return get_previous_('action', $mode);
}

function get_previous_parameters($mode = 'html') {
	return get_previous_('parameters', $mode);
}

function get_previous_url($mode = 'html') {
	$toret = get_previous_('url', $mode);
	if (empty($toret) && !empty($_SERVER['HTTP_REFERER'])) {
		return $_SERVER['HTTP_REFERER'];
	}
	return $toret;
}

function get_previous_query($mode = 'html') {
	$parameters = get_previous_parameters($mode);
	$parameters = array_filter(is_array($parameters) ? $parameters : array());
	$arr = array(
		get_previous_area($mode),
		get_previous_action($mode),
		implode('/', $parameters),
	);
	return implode('/', array_filter($arr));
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
	$query = !empty($_SERVER['argv'][0]) ? substr($_SERVER['argv'][0], strpos($_SERVER['argv'][0], ';')) : '';
	$toret = $protocol . '://' . $host . ($port == $protocol_port ? '' : ':' . $port) . $request . (empty($query) ? '' : '?' . $query);
	return $toret;
}

function build_url(array $url) {
	$toret = '';
	if (array_key_exists('host', $url)) {
		$protocol = array_key_exists('scheme', $url) ? $url['scheme'] : 'http';
		$host     = array_key_exists('host', $url)   ? $url['host']   : $_SERVER['HTTP_HOST'];
		$toret = $protocol . '://' . $host;
		if (array_key_exists('port', $url)) {
			$toret .= ':' . $url['port'];
		}
	}
	$path  = array_key_exists('path', $url)  ? $url['path']  : '/';
	$query = array_key_exists('query', $url) ? $url['query'] : '';
	$toret  .= $path . (empty($query) ? '' : '?' . $query);
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
	$headers = array_change_key_case($headers);
	if (!array_key_exists('from', $headers)) {
		$headers['from'] = Value::get('site_email', 'info@' . SITE_DOMAIN);
	}
	foreach($headers as $name => $value) {
		$headers[$name] = ucwords($name) . ': ' . $value;
	}
	return mail($recipient, $subject, $message, implode("\r\n", $headers));
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
			if (filetype($folder . $file) == 'file' && substr($file, 0, -1) != '~') {
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

/********************************
 * Retro-support of get_called_class()
 * Tested and works in PHP 5.2.4
 * http://www.sol1.com.au/
 ********************************/
if (!function_exists('get_called_class')) {
	function get_called_class($bt = false, $l = 1) {
		if (!$bt) $bt = debug_backtrace();
		if (!isset($bt[$l])) throw new Exception("Cannot find called class -> stack level too deep.");
		if (!isset($bt[$l]['type'])) {
			throw new Exception ('type not set');
		} else {
			switch ($bt[$l]['type']) {
			case '::':
				if (array_key_exists('file', $bt[$l])) {
					$lines = file($bt[$l]['file']);
					$i = 0;
					$callerLine = '';
					do {
						$i++;
						$callerLine = $lines[$bt[$l]['line'] - $i] . $callerLine;
					} while (stripos($callerLine,$bt[$l]['function']) === false);
					preg_match('/([a-zA-Z0-9\_]+)::'.$bt[$l]['function'].'/',
						$callerLine,
						$matches
					);
					if (!isset($matches[1])) {
					// must be an edge case.
						throw new Exception ("Could not find caller class: originating method call is obscured.");
					}
					switch ($matches[1]) {
					case 'self':
					case 'parent':
						return get_called_class($bt, $l + 1);
						break;
					default:
						return $matches[1];
						break;
					}
				} else if (
						array_key_exists('function', $bt[$l + 1])
						&& in_array($bt[$l + 1]['function'], array('call_user_func', 'call_user_func_array'))
						&& array_key_exists(0, $bt[$l + 1]['args'])
						&& is_array($bt[$l + 1]['args'][0])
					) {
					return current($bt[$l + 1]['args'][0]);
				}
			// won't get here.
			case '->':
				switch ($bt[$l]['function']) {
				case '__get':
					// edge case -> get class of calling object
					if (!is_object($bt[$l]['object'])) throw new Exception ("Edge case fail. __get called on non object.");
					return get_class($bt[$l]['object']);
					break;
				default:
					return $bt[$l]['class'];
					break;
				}
			default:
				throw new Exception ("Unknown backtrace method type");
				break;
			}
		}
	}
} 

function curl_request($url, array $parameters = array(), array $options = array()) {
	$ch = curl_init($url);
	
	curl_setopt($ch, CURLOPT_USERAGENT, 'Backend / PHP');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	if (array_key_exists('output', $options) && $options['output']) {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
	} else {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	}
	if (array_key_exists('header_function', $options) && is_callable($options['header_function'])) {
		//curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
		curl_setopt($ch, CURLOPT_HEADER, false);
	} else if (!empty($options['return_header'])) {
		curl_setopt($ch, CURLOPT_HEADER, true);
	} else {
		curl_setopt($ch, CURLOPT_HEADER, false);
	}
	if (!empty($options['headers']) && is_array($options['headers'])) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $options['headers']);
	}
	
	if (!empty($options['username']) && !empty($options['password'])) {
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($ch, CURLOPT_USERPWD, $options['username'] . ':' . $options['password']);
	}

	$method = array_key_exists('method', $options) && in_array(strtolower($options['method']), array('get', 'post', 'pust')) ? strtolower($options['method']) : 'get';
	switch ($method) {
	case 'put':
		curl_setopt($ch, CURLOPT_PUT, true);
		break;
	case 'post':
		curl_setopt($ch, CURLOPT_POST, true);
		if (count($parameters)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
		}
		break;
	case 'get':
	default:
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		if (count($parameters)) {
			curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($parameters));
		}
		break;
	}
	if ($filename = Value::get('log_curl_requests', false)) {
		//$fp = fopen('/var/www/Jrgn5/backend/curl_log.txt', 'a');
		$fp = fopen($filename, 'a');
		fwrite($fp, date('Y-m-d H:i:s') . "\t" . $method . "\t" . $url . PHP_EOL);
		fclose($fp);
	}
	$toret = curl_exec($ch);
	curl_close($ch);
	return $toret;
}

