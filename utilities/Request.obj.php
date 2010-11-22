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

		$extension = explode('.', $query);
		if (count($extension) > 1) {
			$extension = current(explode('?', end($extension)));
			$mode = false;
			switch (true) {
			case $extension == 'css':
				$mode = 'css';
				break;
			case $extension == 'json':
				$mode = 'json';
				break;
			case $extension == 'txt':
				$mode = 'text';
				break;
			//Extend the image array!
			case in_array($extension, array('png', 'jpg', 'jpeg', 'gif', 'bmp')):
				$mode = 'image';
				break;
			case in_array($extension, array('html', 'htm', 'php')):
				$mode = 'html';
				break;
			case $extension == 'atom':
				$mode = 'atom';
				break;
			case $extension == 'rss':
				$mode = 'rss';
				break;
			default:
				break;
			}
			if ($mode) {
				if (!array_key_exists('mode', $_REQUEST)) {
					$_REQUEST['mode'] = $mode;
				}
				if (substr($query, 0 - strlen($extension) - 1) == '.' . $extension) {
					$query = substr($query, 0, strlen($query) - strlen($extension) - 1);
				}
			}
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
