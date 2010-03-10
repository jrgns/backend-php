<?php
/**
 * The file that defines the Backend class.
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
 * The Backend class
 */
class Backend {
	private static $initialized = false;
	private static $vars = array();
	private static $DB = array();
	private static $config = false;
	private static $options = array();

	protected static $error = array();
	protected static $notice = array();
	protected static $success = array();

	protected static $content = array();
	protected static $scripts = array();
	protected static $styles = array();	

	static private function checkSelf() {
		$toret = false;
		if (!self::$initialized) {
			self::init();
		}
		$toret = self::$initialized;
		return $toret;
	}
	
	static public function init(array $options = array()) {
		self::$initialized = true;
		self::$options = $options;
		if (!defined('SITE_STATE')) {
			define('SITE_STATE', 'production');
		}
		require(BACKEND_FOLDER . '/constants.inc.php');
		require(BACKEND_FOLDER . '/functions.inc.php');
		require(BACKEND_FOLDER . '/modifiers.inc.php');
		include(BACKEND_FOLDER . '/libraries/Markdown/markdown.php');
		spl_autoload_register(array('Backend', '__autoload'));
		set_error_handler    (array('Backend', '__error_handler'));
		set_exception_handler(array('Backend', '__exception_handler'));		
		
		//Configs
		self::initConfigs();

		//Some constants
		$url = parse_url(get_current_url());
		$folder = !empty($url['path']) ? dirname($url['path']) : '/';
		if ($folder != '.') {
			if (substr($folder, strlen($folder) - 1) != '/') {
				$folder .= '/';
			}
			define('SITE_SUB_FOLDER', $folder);
		} else {
			define('SITE_SUB_FOLDER', '/');
		}
		Backend::add('SITE_SUB_FOLDER', SITE_SUB_FOLDER);
		
		$domain = !empty($url['host']) ? $url['host'] : 'localhost';
		define('SITE_DOMAIN', $domain);
		Backend::add('SITE_DOMAIN', SITE_DOMAIN);

		$url = SITE_DOMAIN . SITE_SUB_FOLDER;
		define('SITE_LINK', 'http://' . $url);
		if (Backend::getConfig('backend.application.use_ssl', false)) {
			define('S_SITE_LINK', 'https://' . $url);
		} else {
			define('S_SITE_LINK', 'http://' . $url);
		}
		Backend::add('SITE_LINK', SITE_LINK);
		Backend::add('S_SITE_LINK', S_SITE_LINK);
		
		//Application Values
		$values = self::$config->getValue('application');
		if ($values) {
			foreach($values as $name => $value) {
				self::add($name, $value);
			}
		}

		//Backend Values
		$values = self::$config->getValue('backend.values');
		if ($values) {
			foreach($values as $name => $value) {
				self::add($name, $value);
			}
		}
		
		//DBs
		$dbs = self::$config->getValue('backend.dbs');
		if ($dbs) {
			self::initDBs($dbs);
		}
		
		define('BACKEND_INSTALLED', Value::get('admin_installed', false));
	}
	
	static public function add($name, $value) {
		$toret = false;
		if (self::checkSelf()) {
			self::$vars[$name] = $value;
			$toret = true;
		}
		return $toret;
	}
	
	static function get($name, $default = null) {
		$toret = $default;
		if (self::checkSelf()) {
			if (array_key_exists($name, self::$vars)) {
				$toret = self::$vars[$name];
			}
		}
		return $toret;
	}
	
	static function clear($name) {
		$toret = false;
		if (self::checkSelf()) {
			if (array_key_exists($name, self::$vars)) {
				unset(self::$vars[$name]);
				$toret = true;
			}
		}
		return $toret;
	}
	static function getAll() {
		$toret = false;
		if (self::checkSelf()) {
			$toret = self::$vars;
		}
		return $toret;
	}
	
	static private function initConfigs() {
		$ini_file = array_key_exists('config_file', self::$options) ? self::$options['config_file'] : BACKEND_FOLDER . '/configs/configure.ini';
		self::$config = new BackendConfig($ini_file, SITE_STATE);
	}
	
