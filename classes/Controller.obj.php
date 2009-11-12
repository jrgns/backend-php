<?php
/**
 * The file that defines the Controller class.
 *
 * @author J Jurgens du Toit (JadeIT cc) <jurgens.dutoit@gmail.com> - initial API and implementation
 * @copyright Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available {@link http://www.eclipse.org/legal/epl-v10.html here}
 * @license http://www.eclipse.org/legal/epl-v10.html Eclipse Public License v1.0
 * @package Core
 */

/**
 * The main controller for the Backend
 *
 * @TODO We need to check which modujles are available / enabled.
 * @TODO We need to enable different hooks for all enabled components. The hook_start will, as an example, be called in Controller::start
 */
class Controller {
	public static $debug;

	//old
	public static $area;
	public static $action;
	public static $id;
	public static $count;
	//new
	public static $parameters = array();

	public static $salt = 'Change this to something random!';
	public static $view = false;
		
	protected static $error = array();
	protected static $notice = array();
	protected static $success = array();

	protected static $content = array();
	protected static $scripts = array();
	protected static $styles = array();	

	protected static $started = false;
	protected static $init = false;
	
	public static function serve(array $info = array()) {
		self::init();
		$controller = self::start();
		self::action($controller);
		self::finish();
	}

	public static function init() {
		if (!self::$init) {
			session_name('Controller');
			session_start();

			self::check_quotes();

			date_default_timezone_set('Africa/Johannesburg');

			Hook::run('init', 'pre');

			//Debugging
			self::$debug = false;
			switch (true) {
				case array_key_exists('debug', $_REQUEST):
					//Default to lowest level
					self::$debug = is_numeric($_REQUEST['debug']) ? (int)$_REQUEST['debug'] : 1;
					break;
			}

			Backend::add('debug', self::$debug);
			if (true || self::$debug) {
				ini_set('display_errors', 1);
				ini_set('error_reporting', E_ALL | E_STRICT);
			} else {
				ini_set('display_errors', 0);
			}

			//Sessions
			if (array_key_exists('error', $_SESSION)) {
				self::$error = $_SESSION['error'];
			}
			if (array_key_exists('notice', $_SESSION)) {
				self::$notice = $_SESSION['notice'];
			}
			if (array_key_exists('success', $_SESSION)) {
				self::$success = $_SESSION['success'];
			}
			
			//View
			self::$view = self::getView();
			
			Hook::run('init', 'post');
			self::$init = true;
		}
	}

	/**
	 * Startup the application by parsing the query, etc
	 *
	 * @todo Maybe prepend something to the variables tht get added
	 */	
	public static function start() {
		$toret = false;
		if (!self::$init) {
			self::init();
		}
		if (!self::$started) {
			Hook::run('start', 'pre');

			$toret = self::parseQuery();
			
			foreach ($toret as $name => $value) {
				self::$parameters[$name] = $value;
				if (property_exists('Controller', $name)) {
					self::$$name = self::check_map($name, $value);
				}
				Backend::add($name, $value);
			}

		}
		return $toret;
	}
	
	public static function action() {
		$data = null;
		
		//Control
		$control_name = class_name(self::$area);
		if (!Component::isActive($control_name, true)) {
			if (Controller::$debug) {
				Controller::addError('Component is Inactive');
			}
			$control_name = 'TableCtl';
		}
		if (Controller::$debug) {
			var_dump('User Controller ' . $control_name);
		}

		$controller = new $control_name();

		if ($controller instanceof AreaCtl) {
			Hook::run('action', 'pre');
			$result = $controller->action();
			Hook::run('action', 'post');
		} else {
			Controller::whoops();
		}
		Backend::add('BackendErrors', array_unique(array_filter(self::$error)));
		self::$error = array();
		Backend::add('BackendSuccess', array_unique(array_filter(self::$success)));
		self::$success = array();
		Backend::add('BackendNotices', array_unique(array_filter(self::$notice)));
		self::$notice = array();
		
		if (self::$view instanceof View) {
			Hook::run('action_display', 'pre', array($result));
			self::$view->display($result, $controller);
			Hook::run('action_display', 'post', array($result));
		} else {
			die('Unrecognized Request');
		}
		return $result;
	}
	
	public static function finish() {
		Hook::run('finish', 'pre');

		$_SESSION['error'] = self::getError();
		$_SESSION['notice'] = self::getNotice();
		$_SESSION['success'] = self::getSuccess();
		if (empty($_SESSION['previous_url']) || !is_array($_SESSION['previous_url'])) {
			$_SESSION['previous_url'] = array();
		}
		$_SESSION['previous_url'][self::$view->mode] = $_SERVER['REQUEST_URI'];
		$_SESSION['cookie_is_working'] = true;

		Hook::run('finish', 'post');
	}
	
