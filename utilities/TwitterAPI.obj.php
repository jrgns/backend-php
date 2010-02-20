<?php
class TwitterAPI {
	public static $started      = false;
	private static $auth_token  = false;
	private static $auth_secret = false;
	
	public static function init($token, $secret) {
		if (!$token || !$secret) {
			$_SESSION['TwitterRedirect'] = get_current_url();
			Controller::redirect('?q=twitter/request_auth');
			return false;
		} else {
			self::$auth_token  = $token;
			self::$auth_secret = $secret;
			self::$started = true;
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
		$result = curl_request('http://search.twitter.com/search.json?q=' . $parameter);
		if ($result = json_decode($result)) {
			if (array_key_exists('error', $result)) {
				Backend::addError($result->error);
			} else {
				return is_object($result) && isset($result->results) ? $result->results : false;
			}
		}
		return false;
	}
	
	public static function mentions() {
		if (self::started()) {
			$parameters = array('oauth_token' => self::$auth_token, 'oauth_token_secret' => self::$auth_secret);
			$result = OAuth::request('http://twitter.com/statuses/mentions.json', $parameters);
			if ($result = json_decode($result)) {
				if (array_key_exists('error', $result)) {
					Backend::addError($result->error);
				} else {
					return is_array($result) ? $result : false;
				}
			}
		}
		return false;
	}

	public static function tweet($status) {
		if (self::started()) {
			$parameters = array('oauth_token' => self::$auth_token, 'oauth_token_secret' => self::$auth_secret);
			$parameters['status'] = $status;
			$result = OAuth::request('http://twitter.com/statuses/update.json', $parameters, 'POST');
			if ($result = json_decode($result)) {
				if (array_key_exists('error', $result)) {
					Backend::addError($result->error);
				} else {
					return $result;
				}
			}
			return false;
		}
	}
}
