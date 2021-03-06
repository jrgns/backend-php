<?php
class Request {
	public static $allowed_extensions = array(
		'htm', 'html', 'xml', 'json', 'css', 'txt', 'png', 'jpg', 'jpeg', 'gif', 'bmp', 'atom', 'rss'
	);

	public static function getQuery($query = false) {
		if (!$query) {
			if (empty($_REQUEST['q'])) {
				$query = '';
			} else {
				$query = $_REQUEST['q'];
			}
		}
		if (substr($query, -1) == '/') {
			$query = substr($query, 0, strlen($query) - 1);
		}

		$query = self::checkAliases($query);
		return $query;
	}
	
	private static function checkAliases($query) {
		if (file_exists(APP_FOLDER . '/configs/queries.php')) {
			$aliases = include(APP_FOLDER . '/configs/queries.php');
		}
		if (empty($aliases)) {
			if (Controller::$debug) {
				Backend::addError('Invalid query aliases');
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
				$search  = array('/', ':any', ':num', ':controller', ':area_ctl', ':table_ctl');
				//TODO Get the controllers from the Component table, remove area and table
				$replace = array(
					'\/',
					'([^\/]+)',
					'([0-9]+)',
					'(home)',
					'(admin)',
					'(content|comment|content_revision|tag|image|backend_user|hook)',
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