	public static function parameter($name) {
		$toret = null;
		if (array_key_exists($name, self::$parameters)) {
			$toret = self::$parameters[$name];
		}
		return $toret;
	}
	
	/**
	 * Decide on which view to use
	 *
	 * In an ideal world, we will just use the first mime type in the Http-Acccept header. But IE decided
	 * to put a lot of crud in it's Accept header, so we need some hacks.
	 *
	 * Mode takes precedence over the extension, which takes precedence over the accept headers/
	 *
	 * @todo the extension screws up requests such as ?q=content/display/2.txt, as the id is now 2.txt, and not 2 as expected.
	 * @todo Make the process on deciding a view better / extendable! Or, setup preferences that ignore the
	 * Accept header, or just rely on what the client asks for (mode=[json|xml|xhtml|jpg])
	 */
	private static function getView() {
		$view_name = false;
		if (array_key_exists('mode', $_REQUEST)) {
			$view_name = ucwords($_REQUEST['mode']) . 'View';
		} else {
			//Check for an extension
			$extension = explode('.', str_replace(dirname($_SERVER['SCRIPT_NAME']), '', $_SERVER['REQUEST_URI']));
			if (count($extension) > 1) {
				$extension = end($extension);
				switch (true) {
				case $extension == 'css':
					$view_name = 'CssView';
					break;
				case $extension == 'json':
					$view_name = 'JsonView';
					break;
				case $extension == 'txt':
					$view_name = 'TextView';
					break;
				//Extend the image array!
				case in_array($extension, array('png', 'jpg', 'jpeg', 'gif', 'bmp')):
					$view_name = 'ImageView';
					break;
				case in_array($extension, array('html', 'htm')):
					$view_name = 'HtmlView';
					break;
				}
			}
		}

		if (!$view_name) {
			$mime_ranges = Parser::accept_header();
			if ($mime_ranges) {
				$types = array();
				$main_types = array();
				$view_name = false;
				foreach($mime_ranges as $mime_type) {
					$types[] = $mime_type['main_type'] . '/' . $mime_type['sub_type'];
					$main_types[] = $mime_type['main_type'];
					if (!$view_name) {
						$name = class_name(str_replace('+', ' ', $mime_type['main_type']) . ' ' . str_replace('+', ' ', $mime_type['sub_type'])) . 'View';
						if (Component::isActive($name)) {
							$view_name = $name;
						} else {
							$name = class_name(str_replace('+', ' ', $mime_type['main_type'])) . 'View';
							if (Component::isActive($name)) {
								$view_name = $name;
							} else {
								$name = class_name(str_replace('+', ' ', $mime_type['sub_type'])) . 'View';
								if (Component::isActive($name)) {
									$view_name = $name;
								}
							}
						}
					}
				}
				if (in_array('image', $main_types) && in_array('application', $main_types)) {
				//Probably IE
					$view_name = 'HtmlView';
				} else if (in_array('application/xml', $types) && in_array('application/xhtml+xml', $types) && in_array('text/html', $types)) {
				//Maybe another confused browser that asks for XML and HTML
					$view_name = 'HtmlView';
				} else if (count($mime_ranges) == 1 && $mime_ranges[0]['main_type'] == '*' && $mime_ranges[0]['sub_type'] == '*') {
					$view_name = Backend::getConfig('backend.default.view', 'HtmlView');
				}
			} else {
				$view_name = Backend::getConfig('backend.default.view', 'HtmlView');
			}
		}
		if (!Component::isActive($view_name)) {
			$view_name = 'View';
		}
		$view = new $view_name();

		return $view;
	}

	private static function check_quotes() {
		if (get_magic_quotes_gpc()) {
			function stripslashes_deep($value) {
				$value = is_array($value) ?
				            array_map('stripslashes_deep', $value) :
				            stripslashes($value);

				return $value;
			}
			$_POST = array_map('stripslashes_deep', $_POST);
			$_GET = array_map('stripslashes_deep', $_GET);
			$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
			$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
		}
	}

	protected static function parseQuery($query = false) {
		if (empty($_REQUEST['q'])) {
			$query = Value::get('default_query', 'content/list/' . Value::get('list_length', 5));
		} else {
			$query = $_REQUEST['q'];
		}
		$terms = explode('/', $query);
		$terms = array_filter($terms);

		$terms = call_user_func_array(array('Controller', 'checkTuple'), $terms);
		
		if (Component::isActive(class_name($terms['area'])) && method_exists(class_name($terms['area']), 'checkTuple')) {
			$terms = call_user_func(array(class_name($terms['area']), 'checkTuple'), $terms);
		}
		return $terms;
	}

