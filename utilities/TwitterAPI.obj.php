<?php
class TwitterAPI {
	public static $started      = false;
	public static $error_msg    = false;
	private static $auth_token  = false;
	private static $auth_secret = false;
	private static $oauth       = false;
	
	public static function init($token = false, $secret = false) {
		if (!$token || !$secret) {
			$_SESSION['TwitterRedirect'] = get_current_url();
			Controller::redirect('?q=twitter/request_auth');
			return false;
		} else {
			self::$auth_token  = $token;
			self::$auth_secret = $secret;
			self::$started = true;
			self::$oauth = OAuth::getInstance('twitter');
			return true;
		}
	}
	
	public static function started() {
		if (!self::$started) {
			self::init(false, false);
		}
		return self::$started;
	}

	public static function search($parameter) {
		self::$error_msg = false;
		$returned = curl_request('http://search.twitter.com/search.json?q=' . $parameter);
		if (!$returned) {
			self::$error_msg = 'Invalid Twitter API request';
			return false;
		} else if (!($result = json_decode($returned))) {
			self::$error_msg = 'Invalid JSON returned: ' . $returned;
			return false;
		}
		if (array_key_exists('error', $result)) {
			self::$error_msg = $result->error;
		} else {
			return is_object($result) && isset($result->results) ? $result->results : false;
		}
		return false;
	}
	
	public static function mentions() {
		self::$error_msg = false;
		if (!self::started()) {
			self::$error_msg = 'Could not get Authorization';
			return false;
		}
		$parameters = array('oauth_token' => self::$auth_token, 'oauth_token_secret' => self::$auth_secret);
		$returned = self::$oauth->request('http://api.twitter.com/1/statuses/mentions.json', $parameters);
		if (!$returned) {
			self::$error_msg = 'Invalid Twitter API request';
			return false;
		} else if (!($result = json_decode($returned))) {
			self::$error_msg = 'Invalid JSON returned: ' . $returned;
			return false;
		}

		if (array_key_exists('error', $result)) {
			self::$error_msg = $result->error;
		} else {
			return is_array($result) ? $result : false;
		}
		return false;
	}

	public static function tweet($status) {
		self::$error_msg = false;
		if (!self::started()) {
			self::$error_msg = 'Could not get Authorization';
			return false;
		}
		$parameters = array('oauth_token' => self::$auth_token, 'oauth_token_secret' => self::$auth_secret);
		$parameters['status'] = $status;
		$returned = self::$oauth->request('http://api.twitter.com/1/statuses/update.json', $parameters, 'POST');
		if (!$returned) {
			self::$error_msg = 'Invalid Twitter API request';
			return false;
		} else if (!($result = json_decode($returned))) {
			self::$error_msg = 'Invalid JSON returned: ' . $returned;
			return false;
		}
		if (array_key_exists('error', $result)) {
			self::$error_msg = $result->error;
		} else {
			return $result;
		}
		return false;
	}
	
	public static function lists($username) {
		self::$error_msg = false;
		if (!self::started()) {
			self::$error_msg = 'Could not get Authorization';
			return false;
		}
		$parameters = array('oauth_token' => self::$auth_token, 'oauth_token_secret' => self::$auth_secret);
		$returned = self::$oauth->request('http://api.twitter.com/1/' . $username . '/lists.json', $parameters, 'GET');
		if (!$returned) {
			self::$error_msg = 'Invalid Twitter API request';
			return false;
		} else if (!($result = json_decode($returned))) {
			self::$error_msg = 'Invalid JSON returned: ' . $returned;
			return false;
		}
		if (array_key_exists('error', $result)) {
			self::$error_msg = $result->error;
		} else {
			return $result;
		}
		return false;
	}
	
	public static function tweets($username) {
		self::$error_msg = false;
		if (!self::started()) {
			self::$error_msg = 'Could not get Authorization';
			return false;
		}

		$parameters['screen_name'] = $username;
		$data = http_build_query($parameters);
		if (Controller::$debug) {
			echo '<pre>http://api.twitter.com/1/statuses/user_timeline.json?' . $data . '</pre>';
		}
		$returned = curl_request('http://api.twitter.com/1/statuses/user_timeline.json?' . $data, array(), array('cache' => 60 * 60 * 24));
		//$returned = file_get_contents(APP_FOLDER . '/tweets.js');
		if (Controller::$debug) {
			echo "<pre>$returned</pre>";
		}
		if (!$returned) {
			self::$error_msg = 'Invalid Twitter API request';
			return false;
		} else if (!($result = json_decode($returned))) {
			self::$error_msg = 'Invalid JSON returned: ' . $returned;
			return false;
		}
		if (array_key_exists('error', $result)) {
			self::$error_msg = $result->error;
		} else {
			return $result;
		}
		return false;
	}
}
