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
 */
class Controller {
	const MODE_REQUEST = 'request';
	const MODE_EXECUTE = 'execute';

	public static $debug;

	public static $mode = self::MODE_REQUEST;

	protected static $query_string = false;
	protected static $query_vars   = array();
	protected static $method       = null;
	protected static $payload      = null;

	public static $area = 'home';
	public static $action = 'index';

	public static $parameters = array();

	public static $salt = false;
	public static $view = false;

	protected static $started = false;
	protected static $init    = false;

	public static $firephp    = false;

	private static $whoopsed    = false;
	private static $ob_level    = 0;
	//Only check permissions if this is tue
	private static $check_perms = true;

	public static function serve($query_string = false, $method = null, $payload = null, $check_perms = true) {
		if ($query_string) {
			self::$mode         = self::MODE_EXECUTE;
			self::$query_string = $query_string;
			self::$method       = is_null($method) ? request_method() : $method;
			//The payload must be specified, either through the query string or in the payload itself...
			self::$payload      = $payload;
		} else {
			self::$mode         = self::MODE_REQUEST;
			self::$query_string = $_SERVER['QUERY_STRING'];
			self::$method       = is_null($method) ? request_method() : $method;
			///Payload is always set with _GET / _POST with requests
			switch(self::$method) {
			case 'GET':
				self::$payload = array_map('stripslashes_deep', $_GET);
				break;
			case 'POST':
				//Add GET variables as well. Should POST overwrites GET vars
				self::$payload = array_map('stripslashes_deep', $_GET);
				self::$payload = array_merge(self::$payload, array_map('stripslashes_deep', $_POST));
				break;
			}
		}
		self::$check_perms = $check_perms;
		self::$ob_level    = ob_get_level();

		parse_str(self::$query_string, self::$query_vars);
		if (empty(self::$payload) && !is_array(self::$payload)) {
			self::$payload = array();
		}
		//Make sure that all of query string vars is in the payload.
		if (self::$mode == self::MODE_EXECUTE && self::$method == 'GET') {
			self::$payload = array_merge(self::$query_vars, self::$payload);
		}

		self::init();
		self::start();
		list ($controller, $result) = self::action();
		if ($controller instanceof AreaCtl) {
			self::display($controller, $result);
		}
		if (self::$mode == self::MODE_EXECUTE) {
			self::finish();
		}
	}

