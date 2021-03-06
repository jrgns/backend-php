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

function request_method() {
	if (empty(Controller::$method)) {
		return strtoupper(array_key_exists('REQUEST_METHOD', $_SERVER) ? $_SERVER['REQUEST_METHOD'] : 'GET');
	} else {
		return strtoupper(Controller::$method);
	}
}

function is_post() {
	return request_method() == 'POST';
}

function is_get() {
	return request_method() == 'GET';
}

function is_put() {
	return request_method() == 'PUT';
}

function is_delete() {
	return request_method() == 'DELETE';
}

function update_links($content, $new_vars) {
	$toret = $content;
	if (count($new_vars)) {
		preg_match_all('/(<a\s+.*?href=[\'"]|<form\s+.*?action=[\'"]|<link\s+.*?href=[\'"])(.*?)[\'"]/', $toret, $matches);
		if (count($matches) == 3) {
			$matched = array();
			$links = $matches[1];
			$urls = $matches[2];
			$replacements = array();
			foreach ($urls as $key => $url) {
				if ($query = @parse_url($url)) {
					switch (true) {
					//Skip all mailto links
					case !empty($query['scheme']) && $query['scheme'] == 'mailto':
					//Skip all external links
					case !empty($query['host']) && $query['host'] != $_SERVER['SERVER_NAME']:
						continue 2;
						break;
					default:
						break;
					}
					$matched[] = $matches[0][$key];
					if (array_key_exists('scheme', $query)) {
						$query['scheme'] = $query['scheme'] . '://';
					}
					if (array_key_exists('fragment', $query)) {
						$query['fragment'] = '#' . $query['fragment'];
					}
					if (array_key_exists('port', $query)) {
						$query['port'] = ':' . $query['port'];
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
			}
			$toret = str_replace($matched, $replacements, $toret);
		}
	}
	return $toret;
}

function redirect($where_to = false, $dont_die = false) {
	if (!headers_sent()) {
		header('X-Redirector: Controller-' . __LINE__);
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
	if (!array_key_exists('HTTP_HOST', $_SERVER)) {
		return $_SERVER['PHP_SELF'];
	}
	$protocol = 'http';
	if ($_SERVER['SERVER_PORT'] == 443 || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) {
		$protocol .= 's';
		$protocol_port = $_SERVER['SERVER_PORT'];
	} else {
		$protocol_port = 80;
	}
	$host    = $_SERVER['HTTP_HOST'];
	$port    = $_SERVER['SERVER_PORT'];
	$request = $_SERVER['PHP_SELF'];
	if (!empty($_SERVER['QUERY_STRING'])) {
		$request .= '?' . $_SERVER['QUERY_STRING'];
	}
	return $protocol . '://' . $host . ($port == $protocol_port ? '' : ':' . $port) . $request;
}

function get_current_query() {
	return Controller::$area . '/' . Controller::$action . '/' . implode('/', Controller::$parameters);
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

function random_number($min = false, $max = false) {
	reseed();
	if ($min !== false && $max !== false) {
		return mt_rand($min, $max);
	} else {
		return mt_rand();
	}
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
	$seed = ConfigValue::get('Seed', mt_rand());
	srand($seed);
	//$new_seed = floor($seed / 4) . floor(mt_rand() / 4);
	$new_seed = mt_rand();
	ConfigValue::set('Seed', $new_seed);
}

if (!function_exists('send_email')) {
	/**
	 * Wrapper for sending emails
	 *
	 * @todo Extend this to check the recipient formats, handle recipients as an array, etc.
	 */
	function send_email($recipient, $subject, $message, array $headers = array()) {
		$headers = array_change_key_case($headers);
		if (!array_key_exists('from', $headers)) {
			$headers['from'] = ConfigValue::get('application.Email', 'info@' . SITE_DOMAIN);
		}
		foreach($headers as $name => $value) {
			$headers[$name] = ucwords($name) . ': ' . $value;
		}
		return mail($recipient, $subject, $message, implode("\r\n", $headers));
	}
}

function array_flatten($array, $key_field = null, $value_field = null) {
	$toret = false;
	if (is_array($array) && is_array(current($array))) {
		$toret = array();
		if (is_null($value_field) && is_null($key_field)) {
			foreach($array as $row) {
				$toret[] = current($row);
			}
		} else if ($value_field === true && $key_field === true) {
			foreach($array as $row) {
				$toret[array_shift($row)] = array_shift($row);
			}
		} else if ($value_field === true && $key_field === true && array_key_exists($value_field, current($array)) && array_key_exists($key_field, current($array))) {
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
			if (filetype($folder . $file) == 'file' && substr($file, -1, 1) != '~' && substr($file, 0, 1) != '.') {
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

/**
 * Send an HTTP request using CURL
 *
 * @param string the URL at which the request should be directed
 * @param array An associative array with the data to include. It will be converted to GET or POST as needed
 * @param array An associative array with which to alter the behaviour of curl_request
 */
function curl_request($url, array $parameters = array(), array $options = array()) {
    $cache_file = false;
	if (!empty($options['cache']) && $options['cache'] > 0) {
		$cache = $options['cache'];
		if (count($parameters)) {
			$cache_file = $url . '?' . http_build_query($parameters);
		} else {
			$cache_file = $url;
		}
		$cache_file = md5($cache_file);
		if (defined('SITE_FOLDER')) {
			$cache_file = SITE_FOLDER . '/cache/' . $cache_file;
		} else {
			$cache_file = APP_FOLDER . '/cache/' . $cache_file;
		}
		if (file_exists($cache_file) && filemtime($cache_file) >= time() - $cache) {
			return file_get_contents($cache_file);
		}
	} else {
		$cache = false;
	}
	$ch = curl_init($url);

	if (!empty($options['debug'])) {
		var_dump('cURL Request:', $url);
	}

    if (empty($options['user_agent'])) {
    	curl_setopt($ch, CURLOPT_USERAGENT, 'Backend / PHP');
	} else {
    	curl_setopt($ch, CURLOPT_USERAGENT, $options['user_agent']);
	}
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    if (!empty($options['bypass_ssl'])) {
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    }
    if (empty($options['dont_follow'])) {
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	} else {
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	}

	if (array_key_exists('output', $options) && $options['output']) {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
	} else {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	}
	if (array_key_exists('header_function', $options) && is_callable($options['header_function'])) {
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, $options['header_function']);
		curl_setopt($ch, CURLOPT_HEADER, false);
	} else if (!empty($options['return_header']) || !empty($options['debug'])) {
		curl_setopt($ch, CURLOPT_HEADER, true);
	} else {
		curl_setopt($ch, CURLOPT_HEADER, false);
	}
	if (!empty($options['referer'])) {
	    curl_setopt($ch, CURLOPT_REFERER, $options['referer']);
	}
	if (!empty($options['headers']) && is_array($options['headers'])) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $options['headers']);
	}
	if (!empty($options['cookie_jar'])) {
	    curl_setopt($ch, CURLOPT_COOKIEJAR, $options['cookie_jar']);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, $options['cookie_jar']);
	}
	//Use this carefully...
	if (!empty($options['interface'])) {
	    curl_setopt($ch, CURLOPT_INTERFACE, $options['interface']);
	}

	if (!empty($options['username']) && !empty($options['password'])) {
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($ch, CURLOPT_USERPWD, $options['username'] . ':' . $options['password']);
	}

	if (!empty($options['proxy'])) {
		if (Controller::$debug) {
			var_dump('Using proxy: ' . $options['proxy']);
		}
		curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
		curl_setopt($ch, CURLOPT_PROXY, $options['proxy']);
	}

	$method = array_key_exists('method', $options) && in_array(strtolower($options['method']), array('get', 'post', 'put')) ? strtolower($options['method']) : 'get';
	switch ($method) {
	case 'put':
		curl_setopt($ch, CURLOPT_PUT, true);
		break;
	case 'post':
		curl_setopt($ch, CURLOPT_POST, true);
		if (count($parameters)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
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
	if ($filename = ConfigValue::get('LogCurlRequests', false)) {
		$fp = fopen($filename, 'a');
		if ($method == 'post') {
			fwrite($fp, date('Y-m-d H:i:s') . "\t" . $method . "\t" . $url . "\t" . http_build_query($parameters) . PHP_EOL);
		} else {
			fwrite($fp, date('Y-m-d H:i:s') . "\t" . $method . "\t" . $url . PHP_EOL);
		}
		fclose($fp);
	}
	$toret = curl_exec($ch);

	if (!empty($options['debug'])) {
	    @list($headers, $toret) = preg_split("/\n\n|\n\r\n\r|\r\n\r\r/", $toret, 2);
		var_dump('cURL Response Headers:');
		echo "<pre>$headers</pre>";
		var_dump('cURL Response:', $toret);
	}

	if (!empty($options['callback']) && is_callable($options['callback'])) {
		$toret = call_user_func_array($options['callback'], array($ch, $toret, $options));
		if (!empty($options['debug'])) {
			var_dump('cURL Response After Callback:', $toret);
		}
	} else if ($curl_error = curl_errno($ch)) {
		if (!empty($options['debug'])) {
			var_dump('cURL Error:', $curl_error);
		}
		$toret = false;
	} else {
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if (!empty($options['debug'])) {
			var_dump('cURL HTTP Code:', $http_code);
		}
		if (!in_array($http_code, array(200))) {
			$toret = false;
		}
	}
	curl_close($ch);

	if (!empty($options['debug'])) {
		var_dump('cURL Precache:', $toret, $cache, $cache_file);
	}
	if ($toret && $cache) {
		file_put_contents($cache_file, $toret);
	}

	//Don't know if this is a good idea, but if we couldn't fetch the file, and an older one exists, return it
	if (!$toret && $cache && file_exists($cache_file)) {
		$toret = file_get_contents($cache_file);
	}
	return $toret;
}

/**
 * Find maximum depth of an array
 *
 * Do NOT use this function on weird arrays or arrays contiaining objects...
 *
 * @param array The array to be measured
 * @returns int The depth of the array
 */
function array_depth(array $array) {
	$max_depth = 1;
	foreach($array as $value) {
		if (is_array($value)) {
			$depth = array_depth($value) + 1;
			if ($depth > $max_depth) {
				$max_depth = $depth;
			}
		}
	}
	return $max_depth;
}

function add_periods($start, $number, $periods, $format = 'Y-m-d H:i:s') {
	return period_diff('add', $start, $number, $periods, $format);
}

function subtract_periods($start, $number, $periods, $format = 'Y-m-d H:i:s') {
	return period_diff('subtract', $start, $number, $periods, $format);
}

function period_diff($op, $start, $number, $periods, $format = 'Y-m-d H:i:s') {
	$toret = is_string($start) ? strtotime($start) : $start;
	switch (strtolower($periods)) {
		case 's':
		case 'second':
		case 'seconds':
			if ($op == 'subtract') {
				$toret -= $number;
			} else {
				$toret += $number;
			}
			break;
		case 'h':
		case 'hour':
		case 'hours':
			if ($op == 'subtract') {
				$toret -= ($number * 60 * 60);
			} else {
				$toret += ($number * 60 * 60);
			}
			break;
		case 'd':
		case 'day':
		case 'days':
			if ($op == 'subtract') {
				$toret -= ($number * 60 * 60 * 24);
			} else {
				$toret += ($number * 60 * 60 * 24);
			}
			break;
		case 'm':
		case 'minute':
		case 'minutes':
			if (strlen($periods) > 1 || $periods == 'm') {
				if ($op == 'subtract') {
					$toret -= ($number * 60);
				} else {
					$toret += ($number * 60);
				}
				break;
			}
		case 'month':
		case 'months':
			if (strlen($periods) > 1 || $periods == 'M') {
				if ($op == 'subtract') {
					$toret = mktime((int)date('G', $toret), (int)date('i', $toret), (int)date('s', $toret), (int)date('n', $toret) - $number);
				} else {
					$toret = mktime((int)date('G', $toret), (int)date('i', $toret), (int)date('s', $toret), (int)date('n', $toret) + $number);
				}
				break;
			}
		case 'w':
		case 'week':
		case 'weeks':
			if ($op == 'subtract') {
				$toret -= ($number * 60 * 60 * 24 * 7);
			} else {
				$toret += ($number * 60 * 60 * 24 * 7);
			}
			break;
		case 'y':
		case 'year':
		case 'years':
			if ($op == 'subtract') {
				$toret = mktime((int)date('G', $toret), (int)date('i', $toret), (int)date('s', $toret), (int)date('n', $toret), (int)date('j', $toret), (int)date('Y', $toret) - $number);
			} else {
				$toret = mktime((int)date('G', $toret), (int)date('i', $toret), (int)date('s', $toret), (int)date('n', $toret), (int)date('j', $toret), (int)date('Y', $toret) + $number);
			}
			break;
	}
	return date($format, $toret);
}

function bzr_get_file_revision($filename) {
	if (file_exists($filename)) {
		if (exec('bzr log -l 1 --line ' . $filename, $array) && count($array)) {
			$row = array_shift($array);
			$toret = explode(':', $row);
			return $toret[0];
		}
	}
	return false;
}

/**
 * Works the same as the MySQL IFNULL function
 */
function ifnull($var, $value) {
	return is_null($var) ? $value : $var;
}

function debug_header($message) {
	static $count = 0;
	if (!headers_sent()) {
		header('X-Debug-' . str_pad($count++, 3, '0', STR_PAD_LEFT) . ': ' . $message);
	}
}

function stripslashes_deep($value) {
	$value = is_array($value) ?
	            array_map('stripslashes_deep', $value) :
	            stripslashes($value);

	return $value;
}

/**
 * Convert PHP shorthand notation to bytes
 *
 * From http://www.php.net/manual/en/function.ini-get.php on 2011-01-17
 */
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}

/**
 * Check if the memory is within a certain range of the memory limit. End the script if it's too high.
 */
function check_memory_limit($range = 512, $log = false, $user_message = false, $die = false) {
	$usage = memory_get_usage(true);
	$limit = return_bytes(ini_get('memory_limit'));
	if ($log) {
		$message = 'Memory Used: ' . ($usage / 1024 / 1024) . 'MB / ' . ($limit / 1024 / 1024);
		if (!empty($user_message)) {
			$message .= ' <-> ' . $user_message;
		}
		if (is_callable($log)) {
			call_user_func($log, $message);
		} else {
			echo $message . '<br>';
		}
	}
	if ($limit > 0 && $usage  > $limit - (1024 * $range)) {
		if ($log) {
			$message = 'Running out of memory.';
			if (is_callable($log)) {
				call_user_func($log, $message);
			} else {
				echo $message . '<br>';
			}
		}
		if ($die) {
			$message = 'Aborting Process.';
			if (is_callable($log)) {
				call_user_func($log, $message);
			} else {
				echo $message . '<br>';
			}
			print_stacktrace();
			die(__FILE__ . ', ' . __LINE__);
		}
		return true;
	}
	return false;
}

/**
 * Function to write a parse_ini_file parsable file.
 *
 * Copied from http://stackoverflow.com/questions/1268378/create-ini-file-write-values-in-php/1268642#1268642 on 2011-04-27
 */
function write_ini_file($assoc_arr, $path, $has_sections=FALSE) {
    $content = "";
    if ($has_sections) {
        foreach ($assoc_arr as $key=>$elem) {
            $content .= "[".$key."]\n";
            foreach ($elem as $key2=>$elem2) {
                if(is_array($elem2))
                {
                    for($i=0;$i<count($elem2);$i++)
                    {
                        $content .= $key2."[] = \"".$elem2[$i]."\"\n";
                    }
                }
                else if($elem2=="") $content .= $key2." = \n";
                else $content .= $key2." = \"".$elem2."\"\n";
            }
        }
    }
    else {
        foreach ($assoc_arr as $key=>$elem) {
            if(is_array($elem))
            {
                for($i=0;$i<count($elem);$i++)
                {
                    $content .= $key."[] = \"".$elem[$i]."\"\n";
                }
            }
            else if($elem=="") $content .= $key." = \n";
            else $content .= $key." = \"".$elem."\"\n";
        }
    }

    if (!$handle = fopen($path, 'w')) {
        return false;
    }
    if (!fwrite($handle, $content)) {
        return false;
    }
    fclose($handle);
    return true;
}

/**
 * Compare two arrays or objects on their weight elements. Heigher weights float down
 */
function compare_weights($elm1, $elm2) {
	$elm1 = is_object($elm1) ? (array)$elm1 : $elm1;
	$elm2 = is_object($elm2) ? (array)$elm2 : $elm2;
	if (!array_key_exists('weight', $elm1) || !array_key_exists('weight', $elm2)) {
		return null;
	}
	$weight_1 = array_key_exists('weight', $elm1) ? $elm1['weight'] : 0;
	$weight_2 = array_key_exists('weight', $elm2) ? $elm2['weight'] : 0;
	if (empty($weight_1) && empty($weight_2)) {
		return 0;
	}
	if (empty($weight_1)) {
		return -1;
	} else if (empty($weight_2)) {
		return 1;
	}
	if ($weight_1 < $weight_2) {
		return -1;
	} else if ($weight_1 > $weight_2) {
		return 1;
	} else {
		return 0;
	}
}

/**
 * Check if an object or an array has a recursive dependency
 *
 * From http://noteslog.com/post/detecting-recursive-dependencies-in-php-composite-values/ on 2011-07-11
 */
function has_recursive_dependency($value) {
	//if PHP detects recursion in a $value, then a printed $value
	//will contain at least one match for the pattern /\*RECURSION\*/
	$printed = print_r($value, true);
	$recursionMetaUser = preg_match_all('@\*RECURSION\*@', $printed, $matches);
	if ($recursionMetaUser == 0) {
		return false;
	}
	//if PHP detects recursion in a $value, then a serialized $value
	//will contain matches for the pattern /\*RECURSION\*/ never because
	//of metadata of the serialized $value, but only because of user data
	$serialized = serialize($value);
	$recursionUser = preg_match_all('@\*RECURSION\*@', $serialized, $matches);
	//all the matches that are user data instead of metadata of the
	//printed $value must be ignored
	$result = $recursionMetaUser > $recursionUser;
	return $result;
}
