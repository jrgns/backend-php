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
	protected static $script_content = array();
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
		if (ob_get_level() === 0) {
			ob_start('ob_gzhandler');
		}

		self::$initialized = true;
		self::$options = $options;
		if (!defined('SITE_STATE')) {
			define('SITE_STATE', 'production');
		}
		if (defined('SITE_FOLDER') && !defined('APP_FOLDER')) {
			define('APP_FOLDER', SITE_FOLDER);
		}
		if (defined('APP_FOLDER') && !defined('SITE_FOLDER')) {
			define('SITE_FOLDER', APP_FOLDER);
		}

		if (file_exists(APP_FOLDER . '/constants.inc.php')) {
			include_once(APP_FOLDER . '/constants.inc.php');
		}
		if (defined('SITE_FOLDER') && file_exists(SITE_FOLDER . '/constants.inc.php')) {
			include_once(SITE_FOLDER . '/constants.inc.php');
		}
		if (file_exists(APP_FOLDER . '/functions.inc.php')) {
			include_once(APP_FOLDER . '/functions.inc.php');
		}
		if (defined('SITE_FOLDER') && file_exists(SITE_FOLDER . '/functions.inc.php')) {
			include_once(SITE_FOLDER . '/functions.inc.php');
		}
		if (file_exists(APP_FOLDER . '/modifiers.inc.php')) {
			include_once(APP_FOLDER . '/modifiers.inc.php');
		}
		if (defined('SITE_FOLDER') && file_exists(SITE_FOLDER . '/modifiers.inc.php')) {
			include_once(SITE_FOLDER . '/modifiers.inc.php');
		}
		require(BACKEND_FOLDER . '/constants.inc.php');
		require(BACKEND_FOLDER . '/functions.inc.php');
		require(BACKEND_FOLDER . '/modifiers.inc.php');

		include(BACKEND_FOLDER . '/libraries/Markdown/markdown.php');
		spl_autoload_register(array('Backend', '__autoload'));
		set_error_handler    (array('Backend', '__error_handler'));
		set_exception_handler(array('Backend', '__exception_handler'));
		register_shutdown_function(array('Controller', 'finish'));
		
		//Configs
		self::initConfigs();

		//Some constants
		$url = parse_url(get_current_url());
		$folder = !empty($url['path']) ? $url['path'] : '/';
		if (substr($folder, -1) != '/' && substr($folder, -1) != '\\') {
			$folder = dirname($folder);
		}
		if ($folder != '.') {
			if (substr($folder, strlen($folder) - 1) != '/') {
				$folder .= '/';
			}
			define('WEB_SUB_FOLDER', $folder);
		} else {
			define('WEB_SUB_FOLDER', '/');
		}
		Backend::add('WEB_SUB_FOLDER', WEB_SUB_FOLDER);
		
		$domain = !empty($url['host']) ? $url['host'] : 'localhost';
		define('SITE_DOMAIN', $domain);
		Backend::add('SITE_DOMAIN', SITE_DOMAIN);

		$url = SITE_DOMAIN . WEB_SUB_FOLDER;
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
		} else if (array_key_exists('debug', $_REQUEST)) {
			Backend::addError('No DBs');
		}
		
		//Dont use Value::get, because it might not be installed yet
		$installed = false;
		try {
			$db = self::getDB();
			if ($db instanceof PDO) {
				$stmt = $db->prepare('SELECT * FROM `values` WHERE `name` = \'admin_installed\'');
				if ($stmt) {
					if ($stmt->execute()) {
						$row = $stmt->fetch(PDO::FETCH_ASSOC);
						if ($row) {
							$installed = unserialize(base64_decode($row['value']));
						}
					} else if (array_key_exists('debug', $_REQUEST)) {
						Backend::addError('Could not determine if backend was installed');
					}
				}
			}
		} catch (Exception $e) {
			Backend::addError($e->getMessage());
		}
		define('BACKEND_INSTALLED', $installed);
	}
	
	public static function shutdown() {
		foreach(self::$DB as $connection) {
			if (is_object($connection['connection']) && $connection['connection'] instanceof PDO) {
				$connection['connection'] = null;
			}
		}
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
		if (array_key_exists('config_file', self::$options)) {
			$ini_file = self::$options['config_file'];
		} else {
			$ini_file = defined('SITE_FOLDER') ? SITE_FOLDER . '/configs/configure.ini' : APP_FOLDER . '/configs/configure.ini';
		}
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
				$options['host']     = empty($options['host'])     ? self::getConfig('backend.db.default_host')     : $options['host'];
				$options['username'] = empty($options['username']) ? self::getConfig('backend.db.default_username') : $options['username'];
				$options['password'] = empty($options['password']) ? self::getConfig('backend.db.default_password') : $options['password'];
				$options['database'] = empty($options['database']) ? self::getConfig('backend.db.default_database') : $options['database'];
				$options['driver']   = empty($options['driver'])   ? self::getConfig('backend.db.default_driver')   : $options['driver'];

				$dsn = array();
				$driver = array_key_exists('driver', $options)   ? $options['driver'] : 'mysql';
				if (!empty($options['database'])) {
					$dsn[] = 'dbname=' . $options['database'];
				}
				$dsn[] = 'host=' . (array_key_exists('host', $options) ? $options['host'] : 'localhost');
				$dsn = strtolower($driver) . ':' . implode(';', $dsn);
			}
			$username   = array_key_exists('username', $options) ? $options['username'] : '';
			$password   = array_key_exists('password', $options) ? $options['password'] : '';
			$alias      = empty($options['alias'])               ? $name                : $options['alias'];
			$connection = empty($options['connection'])          ? false                : $options['connection'];

			if (array_key_exists($name, self::$DB)) {
				Backend::addNotice('Overwriting existing DB definition: ' . $name);
			}
			self::$DB[$name] = array(
				'database' => $options['database'],
				'dsn'      => $dsn,
				'username' => $username,
				'password' => $password,
				'connection' => $connection
			);
			if ($alias != $name) {
				if (array_key_exists($alias, self::$DB)) {
					Backend::addNotice('Overwriting existing DB definition: ' . $alias);
				}
				self::$DB[$alias] = self::$DB[$name];
			}
			$toret = true;
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
		$definition = self::getDBDefinition($name);
		if ($definition) {
			return $definition['connection'];
		}
		return false;
	}

	static function getDBDefinition($name = false) {
		if (!self::checkSelf()) {
			return false;
		}
		//Single connection or no connection
		if (self::$DB instanceof PDO || empty(self::$DB)) {
			return self::$DB;
		}
		
		$name = $name ? $name : 'default';
		if (!array_key_exists($name, self::$DB)) {
			if ($name == 'default' && current(self::$DB) instanceof PDO) {
				return current(self::$DB);
			} else if (array_key_exists('default', self::$DB) && self::$DB['default']['database'] == $name) {
				return self::$DB['default'];
			}
			return false;
		}

		if (empty(self::$DB[$name]['connection']) || !(self::$DB[$name]['connection'] instanceof PDO)) {
			try {
				//This might be problematic if there shouldn't be a username/password?
				self::$DB[$name]['connection'] = new PDO(
					self::$DB[$name]['dsn'],
					self::$DB[$name]['username'],
					self::$DB[$name]['password']
				);
				header('X-DB-' . computerize($name) . '-DSN: ' . self::$DB[$name]['dsn']);
			} catch (Exception $e) {
				if (array_key_exists('debug', $_REQUEST)) {
					throw new ConnectToDBException($e->getMessage());
				} else {
					throw new ConnectToDBException('Could not connect to Database ' . $name);
				}
			}
		}
		return self::$DB[$name];
	}

	private static function addSomething($what, $string, $options = array()) {
		if (is_null($string)) {
			return false;
		}
		if (is_array($string) && empty($options['as_is'])) {
			$result = true;
			foreach($string as $one_string) {
				$result = self::addSomething($what, $one_string, $options) && $result;
			}
			return $result;
		} else {
			array_push(self::$$what, $string);
			//Log to file if necessary
			$log_to_file = array_key_exists('log_to_file', $options) ? $options['log_to_file'] : true;
			if (defined('BACKEND_INSTALLED') && BACKEND_INSTALLED) {
				$log_to_file = $log_to_file && Value::get('log_to_file', false);
			} else {
				//Only use this pre installation
				$log_to_file = $log_to_file && Backend::get('log_to_file');
			}

			if ($log_to_file) {
				@list($file, $log_what) = explode('|', $log_to_file);
				$file     = empty($file)     ? 'logfile_' . date('Ymd') . 'txt' : $file;
				$log_what = empty($log_what) ? array('success', 'notice', 'error') : explode(',', $log_what);
				if ((is_array($log_what) && in_array($what, $log_what)) || $log_what == '*') {
					if (is_writable(APP_FOLDER . '/logs/' . $file)) {
						if (!file_exists(APP_FOLDER . '/logs/')) {
							mkdir(APP_FOLDER . '/logs/', 0755);
						}
						$fp = fopen(APP_FOLDER . '/logs/' . $file, 'a');
						if ($fp) {
							$query = Controller::$area . '/' . Controller::$action . '/' . implode('/', Controller::$parameters);
							fwrite($fp, time() . "\t" . $query . "\t" . $what . "\t" . $string . PHP_EOL);
						}
					} else {
						array_push(self::$error, 'Log location is unwriteable');
					}
				}
			}
			return true;
		}
		return false;
	}
	
	static public function addContent($content, $options = array()) {
		$options['log_to_file'] = array_key_exists('log_to_file', $options) ? $options['log_to_file'] : true;
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
	
	static public function setScript(array $scripts = array()) {
		self::$scripts = $scripts;
	}
	
	static public function addScriptContent($content, $options = array()) {
		return self::addSomething('script_content', $content, $options);
	}
	
	static public function getScriptContent() {
		return self::$script_content;
	}

	static public function setScriptContent(array $content = array()) {
		self::$script_content = $content;
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
		if (is_string(self::$error)) {
			self::$error = array(self::$error);
		}
		if (!is_array(self::$error)) {
			return array();
		}
		$counts = array_count_values(array_filter(self::$error));
		$result = array();
		foreach($counts as $value => $count) {
			if ($count > 1) {
				$value .= ' (' . $count . ')';
			}
			$result[] = $value;
		}
		return $result;
	}
	
	static public function setError(array $errors = array()) {
		self::$error = $errors;
	}
	
	static public function addNotice($content, $options = array()) {
		return self::addSomething('notice', $content, $options);
	}
	
	static public function getNotice() {
		if (is_string(self::$notice)) {
			self::$notice = array(self::$notice);
		}
		if (!is_array(self::$notice)) {
			return array();
		}
		$counts = array_count_values(array_filter(self::$notice));
		$result = array();
		foreach($counts as $value => $count) {
			if ($count > 1) {
				$value .= ' (' . $count . ')';
			}
			$result[] = $value;
		}
		return $result;
	}

	static public function setNotice(array $notices = array()) {
		self::$notice = $notices;
	}
	
	static public function addSuccess($content, $options = array()) {
		return self::addSomething('success', $content, $options);
	}
	
	static public function getSuccess() {
		if (is_string(self::$success)) {
			self::$success = array(self::$success);
		}
		if (!is_array(self::$success)) {
			return array();
		}
		$counts = array_count_values(array_filter(self::$success));
		$result = array();
		foreach($counts as $value => $count) {
			if ($count > 1) {
				$value .= ' (' . $count . ')';
			}
			$result[] = $value;
		}
		return $result;
	}

	static public function setSuccess(array $successes = array()) {
		self::$success = $successes;
	}
	
	public static function __error_handler($number, $string, $file = false, $line = false, $context = false) {
		if (!class_exists('Component', false)) {
			self::__autoload('Component');
		}
		if (!class_exists('BackendError', false)) {
			self::__autoload('BackendError');
		}
		if (!class_exists('BackendErrorObj', false)) {
			self::__autoload('BackendErrorObj');
		}
		//Record Errors
		switch ($number) {
		case E_STRICT:
			break;
		case E_DEPRECATED:
			if (SITE_STATE == 'production') {
				break;
			} else {
				//Go through to the DEFAULT
			}
		default:
			if (defined('BACKEND_INSTALLED') && BACKEND_INSTALLED && Component::isActive('BackendError')) {
				BackendError::add($number, $string, $file, $line, $context);
			}
			break;
		}
		//Interpret or Bypass Errors
		switch ($number) {
		case E_WARNING:
			preg_match_all('/Missing argument ([0-9]+) for ([\S]+)::([^\(\)]+)\(\), called in ([\S]+) on line ([0-9]+)/', $string, $vars, PREG_SET_ORDER);
			if (!empty($vars)) {
				list($matches, $arg_num, $class, $method, $call_file, $call_line) = current($vars);
				if (SITE_STATE != 'production') {
					Backend::addError("Missing parameter $arg_num for $class::$method, called in $call_file line $call_line, defined in $file line $line");
				} else {
					Backend::addError('Invalid Parameters');
				}
				return true;
			}
			preg_match_all('/Missing argument ([0-9]+) for ([\S]+)::([^\(\)]+)\(\)/', $string, $vars, PREG_SET_ORDER);
			if (!empty($vars)) {
				list($matches, $arg_num, $class, $method) = current($vars);
				if (SITE_STATE != 'production') {
					Backend::addError("Missing parameter $arg_num for $class::$method, defined in $file line $line");
				} else {
					Backend::addError('Invalid Parameters');
				}
				return true;
			}
			break;
		case E_STRICT:
			if (SITE_STATE == 'production') {
				return true;
			}
			break;
		case E_DEPRECATED:
			if (SITE_STATE == 'production') {
				return true;
			}
			break;
		case E_RECOVERABLE_ERROR:
			if (SITE_STATE == 'production') {
				return true;
			}
			break;
		}
		return false;
	}
	
	public static function __exception_handler($exception) {
		echo "Uncaught exception: " , $exception->getMessage(), "\n";
	}
	
	private static function loadCoreClass($classname) {
		$code = false;
		if (file_exists(BACKEND_FOLDER . '/controllers/' . $classname . '.obj.php')) {
			$code = file_get_contents(BACKEND_FOLDER . '/controllers/' . $classname . '.obj.php');
		} else if (file_exists(BACKEND_FOLDER . '/models/' . $classname . '.obj.php')) {
			$code = file_get_contents(BACKEND_FOLDER . '/models/' . $classname . '.obj.php');
		} else if (file_exists(BACKEND_FOLDER . '/classes/' . $classname . '.obj.php')) {
			$code = file_get_contents(BACKEND_FOLDER . '/classes/' . $classname . '.obj.php');
		} else if (file_exists(BACKEND_FOLDER . '/views/' . $classname . '.obj.php')) {
			$code = file_get_contents(BACKEND_FOLDER . '/views/' . $classname . '.obj.php');
		} else if (file_exists(BACKEND_FOLDER . '/utilities/' . $classname . '.obj.php')) {
			$code = file_get_contents(BACKEND_FOLDER . '/utilities/' . $classname . '.obj.php');
		}
		if (empty($code)) {
			return false;
		}
		$code = preg_replace('/class ' . $classname . '/', 'class BE' . $classname, $code, 1, $count);
		if (!$count) {
			return false;
		}
		$code = preg_replace('/^<\?php\s+/', '', $code, 1);
		eval($code);
		if (!class_exists('BE' . $classname)) {
			return false;
		}
		return true;
	}

	static public function __autoload($classname) {
		$included = false;
		//Check if it's a core module first
		if (substr($classname, 0, 2) == 'BE') {
			if (self::loadCoreClass(substr($classname, 2))) {
				return true;
			}
		}
		//TODO eventually cache / determine by class name exactly where the file should be to improve performance
		if (defined('SITE_FOLDER')) {
			$folders = array(
				SITE_FOLDER . '/controllers/' => 'controller',
				APP_FOLDER . '/controllers/' => 'controller',
				BACKEND_FOLDER . '/controllers/' => 'controller',

				SITE_FOLDER . '/models/' => 'model',
				APP_FOLDER . '/models/' => 'model',
				BACKEND_FOLDER . '/models/' => 'model',

				SITE_FOLDER . '/classes/' => 'class',
				APP_FOLDER . '/classes/' => 'class',
				BACKEND_FOLDER . '/classes/' => 'class',

				SITE_FOLDER . '/views/' => 'view',
				APP_FOLDER . '/views/' => 'view',
				BACKEND_FOLDER . '/views/' => 'view',

				SITE_FOLDER . '/utilities/' => 'utility',
				APP_FOLDER . '/utilities/' => 'utility',
				BACKEND_FOLDER . '/utilities/' => 'utility',

				SITE_FOLDER . '/widgets/' => 'widget',
				APP_FOLDER . '/widgets/' => 'widget',
				BACKEND_FOLDER . '/widgets/' => 'widget',
			);
		} else {
			$folders = array(
				APP_FOLDER . '/controllers/' => 'controller',
				BACKEND_FOLDER . '/controllers/' => 'controller',

				APP_FOLDER . '/models/' => 'model',
				BACKEND_FOLDER . '/models/' => 'model',

				APP_FOLDER . '/classes/' => 'class',
				BACKEND_FOLDER . '/classes/' => 'class',

				APP_FOLDER . '/views/' => 'view',
				BACKEND_FOLDER . '/views/' => 'view',

				APP_FOLDER . '/utilities/' => 'utility',
				BACKEND_FOLDER . '/utilities/' => 'utility',

				APP_FOLDER . '/widgets/' => 'widget',
				BACKEND_FOLDER . '/widgets/' => 'widget',
			);
		}
		foreach($folders as $folder => $type) {
			if (file_exists($folder . $classname . '.obj.php')) {
				include($folder . $classname . '.obj.php');
				$included = true;
				break;
			} else if ($type == 'controller' && file_exists($folder . $classname . '/index.php')) {
				include($folder . $classname . '/index.php');
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