	protected static function checkTuple($area = 'content', $action = 'display', $id = null) {
		$toret = array(
			'area'   => $area,
			'action' => $action,
		);
		if ($action == 'list') {
			$toret['count'] = $id;
		} else {
			$toret['id'] = $id;
		}
		return $toret;
	}
	
	public static function check_map($what, $value) {
		$map = Backend::get($what . '_maps');
		if (is_array($map)) {
			$value = array_key_exists($value, $map) ? $map[$value] : $value;
		}
		return $value;
	}

	public static function check_reverse_map($what, $value) {
		$map = Backend::get($what . '_maps');
		if (is_array($map)) {
			$key = array_search($value, $map);
			if ($key) {
				$value = $key;
			}
		}
		return $value;
	}
	
	private static function addSomething($what, $string, $options = array()) {
		$toret = false;
		if (!is_null($string)) {
			if (is_array($string)) {
				foreach($string as $one_string) {
					$toret = true;
					array_push(self::$$what, $one_string);
				}
			} else {
				$toret = true;
				array_push(self::$$what, $string);
			}
		}
		return $toret;
	}

	public function __call($function, $params) {
		$toret = false;
	/*
		if (substr($function, 0, 3) == 'add') {
			switch (strtolower(substr($function, 3))) {
				case 'content':
				case 'error':
				case 'notice':
				case 'success':
					$toret = $this->addSomething(strtolower(substr($function, 3)), array_shift($params), array_shift($params));
					break;
				default:
					break;
			}
		} else 
	*/
			if (substr($function, 0, 3) == 'get') {
				$toret = null;
				$what = strtolower(substr($function, 3));
				if (property_exists('Controller', $what)) {
					return self::$$what;
				}
		}
		return $toret;
	}

	/**
	 * Redirect to a specified location.
	 *
	 * If the location is omitted, go to the previous URL
	 */	
	public static function redirect($location = false) {
		try {
			$location = $location ? $location : (empty($_SESSION['previous_url']) ? false : $_SESSION['previous_url']);
			if (is_array($location)) {
				if (array_key_exists(self::$view->mode, $location)) {
					$location = $location[self::$view->mode];
				} else {
					$location = current($location);
				}
			}
			//This should fix most redirects, but it may happen that location == '?debug=true&q=something/or/another' or something similiar
			if (Value::get('clean_urls', false) && substr($location, 0, 3) == '?q=') {
				$location = SITE_LINK . substr($location, 3);
			}
			//There's some other variables that should also be transported, but I need this NOW
			if (array_key_exists('debug', $_REQUEST)) {
				if (strpos($location, '?') !== false) {
					$location .= '&debug=' . $_REQUEST['debug'];
				} else {
					$location .= '?debug=' . $_REQUEST['debug'];
				}
			}
			
			if (self::$debug) {
				self::addSuccess('The script should now redirect to <a href="' . $location . '">here</a>');
			} else {
				redirect($location, true);
				self::finish();
				die('redirecting');
			}
		} catch (Exception $e) {
		}
		return true;
	}
	
	/**
	 * Redirect to the current URL
	 */
	public static function refresh() {
		try {
			redirect(false, true);
			self::finish();
			die('redirecting');
		} catch (Exception $e) {
		}
		return true;
	}
	
	public static function whoops($options = array()) {
		if (!is_array($options)) {
			$options = array('message' => $options);
		}
		$title = array_key_exists('title', $options) ? $options['title'] : 'Whoops!';
		$msg = array_key_exists('message', $options) ? $options['message'] : 'Looks like something went wrong...';
		Backend::add('Sub Title', $title);
		self::addContent($msg);
		if (array_key_exists('debug', $_REQUEST)) {
			print_stacktrace();
		}
	}
	
	function __destruct() {
		self::finish();
	}
	
	static public function addContent($content, $options = array()) {
		return self::addSomething('content', $content, $options);
	}
	
	static public function getContent() {
		return self::$content;
	}
	
	static public function addScript($script, $options = array()) {
		return self::addSomething('scripts', $script, $options);
	}
	
	static public function getScripts() {
		return self::$scripts;
	}
	
	static public function addStyle($style, $options = array()) {
		return self::addSomething('styles', $style, $options);
	}
	
	static public function getStyles() {
		return self::$styles;
	}
	
	static public function addError($content, $options = array()) {
		return self::addSomething('error', $content, $options);
	}
	
	static public function getError() {
		return self::$error;
	}
	
	static public function addNotice($content, $options = array()) {
		return self::addSomething('notice', $content, $options);
	}
	
	static public function getNotice() {
		return self::$notice;
	}
	
	static public function addSuccess($content, $options = array()) {
		return self::addSomething('success', $content, $options);
	}
	
	static public function getSuccess() {
		return self::$success;
	}
}
