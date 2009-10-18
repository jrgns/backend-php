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
	static private $initialized = false;
	static private $vars = array();
	static private $DB = array();
	static private $config = false;
	static private $options = array();

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
		require(BACKEND_FOLDER . '/functions.inc.php');
		require(BACKEND_FOLDER . '/modifiers.inc.php');
		spl_autoload_register(array('Backend', '__autoload'));
		
		//Some constants
		$url = parse_url(get_current_url());
		$url = $url['host'] . dirname($url['path']);
		if (substr($url, strlen($url) - 1) != '/') {
			$url .= '/';
		}

		//Configs
		self::initConfigs();

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
	}
	
	static private function initConfigs() {
		$ini_file = array_key_exists('config_file', self::$options) ? self::$options['config_file'] : BACKEND_FOLDER . '/configs/configure.ini';
		self::$config = new Config($ini_file, SITE_STATE);
	}
		
	static private function initDBs($dbs) {
		if (is_array($dbs)) {
			foreach($dbs as $name => $db) {
				self::addDB($name, $db);
			}
		}
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
					|| (is_subclass_of($classname, 'AreaCtl') && !in_array($classname, array('TableCtl')))
				) {
					trigger_error('Invalid class: ' . $classname . ' not under correct file structure', E_USER_ERROR);
				}
				break;
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
	
	static function getConfig($name, $default = null) {
		$toret = self::$config->getValue($name);
		return is_null($toret) ? $default : $toret;
	}
	
	static function getAll() {
		$toret = false;
		if (self::checkSelf()) {
			$toret = self::$vars;
		}
		return $toret;
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
				$dsn[] = array_key_exists('host', $options) ? $options['host'] : 'localhost';
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
						Controller::addError($e->getMessage());
					} else {
						Controller::addError('Could not connect to Database ' . $name);
					}
				}
			}
			if (!empty($connection) && $connection instanceof PDO) {
				if (array_key_exists($alias, self::$DB)) {
					Controller::addWarning('Overwriting existing DB definition: ' . $alias);
				}
				self::$DB[$name] = array('dsn' => $dsn, 'username' => $username, 'password' => $password, 'connection' => $connection);
				if ($alias != $name) {
					self::$DB[$alias] = array('dsn' => $dsn, 'username' => $username, 'password' => $password, 'connection' => $connection);
				}
				$toret = true;
			} else {
				Controller::addError('Could not connect to Database ' . $alias);
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
}
