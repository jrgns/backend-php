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
	private static $vars        = array();
	private static $DB          = array();
	private static $config      = false;
	private static $config_file = false;
	private static $options     = array();

	protected static $error     = array();
	protected static $notice    = array();
	protected static $success   = array();
	protected static $info      = array();

	protected static $content   = array();
	protected static $scripts   = array();
	protected static $styles    = array();
	protected static $script_content = array();

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

		self::requireFile('classes', 'Controller');
		self::requireFile('utilities', 'BackendConfig');
		self::requireFile('classes', 'AreaCtl');
		self::requireFile('classes', 'TableCtl');
		self::requireFile('controllers', 'Component');
		self::requireFile('controllers', 'Value');
		self::requireFile('controllers', 'ConfigValue');
		self::requireFile('classes', 'View');
		self::requireFile('utilities', 'Request');
		self::requireFile('utilities', 'Parser');

		//TODO Maybe add a config value to decide if this should be included...
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

		$scheme = !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
		$url = SITE_DOMAIN . WEB_SUB_FOLDER;
		define('SITE_LINK', $scheme . $url);
		Backend::add('SITE_LINK', SITE_LINK);

		//Application Values
		$values = self::$config->getValue('application');
		if (is_array($values)) {
			foreach($values as $name => $value) {
				self::add($name, $value);
			}
		}

		//Init DBs
		$with_database = self::initDBs();
		//Check if we can connect to the default DB
		if ($with_database) {
			try {
				$def = self::getDB();
			} catch (Exception $e) {
				$with_database = false;
			}
		}

		define('BACKEND_WITH_DATABASE', $with_database);
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

	public static function getConfigFileLocation() {
		return defined('SITE_FOLDER') ? SITE_FOLDER . '/configs/configure.ini.php' : APP_FOLDER . '/configs/configure.ini.php';
	}

	private static function initConfigs() {
	    self::$config_file = self::getConfigFileLocation();
		self::$config = new BackendConfig(self::$config_file, SITE_STATE);
	}

	static function getConfig($name, $default = null) {
		//Check for a site specific setting first
		$result = self::$config->getValue($name);
		return is_null($result) ? $default : $result;
	}

	static function setConfig($name, $value) {
		return self::$config->setValue($name, $value);
	}

	public static function checkConfigFile() {
		$folder = dirname(self::$config_file);
		if (!file_exists($folder)) {
			if (@!mkdir($folder, 0755)) {
				if (SITE_STATE != 'production') {
					Backend::addError('Cannot create config file folder ' . $folder);
				} else {
					Backend::addError('Cannot create config file folder');
				}
				return false;
			}
		}
		if (file_exists(self::$config_file) && is_writable(self::$config_file)) {
			return true;
		}
		if (!is_writable($folder)) {
			if (SITE_STATE != 'production') {
				Backend::addError('Backend::Config file folder unwritable (' . $folder . ')');
			} else {
				Backend::addError('Backend::Config file folder unwritable');
			}
			return false;
		}
		return true;
	}

	private static function initDBs() {
		$result = false;
		//Get the DEFAULT db first,
		if ($db_settings = self::$config->getValue('database')) {
			if (is_array($db_settings)) {
				$db_settings['alias'] = 'default';
				if (empty($db_settings['name'])) {
					$db_settings['name'] = $db_settings['database'];
				}
				if (self::addDB($db_settings['name'], $db_settings)) {
					$result = true;
				} else {
					Backend::addError('Could not add Default Database');
				}
			}
		}
		//Get all the other DB's, if any
		$count = 1;
		while ($db_settings = self::$config->getValue('database_' . $count)) {
			if (self::addDB('database_' . $count, $db_settings)) {
				$result = true;
			} else {
				Backend::addError('Could not add Database: ' . $db_settings['alias']);
			}
			$count++;
		}
		return $result;
	}

	/**
	 * Add a DB definition to the Backend
	 *
	 * @param string The name of the DB
	 * @param array Options for the DB Connection. Can include
	 * + username, the username for the connection.
	 * + password, the password for the connection.
	 * + name, the name for the connection.
	 * + connection, An actual PDO connection.
	 * @returns boolean True if the connection succeeded.
	 */
	public static function addDB($name, array $options = array()) {
		if (!self::checkSelf()) {
			return false;
		}
		$dsn = array_key_exists('dsn', $options) ? $options['dsn'] : false;
		if (!$dsn) {
			$options['hostname'] = empty($options['hostname']) ? self::getConfig('database.hostname') : $options['hostname'];
			$options['database'] = empty($options['database']) ? self::getConfig('database.database') : $options['database'];
			$options['driver']   = empty($options['driver'])   ? self::getConfig('backend.db.default_driver', 'mysql') : $options['driver'];

			$dsn = array();
			if (!empty($options['database'])) {
				$dsn[] = 'dbname=' . $options['database'];
			}
			$dsn[] = 'host=' . (empty($options['host']) ? 'localhost' : $options['host']);
			$dsn = strtolower($options['driver']) . ':' . implode(';', $dsn);
		}
		$alias      = empty($options['alias'])      ? null : $options['alias'];
		$username   = empty($options['username'])   ? null : $options['username'];
		$password   = empty($options['password'])   ? null : $options['password'];
		$connection = empty($options['connection']) ? null : $options['connection'];

		if (array_key_exists($name, self::$DB)) {
			Backend::addNotice('Overwriting existing DB definition: ' . $name);
		}
		self::$DB[$name] = array(
			'database'   => $options['database'],
			'dsn'        => $dsn,
			'username'   => $username,
			'password'   => $password,
			'connection' => $connection
		);
		if (!is_null($alias) && $alias != $name) {
			if (array_key_exists($alias, self::$DB)) {
				Backend::addNotice('Overwriting existing DB definition: ' . $alias);
			}
			self::$DB[$alias] = self::$DB[$name];
		}
		return true;
	}

	/**
	 * Get a defined DB connection
	 *
	 * @param string The name of the DB connections. Defaults to 'default' :P.
	 * @returns PDO The PDO DB connection, or false.
	 */
	static function getDB($name = 'default', $full = false) {
		$definition = self::getDBDefinition($name);
		if ($definition && $definition['connection'] instanceof PDO) {
			return $full ? $definition : $definition['connection'];
		}
		return false;
	}

	static function getDBDefinition($name = 'default') {
		if (!self::checkSelf()) {
			return false;
		}

		if (!array_key_exists($name, self::$DB)) {
			return false;
		}

		if (empty(self::$DB[$name]['connection']) || !(self::$DB[$name]['connection'] instanceof PDO)) {
			try {
				//TODO This might be problematic if there shouldn't be a username/password?
				self::$DB[$name]['connection'] = new PDO(
					self::$DB[$name]['dsn'],
					self::$DB[$name]['username'],
					self::$DB[$name]['password']
				);
				if (self::getConfig('backend.dsn_header', false)) {
					header('X-DB-' . computerize($name) . '-DSN: ' . self::$DB[$name]['dsn']);
				}
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

	public static function getDBNames() {
		return array_keys(self::$DB);
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
			$log_to_file = array_key_exists('log_to_file', $options) ? $options['log_to_file'] : ConfigValue::get('LogToFile', false);

			if ($log_to_file) {
				if (is_string($log_to_file)) {
					@list($file, $log_what) = explode('|', $log_to_file);
				}
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

	static public function addInfo($content, $options = array()) {
		return self::addSomething('info', $content, $options);
	}

	static public function getInfo() {
		if (is_string(self::$info)) {
			self::$info = array(self::$info);
		}
		if (!is_array(self::$info)) {
			return array();
		}
		$counts = array_count_values(array_filter(self::$info));
		$result = array();
		foreach($counts as $value => $count) {
			if ($count > 1) {
				$value .= ' (' . $count . ')';
			}
			$result[] = $value;
		}
		return $result;
	}

	static public function setInfo(array $info = array()) {
		self::$info = $info;
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
			if (Component::isActive('BackendError')) {
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
			preg_match_all("/[\S]+\/classes\/Render.obj.php\([0-9]+\) : eval\(\)'d code/", $file, $vars, PREG_SET_ORDER);
			if (!empty($vars)) {
				$template_name = empty($context['be_template_name']) ? 'Unknown' : $context['be_template_name'];
				if (SITE_STATE != 'production') {
					Backend::addError('Error in template: ' . $template_name . ' on line ' . $line . ': ' . $string);
				} else {
					Backend::addError('Template Error');
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
		if (Controller::$debug) {
			$trace = array_reverse($exception->getTrace());
			echo '<ol>';
			foreach($trace as $item) {
				echo '<li>';
				if (isset($item['file'])) echo $item['file'];
				if (isset($item['line'])) echo '('.$item['line'].') called ';
				if (isset($item['class'])) echo '<strong>'.$item['class'].'</strong>->';
				if (isset($item['function'])) echo '<i>'.$item['function'].'</i>';
				echo '</li>';
			}
			echo '</ol>';
		}
		echo "Uncaught exception: " , $exception->getMessage(), ' in ', $exception->getFile(), ' line ', $exception->getLine(), "\n";
		if (Component::isActive('BackendError')) {
			BackendError::add($exception->getCode(), "Uncaught exception: " . $exception->getMessage(), $exception->getFile(), $exception->getLine());
		}
		//Execution ends here
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

	public static function requireFile($type, $name) {
		if (strpos($name, '.') === false) {
			$name .= '.obj.php';
		}
		$folders = array(APP_FOLDER, BACKEND_FOLDER);
		if (defined('SITE_FOLDER')) {
			array_unshift($folders, SITE_FOLDER);
		}
		foreach(array_unique($folders) as $folder) {
			if (file_exists($folder . '/' . $type . '/' . $name)) {
				include($folder . '/' . $type . '/' . $name);
				return true;
			}
		}
		return false;
	}

	public static function __autoload($classname) {
		$included = false;
		//Check if it's a core module first
		if (substr($classname, 0, 2) == 'BE') {
			if (self::loadCoreClass(substr($classname, 2))) {
				return true;
			}
		}

		//TODO eventually cache / determine by class name exactly where the file should be to improve performance
		$types = array('controllers', 'models', 'classes', 'views', 'utilities', 'widgets');
		foreach($types as $type) {
			if (self::requireFile($type, $classname . '.obj.php')) {
				$included = true;
				break;
			} else if ($type == 'controllers' && self::requireFile($type, $classname . '/index.php')) {
				$included = true;
				break;
			}
		}
		if ($included) {
			switch (true) {
			case !class_exists($classname):
				trigger_error('Could not load Class: ' . $classname, E_USER_ERROR);
				break;
			case $type == 'models':
				if (!(is_subclass_of($classname, 'DBObject'))) {
					trigger_error('Invalid class: ' . $classname . ' not a DBObject', E_USER_ERROR);
				}
				break;
			case $type == 'controllers':
				if (!(is_subclass_of($classname, 'AreaCtl'))) {
					trigger_error('Invalid class: ' . $classname . ' not a AreaController', E_USER_ERROR);
				}
				break;
			case $type == 'views':
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
