<?php
class TwitterSearch {
	//READMORE https://dev.twitter.com/docs/using-search
	public static $error_msg = false;
	private static $url      = 'http://search.twitter.com/search.json';

	public static function search($terms) {
		if(!is_array($terms)) {
			parse_str($terms, $terms);
		}
		$terms = array_map('urlencode', $terms);
		$search_string = implode('%20', $terms);
		return self::execute('q=' . $search_string);
	}

	public static function searchNear($terms, $location, $radius = 15, $units = 'km') {
		if(!is_array($terms)) {
			parse_str($terms, $terms);
		}
		$terms = array_map('urlencode', $terms);
		$terms = implode('%20', $terms);

		$search_string = 'q=' . $terms . '&geocode=' . urlencode($location . ',' . $radius . $units);
		return self::execute($search_string);
	}

	private static function execute($search_string) {
		self::$error_msg = false;
		if (Controller::$debug) {
			var_dump(self::$url . '?' . $search_string);
		}
		$returned = curl_request(self::$url . '?' . $search_string);
		if (!$returned) {
			self::$error_msg = 'Invalid Twitter API request';
			if (Controller::$debug) {
				var_dump(self::$url . '?' . $search_string);
			}
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
		if (!empty(self::$error_msg) && Controller::$debug) {
			Backend::addError('TwitterSearch: ' . self::$error_msg);
		}
		return false;
	}
}
