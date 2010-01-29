<?php
class TwitterAPI {
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
	
	public static function mentions() {
		if (empty($_SESSION['OAuthAccessToken'])) {
			$_SESSION['TwitterRedirect'] = get_current_url();
			Controller::redirect('?q=twitter/request_auth');
		}
		$result = OAuth::request('http://twitter.com/statuses/mentions.json', $_SESSION['OAuthAccessToken']);
		if ($result = json_decode($result)) {
			if (array_key_exists('error', $result)) {
				Controller::addError($result->error);
			} else {
				return is_array($result) ? $result : false;
			}
		}
		return false;
	}

	public static function tweet($status) {
		if (empty($_SESSION['OAuthAccessToken'])) {
			$_SESSION['TwitterRedirect'] = get_current_url();
			Controller::redirect('?q=twitter/request_auth');
		}
		$parameters = $_SESSION['OAuthAccessToken'];
		$parameters['status'] = $status;
		$result = OAuth::request('http://twitter.com/statuses/update.json', $parameters, 'POST');
		var_dump($result); die;
		if ($result = json_decode($result)) {
			if (array_key_exists('error', $result)) {
				Controller::addError($result->error);
			} else {
				return $result;
			}
		}
		return false;
	}
}
