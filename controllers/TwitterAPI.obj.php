<?php
class TwitterAPI extends AreaCtl {
	public static function search($parameter) {
		$result = curl_request('http://search.twitter.com/search.json?q=' . $parameter);
		if ($result = json_decode($result)) {
			if (array_key_exists('error', $result)) {
				Controller::addError($result->error);
			} else {
				return is_object($result) && isset($result->results) ? $result->results : false;
			}
		}
		return false;
	}
	
	public static function mentions($username = false, $password = false) {
		$username = $username ? $username : Value::get('twitter_username', false);
		$password = $password ? $password : Value::get('twitter_password', false);
		if ($username && $password) {
			$result = curl_request('http://twitter.com/statuses/mentions.json', array(), array('username' => $username, 'password' => $password));
			if ($result = json_decode($result)) {
				if (array_key_exists('error', $result)) {
					Controller::addError($result->error);
				} else {
					return is_array($result) ? $result : false;
				}
			}
		}
		return false;
	}
}