	static function getConfig($name, $default = null) {
		$toret = self::$config->getValue($name);
		return is_null($toret) ? $default : $toret;
	}
	
	static private function initDBs($dbs) {
		if (is_array($dbs)) {
			foreach($dbs as $name => $db) {
				self::addDB($name, $db);
			}
		}
	}
	/**
	 * Add a DB definition to the Backend
	 *
	 * @param string A PDO DSN for the DB.
	 * @param array Options for the DB Connection. Can include 
	 * + username, the username for the connection.
	 * + password, the password for the connection.
	 * + name, the name for the connection, defaults to 'default'.
	 * + connection, An actual PDO connection.
	 * @returns boolean True if the connection succeeded.
	 */
	static function addDB($name, array $options = array()) {
		$toret = false;
		if (self::checkSelf()) {
			$dsn = array_key_exists('dsn', $options) ? $options['dsn'] : false;
			if (!$dsn) {
				$dsn = array();
				$driver = array_key_exists('driver', $options)   ? $options['driver'] : 'mysql';
				if (!empty($options['database'])) {
					$dsn[] = 'dbname=' . $options['database'];
				}
				$dsn[] = 'host=' . (array_key_exists('host', $options) ? $options['host'] : 'localhost');
				$dsn = strtolower($driver) . ':' . implode(';', $dsn);
			}
			$alias    = !empty($options['alias'])              ? $options['alias']    : $name;
			$username = array_key_exists('username', $options) ? $options['username'] : '';
			$password = array_key_exists('password', $options) ? $options['password'] : '';
			if (!empty($options['connection'])) {
				$connection = $options['connection'];
			} else {
				try {
					//This might be problematic if there shouldn't be a username/password?
					$connection = new PDO($dsn, $username, $password);
				} catch (Exception $e) {
					if (Controller::$debug) {
						throw new ConnectToDBException($e->getMessage());
					} else {
						throw new ConnectToDBException('Could not connect to Database ' . $name);
					}
				}
			}
			if (!empty($connection) && $connection instanceof PDO) {
				if (array_key_exists($alias, self::$DB)) {
					Backend::addNotice('Overwriting existing DB definition: ' . $alias);
				}
				self::$DB[$name] = array('dsn' => $dsn, 'username' => $username, 'password' => $password, 'connection' => $connection);
				if ($alias != $name) {
					self::$DB[$alias] = array('dsn' => $dsn, 'username' => $username, 'password' => $password, 'connection' => $connection);
				}
				$toret = true;
			} else {
				if (Controller::$debug) {
					throw new ConnectToDBException($e->getMessage());
				} else {
					throw new ConnectToDBException('Could not connect to Database ' . $alias);
				}
			}
		}
		return $toret;
	}
	
	/**
	 * Get a defined DB connection
	 *
	 * @param string The name of the DB connections. Defaults to 'default' :P.
	 * @returns PDO The PDO DB connection, or false.
	 */
	static function getDB($name = false) {
		$toret = false;
		if (self::checkSelf()) {
			$name = $name ? $name : 'default';
			if ($name && array_key_exists($name, self::$DB) && array_key_exists('connection', self::$DB[$name]) && self::$DB[$name]['connection'] instanceof PDO) {
				$toret = self::$DB[$name]['connection'];
			} else if (array_key_exists('default', self::$DB) && array_key_exists('connection', self::$DB['default']) && self::$DB['default']['connection'] instanceof PDO) {
				$toret = self::$DB['default']['connection'];
			} else if (current(self::$DB) instanceof PDO) {
				$toret = current(self::$DB);
			} else if (self::$DB instanceof PDO) {
				$toret = self::$DB;
			}
		}
		return $toret;
	}

