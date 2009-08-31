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
 * @TODO We need to enable different hooks for all enabled modules. The hook_start will, as an example, be called in Controller::start
 */
class Controller {
	public static $debug;

	public static $area;
	public static $action;
	public static $id;
	public static $count;
	public static $salt = 'Change this to something random!';
	public static $mime_type;
	public static $mode;
		
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
		self::start();
		self::action();
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
					self::$debug = true;
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
			self::$init = true;

			Hook::run('init', 'post');
		}
	}
	
	public static function start() {
		if (!self::$init) {
			self::init();
		}
		if (!self::$started) {
			Hook::run('start', 'pre');

			self::check_query();

			Hook::run('start', 'post');
		}
	}
	
	public static function action() {
		$data = null;
		
		if (self::$debug) {
			var_dump('Area:   ' . self::$area);
			var_dump('Action: ' . self::$action);
			var_dump('ID:     ' . self::$id);
		}
		
		//Controll
		$control_name = class_name(self::$area);
		if (!class_exists($control_name, true)) {
			$control_name = 'AreaCtl';
		}
		$controller = new $control_name();
		$method = 'action_' . self::$action;
		if ($controller instanceof AreaCtl) {
			$data = $controller->action();
		} else {
			Controller::whoops();
		}
		Backend::add('BackendErrors', array_unique(array_filter(self::$error)));
		self::$error = array();
		Backend::add('BackendSuccess', array_unique(array_filter(self::$success)));
		self::$success = array();
		Backend::add('BackendNotices', array_unique(array_filter(self::$notice)));
		self::$notice = array();
		
		$view = self::getView();
		if ($view instanceof View) {
			self::$mode = $view->mode;
			$view->display($data, $controller);
		} else {
			die('Unrecognized Request');
		}
		return $data;
	}
	
	public static function finish() {
		Hook::run('finish', 'pre');

		$_SESSION['error'] = self::getError();
		$_SESSION['notice'] = self::getNotice();
		$_SESSION['success'] = self::getSuccess();
		$_SESSION['previous_url'] = $_SERVER['REQUEST_URI'];
		$_SESSION['cookie_is_working'] = true;

		Hook::run('finish', 'post');
	}
	
	private static function getView() {
		$view = false;
		$mime_ranges = Parser::accept_header();
		if (array_key_exists('mode', $_REQUEST)) {
			$view_name = ucwords($_REQUEST['mode']) . 'View';
			if (class_exists($view_name, true)) {
				$view = new $view_name();
			}
		}
		if (!$view && $mime_ranges) {
			$view_name = false;
			foreach($mime_ranges as $mime_type) {
				$name = class_name(str_replace('+', ' ', $mime_type['main_type']) . ' ' . str_replace('+', ' ', $mime_type['sub_type'])) . 'View';
				if (class_exists($name, true)) {
					$view_name = $name;
				} else {
					$name = class_name(str_replace('+', ' ', $mime_type['main_type'])) . 'View';
					if (class_exists($name, true)) {
						$view_name = $name;
					} else {
						$name = class_name(str_replace('+', ' ', $mime_type['sub_type'])) . 'View';
						if (class_exists($name, true)) {
							$view_name = $name;
						}
					}
				}
				if ($view_name) {
					break;
				}
			}
			if (!class_exists($view_name, true)) {
				$view_name = 'View';
			}
			$view = new $view_name();
		}
		return $view;
	}

	public static function serve_text_css(array $info = array()) {
		self::$mode = 'css';
		//Will this header always be there?
		$filename = basename($_SERVER['REDIRECT_URL'] . '.php');
		if (file_exists(BACKEND_FOLDER . '/styles/' . $filename)) {
			$filename = BACKEND_FOLDER . '/styles/' . $filename;
		} else if (file_exists(SITE_FOLDER . '/styles/' . $filename)) {
			$filename = SITE_FOLDER . '/styles/' . $filename;
		} else {
			$filename = false;
		}
		if ($filename) {
			include($filename);
		}
	}
	
	public static function serve_application_json(array $info = array()) {
		self::start();
		$content = self::action();
		die(json_encode(array('content' => $content, 'result' => (bool)$content)));
	}
	
	public static function serve_image_plain(array $info = array()) {
		self::start();
		self::action();
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

	protected static function check_query($query = false) {
		$query = $query ? $query : (array_key_exists('q', $_REQUEST) ? $_REQUEST['q'] : '');
		$terms = explode('/', $query);
		$terms = array_filter($terms);
		call_user_func_array(array('Controller', 'check_tupple'), $terms);
	}

	protected static function check_tupple($area = 'content', $action = 'display', $id = 0, $count = 30) {
		self::$area = self::check_map('area', $area);
		Backend::add('area', self::$area);
		self::$action = self::check_map('action', $action);
		Backend::add('action', self::$action);
		self::$id = self::check_map('id', $id);
		Backend::add('id', self::$id);
		self::$count = self::check_map('count', $count);
		Backend::add('count', self::$count);
		return true;
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
			$location = $location ? $location : (empty($_SESSION['previous_url']) ? '' : $_SESSION['previous_url']);
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
