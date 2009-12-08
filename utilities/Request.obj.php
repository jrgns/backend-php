<?php
class Request {
	public static function getQuery($query = false) {
		if (!$query) {
			if (empty($_REQUEST['q'])) {
				$query = '';
			} else {
				$query = $_REQUEST['q'];
			}
		}
		$query = self::checkAliases($query);
		if (substr($query, -1) == '/') {
			$query = substr($query, 0, strlen($query) - 1);
		}
		return $query;
	}
	
	private static function checkAliases($query) {
		$aliases = @include(APP_FOLDER . '/configs/queries.php');
		if (empty($aliases)) {
			if (Controller::$debug) {
				Controller::addError('Invalid query aliases');
			}
			return $query;
		}
		if (array_key_exists($query, $aliases)) {
			return $aliases[$query];
		}
		foreach($aliases as $test => $new_query) {
			if ($test == $query) {
				return $new_query;
			} else {
				$search  = array('/', ':any', ':num', ':area_ctl', ':table_ctl');
				$replace = array(
					'\/',
					'([^\/]+)',
					'([0-9]+)',
					'(admin)',
					'(content|comment|content_revision|tag|image|account|hook)',
				);
				$pattern = '/^' . str_replace($search, $replace, $test) . '\/?$/';
				preg_match_all($pattern, $query, $matches);
				if (count($matches[0])) {
					foreach($matches as $key => $match) {
						$new_query = str_replace('$' . $key, current($match), $new_query);
					}
					return $new_query;
				}
			}
		}
		return $query;
	}
}
