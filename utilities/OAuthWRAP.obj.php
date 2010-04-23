<?php
class OAuthWRAP {
	private static $instances = array();
	private $parameters = array();
	
	public static function getInstance($provider) {
		if (array_key_exists($provider, self::$instances)) {
			return self::$instances[$provider];
		}
		$parameters = array(
			'consumer_key'    => Backend::getConfig($provider . '.oauth.consumer.key'),
			'consumer_secret' => Backend::getConfig($provider . '.oauth.consumer.secret'),
			'access_url'      => Backend::getConfig($provider . '.oauth.access.url'),
			'authorize_url'   => Backend::getConfig($provider . '.oauth.authorize.url'),
		);
		self::$instances[$provider] = new self($parameters);
		return self::$instances[$provider];
	}
	
	private function __construct($parameters) {
		if (empty($parameters['consumer_key']) || empty($parameters['consumer_secret'])) {
			return false;
		}
		$this->parameters = $parameters;
	}

	private function __clone() {}

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
			$query[] = self::encode($name) . '=' . self::encode($value);
		}

		$string = $parts['scheme'] . '://' . $username . $parts['host'] . $port . $parts['path'];
		return strtoupper($method) . '&' . self::encode($string) . '&' . self::encode(implode('&', $query));
	}

	public function request($url, array $parameters = array(), $method = 'GET') {
		$request = $this->get_request($url, $parameters, $method);
		$options = array(
			'method'   => $method,
			'headers'  => array('Expect:'),
			'callback' => array(__CLASS__, 'handleRequest'),
		);
		var_dump($request, $parameters);
		switch (strtoupper($method)) {
		case 'GET':
			$returned = curl_request($request, array(), $options);
			break;
		default:
			$returned = curl_request($request, $parameters, $options);
			break;
		}
		if (Controller::$debug >= 2) {
			var_dump('Returned', $returned);
		}
		return $returned;
	}
	
	public static function handleRequest($ch, $returned, $options) {
		if (Controller::$debug) {
			if ($curl_error = curl_errno($ch)) {
				Backend::addNotice('CURL Error: ' . $curl_error);
			}
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($http_code != 200) {
				Backend::addNotice('HTTP Returned code: ' . $http_code);
			}
		}
		return $returned;
	}

	protected function get_request($url, array &$parameters = array(), $method = 'GET') {
		$parameters['client_id']    = empty($parameters['oauth_consumer_key']) ? $this->parameters['consumer_key'] : $parameters['oauth_consumer_key'];

		//Don't pass the secret as a parameter, just use it
		if (!empty($parameters['oauth_token_secret'])) {
			$token_secret = $parameters['oauth_token_secret'];
			unset($parameters['oauth_token_secret']);
		} else {
			$token_secret = '';
		}


		ksort($parameters);

		switch (strtoupper($method)) {
		case 'GET':
			$request = $url . '?' . http_build_query($parameters);
			break;
		default:
			$request = $url;
			break;
		}
		if (Controller::$debug >= 2) {
			var_dump('Request', $request);
		}
		return $request;
	}

	public function getAuthURL(array $parameters = array()) {
		return $this->get_request($this->parameters['authorize_url'], $parameters);
		$returned = self::request();
		var_dump($returned);
		parse_str($returned, $vars);
		if (count($vars) == 2) {
			return $vars;
		} else {
			return false;
		}
	}

	public function getAccessToken(array $parameters = array()) {
		$returned = self::request($this->parameters['access_url'], $parameters);
		var_dump($returned); die;
		parse_str($returned, $vars);
		if (count($vars) == 4) {
			return $vars;
		} else {
			return false;
		}
	}
}

