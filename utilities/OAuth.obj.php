<?php
class OAuth {
	public static function encode($input) {
		if (is_array($input)) {
			return array_map(array('OAuth', 'encode'), $input);
		} else if (is_scalar($input)) {
			return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode($input)));
		}
	}
	
	public static function base_string($request, array $parameters = array(), $method = 'GET') {
		$parts           = parse_url($request);
		$parts['scheme'] = empty($parts['scheme']) ? 'http'      : $parts['scheme'];
		$parts['host']   = empty($parts['host'])   ? 'localhost' : $parts['host'];
		//Let's keep it as simple as possible
		if (!empty($parts['port'])) {
			if (($parts['scheme'] == 'http' && $parts['port'] == '80') || ($parts['scheme'] == 'https' && $parts['port'] == '443')) {
				$port = '';
			} else {
				$port = ':' . $parts['port'];
			}
		} else {
			$port = '';
		}
		if (!empty($parts['user']) && !empty($parts['pass'])) {
			$username = $parts['user'] . ':' . $parts['pass'] . '@';
		} else {
			$username = '';
		}
		$parts['path'] = empty($parts['path']) ? '/' : $parts['path'];
		$fragment      = empty($parts['fragment']) ? '' : '#' . $parts['fragment'];

		parse_str(empty($parts['query']) ? '' : $parts['query'], $vars);
		$parameters = array_merge($vars, $parameters);
		uksort($parameters, 'strnatcmp');
		$query = array();
		foreach($parameters as $name => $value) {
			$query[] = $name . '=' . $value;
		}

		$string = $parts['scheme'] . '://' . $username . $parts['host'] . $port . $parts['path'];
		return strtoupper($method) . '&' . self::encode($string) . '&' . self::encode(implode('&', $query));
	}

	public static function sign_request($base, $token_secret = '') {
		$key = self::encode(Backend::getConfig('oauth.consumer.secret')) . '&' . self::encode($token_secret);
		if (Controller::$debug >= 2) {
			var_dump('Key', $key);
		}
		return base64_encode(hash_hmac('sha1', $base, $key, true));
	}
	
	public static function getAuthToken(array $parameters = array()) {
		$returned = self::request(Backend::getConfig('oauth.request.url'), $parameters);
		parse_str($returned, $vars);
		if (count($vars) == 2) {
			return $vars;
		} else {
			return false;
		}
	}

	public static function getAccessToken(array $parameters = array()) {
		$returned = self::request(Backend::getConfig('oauth.access.url'), $parameters);
		parse_str($returned, $vars);
		if (count($vars) == 4) {
			return $vars;
		} else {
			return false;
		}
	}

	public static function request($url, array $parameters = array(), $method = 'GET') {
		$request = self::get_request($url, $parameters, $method);
		$returned = curl_request($request, array(), array('method' => $method));
		if (Controller::$debug >= 2) {
			var_dump('Returned', $returned);
		}
		return $returned;
	}
	
	protected static function get_request($url, array $parameters = array(), $method = 'GET') {
		$parameters['oauth_version']          = empty($parameters['oauth_version'])      ? '1.0'                : $parameters['oauth_version'];
		$parameters['oauth_nonce']            = empty($parameters['oauth_nonce'])        ? md5(microtime().mt_rand()) : $parameters['oauth_nonce'];
		$parameters['oauth_timestamp']        = empty($parameters['oauth_timestamp'])    ? time()               : $parameters['oauth_timestamp'];
		$parameters['oauth_consumer_key']     = empty($parameters['oauth_consumer_key']) ? Backend::getConfig('oauth.consumer.key') : $parameters['oauth_consumer_key'];
		$parameters['oauth_signature_method'] = 'HMAC-SHA1';

		//Don't pass the secret as a parameter, just use it
		if (!empty($parameters['oauth_token_secret'])) {
			$oauth_secret = $parameters['oauth_token_secret'];
			unset($parameters['oauth_token_secret']);
		} else {
			$oauth_secret = '';
		}

		$base =  self::base_string($url, $parameters, $method);
		if (Controller::$debug >= 2) {
			var_dump('Base', $base);
		}

		$parameters['oauth_signature'] = self::sign_request($base, $oauth_secret);

		ksort($parameters);
		$request = $url . '?' . http_build_query($parameters);
		if (Controller::$debug >= 2) {
			var_dump('Request', $request);
		}
		return $request;
	}
}