	private static function addSomething($what, $string, $options = array()) {
		$toret = false;
		if (!is_null($string)) {
			if (is_array($string)) {
				$toret = true;
				foreach($string as $one_string) {
					$toret = self::addSomething($what, $one_string, $options) && $toret;
				}
			} else {
				$toret = true;
				array_push(self::$$what, $string);
				//Log to file if necessary
				$log_to_file = defined('BACKEND_INSTALLED') && BACKEND_INSTALLED ? Value::get('log_to_file', false) : false;
				if ($log_to_file && in_array($what, array('success', 'notice', 'error'))) {
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
			}
		}
		return $toret;
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

	public static function __error_handler($number, $string, $file = false, $line = false, $context = false) {
		if (!class_exists('Component', false)) {
			require(BACKEND_FOLDER . '/controllers/Component.obj.php');
		}
		if (!class_exists('BackendError', false)) {
			require(BACKEND_FOLDER . '/controllers/BackendError.obj.php');
		}
		if (!class_exists('BackendErrorObj', false)) {
			require_once(BACKEND_FOLDER . '/models/BackendErrorObj.obj.php');
		}
		if (Component::isActive('BackendError')) {
			BackendError::add($number, $string, $file, $line, $context);
		}
		switch ($number) {
		case 2:
			preg_match_all('/Missing argument ([0-9]+) for ([\S]+)::([^\(\)]+)\(\), called in ([\S]+) on line ([0-9]+)/', $string, $vars, PREG_SET_ORDER);
			if (!empty($vars)) {
				list($matches, $arg_num, $class, $method, $call_file, $call_line) = current($vars);
				Backend::addError("Missing parameter $arg_num for $class::$method, called in $call_file line $call_line, defined in $file line $line");
				return true;
			}
			preg_match_all('/Missing argument ([0-9]+) for ([\S]+)::([^\(\)]+)\(\)/', $string, $vars, PREG_SET_ORDER);
			if (!empty($vars)) {
				list($matches, $arg_num, $class, $method) = current($vars);
				Backend::addError("Missing parameter $arg_num for $class::$method, defined in $file line $line");
				return true;
			}
			break;
		}
		return false;
	}
	
	public static function __exception_handler($exception) {
		echo "Uncaught exception: " , $exception->getMessage(), "\n";
	}

	static public function __autoload($classname) {
		$included = false;
		//TODO eventually cache / determine by class name exactly where the file should be to improve performance
		$folders = array(
			APP_FOLDER . '/models/' => 'model',
			BACKEND_FOLDER . '/models/' => 'model',
			APP_FOLDER . '/controllers/' => 'controller',
			BACKEND_FOLDER . '/controllers/' => 'controller',
			APP_FOLDER . '/classes/' => 'class',
			BACKEND_FOLDER . '/classes/' => 'class',
			APP_FOLDER . '/views/' => 'view',
			BACKEND_FOLDER . '/views/' => 'view',
			APP_FOLDER . '/utilities/' => 'utility',
			BACKEND_FOLDER . '/utilities/' => 'utility',
		);
		foreach($folders as $folder => $type) {
			$file = $folder . $classname . '.obj.php';
			if (file_exists($file)) {
				include($file);
				$included = true;
				break;
			}
		}
		if ($included) {
			switch (true) {
			case !class_exists($classname):
				trigger_error('Could not load Class: ' . $classname, E_USER_ERROR);
				break;
			case $type == 'model':
				if (!(is_subclass_of($classname, 'DBObject'))) {
					trigger_error('Invalid class: ' . $classname . ' not a DBObject', E_USER_ERROR);
				}
				break;
			case $type == 'controller':
				if (!(is_subclass_of($classname, 'AreaCtl'))) {
					trigger_error('Invalid class: ' . $classname . ' not a AreaController', E_USER_ERROR);
				}
				break;
			case $type == 'view':
				if (!(is_subclass_of($classname, 'View'))) {
					trigger_error('Invalid class: ' . $classname . ' not a View', E_USER_ERROR);
				}
				break;
			default:
				if (
					is_subclass_of($classname, 'DBObject')
					|| (is_subclass_of($classname, 'AreaCtl') && !in_array($classname, array('TableCtl', 'WorkflowCtl')))
				) {
					trigger_error('Invalid class: ' . $classname . ' (' . $type . ') not under correct file structure', E_USER_ERROR);
				}
				break;
			}
		}
	}
}