	public static function init() {
		if (!self::$init) {
			if (self::$mode == self::MODE_REQUEST) {
				if ($_SERVER['SERVER_PORT'] == 443 || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) {
					$secure = true;
				} else {
					$secure = false;
				}
				if (WEB_SUB_FOLDER == '/') {
					//WTF?
					//print_stacktrace(); die;
				}
				if (session_id() == '') {
					session_set_cookie_params(0, WEB_SUB_FOLDER, null, $secure, true);
					session_name('Controller');
					@session_start();
				}

				date_default_timezone_set(ConfigValue::get('Timezone', 'Africa/Johannesburg'));
			}

			self::check_quotes();
			self::$salt = ConfigValue::get('Salt', 'Change this to something random!');

			//TODO jrgns: Don't know if I like this here...
			$user = BackendUser::check();
			//Debugging
			self::$debug = false;
			if (SITE_STATE != 'production' || ($user && in_array('superadmin', $user->roles))) {
				switch (true) {
				case array_key_exists('debug', self::$query_vars):
					//Default to lowest level
					self::$debug = is_numeric(self::$query_vars['debug']) ? (int)self::$query_vars['debug'] : 1;
					break;
				}
			}
			if ($config_debug = ConfigValue::get('Debug', false)) {
				self::$debug = $config_debug;
			}

			Backend::add('debug', self::$debug);
			if (SITE_STATE != 'production' || self::$debug) {
				ini_set('display_errors', 1);
				ini_set('error_reporting', E_ALL | E_STRICT);
			} else {
				ini_set('display_errors', 0);
			}

			//q in the payload overrides the q in the query string
			$query = array_key_exists('q', self::$payload) ?
						self::$payload['q'] :
						(
							array_key_exists('q', self::$query_vars) ?
							self::$query_vars['q'] :
							''
						)
			;
			$query = self::checkQuery(Request::getQuery($query));

			$query = Hook::run('init', 'pre', array($query));
			self::parseQuery($query);

			//View
			self::$view = View::getInstance();
			if (!(self::$view instanceof View)) {
				self::$view = View::getInstance(ConfigValue::get('DefaultView', 'HtmlView'));
				self::whoops('Unrecognized Request', array('message' => 'Could not find a View for the Request', 'code_hint' => 406));
				if (self::$debug) {
					print_stacktrace();
					var_dump(self::$query_vars, $query, $_REQUEST, $_SERVER);
				}
			}

			//Sessions
			if (array_key_exists('error', $_SESSION)) {
				Backend::addError($_SESSION['error']);
			}
			if (array_key_exists('notice', $_SESSION)) {
				Backend::addNotice($_SESSION['notice']);
			}
			if (array_key_exists('success', $_SESSION)) {
				Backend::addSuccess($_SESSION['success']);
			}

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
		if (!self::$started) {
			Hook::run('start', 'pre');

			Hook::run('start', 'post');
			self::$started = true;
		}
	}

	public static function action() {
		if (self::$whoopsed) {
			return array(null, null);
		}
		$control_name = class_name(self::$area);
		if (Controller::$debug) {
			Backend::addNotice('Trying Controller ' . $control_name);
		}
		$controller = class_exists($control_name, true) ? new $control_name() : false;
		if (!($controller instanceof AreaCtl && Component::isActive($control_name))) {
    		if (Backend::getDB('default')) {
    		    //We have a DB
			    Controller::whoops(
			        'Component ' . $control_name . ' is Inactive or Invalid',
			        array(
			            'message' => 'The requested component doesn\'t exist or is inactive.',
			            'code_hint' => 404
		            )
	            );
			    self::$area   = ConfigValue::get('DefaultErrorController', 'home');
			    self::$action = ConfigValue::get('DefaultErrorAction', 'error');
			    $control_name = class_name(self::$area);
		    } else {
		        //No DB, allow Content to check if the template exists
			    self::$parameters[0] = self::$area . '/' . self::$action;
			    if (count(self::$parameters[0]) > 1) {
			        self::$parameters = array(self::$parameters[0]);
			    }
			    self::$area   = ConfigValue::get('DefaultErrorController', 'content');
			    self::$action = ConfigValue::get('DefaultErrorAction', 'display');
			    $control_name = class_name(self::$area);
		    }
		}

		$controller = class_exists($control_name, true) ? new $control_name() : false;
		if (!($controller instanceof AreaCtl && Component::isActive($control_name))) {
			Controller::whoops('Invalid Error Area Controller', 'The DefaultErrorController is invalid or inactive.');
			return null;
		}

		Backend::add('Area', self::$area);
		Backend::add('Action', self::$action);
		if (Controller::$debug) {
			Backend::addNotice('Code for this page is in the ' . get_class($controller) . ' Controller');
		}

		$result = null;
		$run_action = Hook::run('action', 'pre', array(), array('toret' => true));
		if ($run_action) {
			$result = $controller->action();
		}
		Hook::run('action', 'post');
		return array($controller, $result);
	}

	public static function display(AreaCtl $controller, $result) {
		if (!(self::$view instanceof View)) {
			Controller::whoops('Invalid View', array('message' => 'The requested mode doesn\'t have a valid associated View.', 'code_hint' => 406));
			return null;
		}

		Hook::run('action_display', 'pre', array($result));
		self::$view->display($result, $controller);
		Hook::run('action_display', 'post', array($result));
	}

	/**
	 * This function get's called via register_shutdown_function when the script finishes or exit is called
	 */
	public static function finish() {
		if (self::$init) {
			Hook::run('finish', 'pre');

			$_SESSION['error']   = Backend::getError();
			$_SESSION['notice']  = Backend::getNotice();
			$_SESSION['success'] = Backend::getSuccess();
			if (!empty(self::$view)) {
				if (empty($_SESSION['previous_url']) || !is_array($_SESSION['previous_url'])) {
					$_SESSION['previous_url'] = array();
				}
				if (array_key_exists('REQUEST_URI', $_SERVER)) {
					$_SESSION['previous_url'][self::$view->mode] = $_SERVER['REQUEST_URI'];
				}

				if (empty($_SESSION['previous_area']) || !is_array($_SESSION['previous_area'])) {
					$_SESSION['previous_area'] = array();
				}
				$_SESSION['previous_area'][self::$view->mode] = self::$area;

				if (empty($_SESSION['previous_action']) || !is_array($_SESSION['previous_action'])) {
					$_SESSION['previous_action'] = array();
				}
				$_SESSION['previous_action'][self::$view->mode] = self::$action;

				if (empty($_SESSION['previous_parameters']) || !is_array($_SESSION['previous_parameters'])) {
					$_SESSION['previous_parameters'] = array();
				}
				$_SESSION['previous_parameters'][self::$view->mode] = self::$parameters;
			}

			//Check if we encountered a fatal error
			if ($last_error = error_get_last()) {
				if ($last_error['type'] === E_ERROR && SITE_STATE == 'production') {
					$id = send_email(
						ConfigValue::get('author.Email', ConfigValue::get('Email', 'info@' . SITE_DOMAIN)),
						'Fatal PHP Error in ' . ConfigValue::get('Title', 'Application'),
						'Error: ' . var_export($last_error, true) . PHP_EOL
						. 'Query: ' . self::$query_string . PHP_EOL
						. 'Payload: ' . var_export(self::$payload, true)
					);
				}
			}

			//Clean up
			self::$query_string = false;
			self::$query_vars   = array();
			self::$method       = null;
			self::$payload      = false;

			self::$area = 'home';
			self::$action = 'index';

			self::$parameters = array();

			self::$salt = false;
			self::$view = false;

			self::$started = false;
			self::$init    = false;

			self::$firephp    = false;

			self::$whoopsed  = false;

			Backend::shutdown();

			Hook::run('finish', 'post');

			while (ob_get_level() > self::$ob_level) {
				ob_end_flush();
			}
		}
		self::$init = false;
	}

	public static function getMethod() {
		return self::$method;
	}

	public static function getCheckPermissions() {
		return self::$check_perms;
	}

	public static function getPayload() {
		return self::$payload;
	}

	public static function getQueryVars() {
		return self::$query_vars;
	}

	public static function getQueryString() {
		return self::$query_string;
	}

	public static function getInit() {
		return self::$init;
	}

	public static function getVar($name, $filter = FILTER_DEFAULT, $options = null) {
		if (!array_key_exists($name, self::$payload)) {
			return null;
		}
		if (is_array(self::$payload[$name])) {
			return filter_var(self::$payload[$name], (int)$filter, FILTER_REQUIRE_ARRAY);
		} else {
			return filter_var(self::$payload[$name], (int)$filter, $options);
		}
	}

	public static function setVar($name, $value) {
		self::$payload[$name] = $value;
	}

	private static function check_quotes() {
		if (get_magic_quotes_gpc()) {
			//POST and GET should be checked when setting Controller::$payload
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

	protected static function checkQuery($query) {
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
				if (!array_key_exists('mode', self::$query_vars)) {
					self::$query_vars['mode'] = $mode;
				}
				if (substr($query, 0 - strlen($extension) - 1) == '.' . $extension) {
					$query = substr($query, 0, strlen($query) - strlen($extension) - 1);
				}
			}
		}
		return $query;
	}

	protected static function parseQuery($query) {
		if (!ConfigValue::get('AdminInstalled', false) && !in_array($query, array('admin/pre_install', 'admin/check_install', 'admin/install'))) {
			$query = 'admin/pre_install';
		}

		if (!empty($query)) {
			$terms = explode('/', $query);
		} else {
			$terms = array();
		}
		//We want to now what a parameter was, even if it's empty, so don't filter
		//$terms = array_filter($terms);

		self::$area   = count($terms) ? array_shift($terms) : ConfigValue::get('DefaultController', 'home');
		if (count($terms)) {
			self::$action = array_shift($terms);
		} else if (Component::isActive(class_name(self::$area))) {
			self::$action = ConfigValue::get('Default' . class_name(self::$area) . 'Action', ConfigValue::get('DefaultAction', 'index'));
		} else {
			self::$action = ConfigValue::get('DefaultAction', 'index');
		}

		self::$parameters = !empty($terms) ? $terms : array();
		if (Component::isActive(class_name(self::$area)) && method_exists(class_name(self::$area), 'checkParameters')) {
			self::$parameters = call_user_func(array(class_name(self::$area), 'checkParameters'), self::$parameters);
		}
		return self::$parameters;
	}

	/**
	 * Dont know if this will be usefull, might just use get_current_url()
	 */
	public static function getQuery() {
		return implode('/', array_filter(array_merge(array(self::$area, self::$action), self::$parameters)));
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

	/**
	 * Redirect to a specified location.
	 *
	 * If the location is omitted, go to the current URL. If $location == 'previous', go the previous URL for the current mode.
	 */
	public static function redirect($location = false) {
		if (self::$mode == self::MODE_REQUEST) {
			switch ($location) {
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
			if (!$location) {
				$location = $_SERVER['REQUEST_URI'];
			}

			//The following is only for on site redirects
			if (substr($location, 0, 7) != 'http://' || substr($location, 0, strlen(SITE_LINK)) == SITE_LINK) {
				//This should fix most redirects, but it may happen that location == '?debug=true&q=something/or/another' or something similiar
				if (ConfigValue::get('CleanURLs', false) && substr($location, 0, 3) == '?q=') {
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
					if (!empty($url['query'])) {
						parse_str($url['query'], $old_vars);
					} else {
						$old_vars = array();
					}
					//Allow the redirect to overwrite these vars
					$new_vars = array_merge($new_vars, $old_vars);

					$old_url = parse_url(get_current_url());
					$url['query'] = http_build_query($new_vars);
					$url = array_merge($old_url, $url);
					$location = build_url($url);
				}
			}

			try {
				if (self::$debug) {
					Backend::addSuccess('The script should now redirect to <a href="' . $location . '">here</a>');
				} else {
					//Redirect
					header('X-Redirector: Controller-' . __LINE__);
					header('Location: ' . $location);
					die('redirecting to <a href="' . $location . '">');
				}
			} catch (Exception $e) {
				Backend::addError('Could not redirect');
			}
		}
		return true;
	}

	/**
	 * Redirect to the current URL
	 */
	public static function refresh() {
		try {
			redirect(false, true);
			die('redirecting');
		} catch (Exception $e) {
		}
		return true;
	}

	public static function whoops($title = 'Whoops!', $extra = 'Looks like something went wrong...') {
		self::$whoopsed = true;


		if (is_array($extra)) {
			$code_hint = array_key_exists('code_hint', $extra) ? $extra['code_hint'] : false;
			$message   = array_key_exists('message', $extra)   ? $extra['message']   : false;
		} else if (is_numeric($extra)) {
			$code_hint = $extra;
			$message   = 'Looks like something went wrong...';
		} else {
			$code_hint = false;
			$message   = $extra;
		}

		if (Component::isActive('BackendError')) {
			BackendError::add($title, $message);
		}

		if (is_callable(array(self::$view, 'whoops'))) {
			call_user_func_array(array(self::$view, 'whoops'), array($title, $message, $code_hint));
		} else if (self::$view instanceof View) {
			self::$view->whoops($title, $message, $code_hint);
		}
		if (array_key_exists('debug', self::$query_vars)) {
			var_dump($title, $message);
			print_stacktrace();
		}
	}
}
