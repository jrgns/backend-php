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

	public static $area = 'home';
	public static $action = 'index';

	public static $parameters = array();
	
	//TODO move this to the config or Application class
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
	
	public static $firephp = false;
	
	public static function serve(array $info = array()) {
		self::init();
		self::start();
		self::action();
		self::finish();
	}

	public static function init() {
		if (!self::$init) {
			session_set_cookie_params(0, SITE_SUB_FOLDER, null, null, true);
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
			self::parseQuery();
			$toret = true;
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
			self::redirect('?q=' . Value::get('default_controller', 'home') . '/' . Value::get('default_action', 'index'));
		}
		if (Controller::$debug) {
			var_dump('User Controller ' . $control_name);
		}

		$controller = new $control_name();

		if ($controller instanceof AreaCtl) {
			$run_action = Hook::run('action', 'pre', array(), array('toret' => true));
			if ($run_action) {
				$result = $controller->action();
				if (Controller::$debug) {
					Controller::addNotice('Code for this page is in the ' . get_class($controller) . ' Controller');
				}
			} else {
				$result = null;
			}
			Hook::run('action', 'post');
		} else {
			Controller::whoops();
		}
		
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

		Hook::run('finish', 'post');
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
				$extension = current(explode('?', end($extension)));
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
		if ($view_name == 'View') {
			$view_name = Backend::getConfig('backend.default.view', 'HtmlView');
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
	
	public static function setArea($area) {
		if (!self::$started) {
			self::$area = $area;
		} else {
			trigger_error('Application already started, can\'t set area', E_USER_ERROR);
		}
	}

	public static function setAction($action) {
		if (!self::$started) {
			self::$action = $action;
		} else {
			trigger_error('Application already started, can\'t set action', E_USER_ERROR);
		}
	}
	
	protected static function parseQuery($query = false) {
		$query = Request::getQuery($query);
		
		if (!Value::get('admin_installed', false) && !in_array($query, array('admin/pre_install', 'admin/install'))) {
			$query = 'admin/pre_install';
		}

		if (!empty($query)) {
			$terms = explode('/', $query);
		} else {
			$terms = array();
		}
		//We want to now what a parameter was, even if it's empty, so don't filter
		//$terms = array_filter($terms);
		
		self::$area   = count($terms) ? array_shift($terms) : Value::get('default_controller', 'home');
		self::$action = count($terms) ? array_shift($terms) : Value::get('default_action', 'index');
		
		self::$parameters = !empty($terms) ? $terms : array();
		if (Component::isActive(class_name(self::$area)) && method_exists(class_name(self::$area), 'checkParameters')) {
			self::$parameters = call_user_func(array(class_name(self::$area), 'checkParameters'), self::$parameters);
		}
		return self::$parameters;
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
			$log_to_file = defined('BACKEND_INSTALLED') && BACKEND_INSTALLED ? Value::get('log_to_file', false) : false;
			if ($log_to_file) {
				@list($file, $log_what) = explode('|', $log_to_file);
				$file     = empty($file)     ? 'logfile_' . date('Ymd') . 'txt' : $file;
				$log_what = empty($log_what) ? '*' : explode(',', $log_what);
				if ((is_array($log_what) && in_array($what, $log_what)) || $log_what == '*') {
					if (!file_exists(APP_FOLDER . '/logs/')) {
						mkdir(APP_FOLDER . '/logs/', 0755);
					}
					$fp = fopen(APP_FOLDER . '/logs/' . $file, 'a');
					if ($fp) {
						$query = Controller::$area . '/' . Controller::$action . '/' . implode('/', Controller::$parameters);
						fwrite($fp, time() . "\t" . $query . "\t" . $what . "\t" . $string . PHP_EOL);
					}
				}
			}
			if (is_array($string)) {
				$toret = true;
				foreach($string as $one_string) {
					$toret = self::addSomething($what, $one_string, $options) && $toret;
				}
			} else {
				$toret = true;
				array_push(self::$$what, $string);
			}
		}
		return $toret;
	}

	/**
	 * Redirect to a specified location.
	 *
	 * If the location is omitted, go to the current URL. If $location == 'previous', go the previous URL for the current mode.
	 */	
	public static function redirect($location = false) {
		switch ($location) {
		case false:
			$location = get_current_url();
			break;
		case 'previous':
			if (!empty($_SESSION['previous_url'])) {
				if (is_array($_SESSION['previous_url'])) {
					$location = !empty($_SESSION['previous_url'][self::$view->mode]) ? $_SESSION['previous_url'][self::$view->mode] : reset($_SESSION['previous_url']);
				} else {
					$location = $_SESSION['previous_url'];
				}
			} else {
				$location = false;
			}
			break;
		}

		//This should fix most redirects, but it may happen that location == '?debug=true&q=something/or/another' or something similiar
		if (Value::get('clean_urls', false) && substr($location, 0, 3) == '?q=') {
			$location = SITE_LINK . substr($location, 3);
		}
		//Add some meta variables
		if (!empty($_SERVER['QUERY_STRING'])) {
			parse_str($_SERVER['QUERY_STRING'], $vars);
			$new_vars = array();
			if (array_key_exists('debug', $vars)) {
				$new_vars['debug'] = $vars['debug'];
			}
			if (array_key_exists('nocache', $vars)) {
				$new_vars['nocache'] = $vars['nocache'];
			}
			if (array_key_exists('recache', $vars)) {
				$new_vars['recache'] = $vars['recache'];
			}
			if (array_key_exists('mode', $vars)) {
				$new_vars['mode'] = $vars['mode'];
			}

			$url = parse_url($location);
			parse_str($url['query'], $old_vars);
			//Allow the redirect to overwrite these vars
			$new_vars = array_merge($new_vars, $old_vars);

			$old_url = parse_url(get_current_url());
			$url['query'] = http_build_query($new_vars);
			$url = array_merge($old_url, $url);
			$location = build_url($url);
		}
			
		try {
			if (self::$debug) {
				self::addSuccess('The script should now redirect to <a href="' . $location . '">here</a>');
			} else {
				//Redirect
				self::finish();
				header('Location: ' . $location);
				die('redirecting to <a href="' . $location . '">');
			}
		} catch (Exception $e) {
			Controller::addError('Could not redirect');
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
	
	static public function setError(array $errors = array()) {
		self::$error = $errors;
	}
	
	static public function addNotice($content, $options = array()) {
		return self::addSomething('notice', $content, $options);
	}
	
	static public function getNotice() {
		return self::$notice;
	}

	static public function setNotice(array $notices = array()) {
		self::$notice = $notices;
	}
	
	static public function addSuccess($content, $options = array()) {
		return self::addSomething('success', $content, $options);
	}
	
	static public function getSuccess() {
		return self::$success;
	}

	static public function setSuccess(array $successes = array()) {
		self::$success = $successes;
	}
}
