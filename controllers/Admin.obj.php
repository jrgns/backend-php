<?php
/**
 * The class file for Admin
 *
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 * @copyright Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * @license http://www.eclipse.org/legal/epl-v10.html
 * @package ControllerFiles
 */
 
/**
 * This is the controller for the Admin area
 * @package Controllers
 */
class Admin extends AreaCtl {
	const ERR_DB_INSUFFICIENT_INFO = 1;
	const ERR_DB_CANT_CONNECT      = 2;
	public static $error_msgs = array(
		self::ERR_DB_INSUFFICIENT_INFO => 'Insufficient information supplied.',
		self::ERR_DB_CANT_CONNECT      => 'Could not connect to the DB. Please check the information supplied and that the DB exists.',
	);

	/**
	 * Simple page to notify the user that the app has not been installed yet
	 */
	public function html_pre_install($result) {
		Backend::add('Sub Title', 'This application has not been installed yet.');
		Backend::addContent(Render::renderFile('admin.pre_install.tpl.php'));
		return $result;
	}

	/**
	 *	Do some checks before the install commences
	 *
	 * Catch both get and post
	 */
	public function action_check_install() {
		$components = Component::getActive();
		if (!$components) {
			Backend::addError('Could not get components to check');
			return false;
		}

		$can_install = true;
		$components = array_flatten($components, null, 'name');
		foreach($components as $component) {
			if (class_exists($component, true) && method_exists($component, 'install_check')) {
				if (!call_user_func_array(array($component, 'install_check'), array())) {
					Backend::addNotice('Error on checking install for ' . $component);
					$can_install = false;
				}
			}
		}

		return $can_install;
	}
	
	public function html_check_install($result) {
		Backend::add('Sub Title', 'Pre Installation Check');
		Backend::addNotice('This application has not been installed yet');
		Backend::addContent(Render::renderFile('admin.check_install.tpl.php', array('can_install' => $result)));
		return $result;
	}

	/**
	* Do the initial install for the different components.
	*/
	public function post_install() {
		$installed = ConfigValue::get('AdminInstalled', false);
		if ($installed) {
			return false;
		}

		return self::installComponents();
	}
	
	public function html_install($result) {
		if (is_post() && $result) {
			Backend::addSuccess('Backend Install Successful');
			ConfigValue::set('AdminInstalled', date('Y-m-d H:i:s'));
			if (Controller::getVar('add_database')) {
				Controller::redirect('?q=admin/install_db');
			} else {
				Controller::redirect('?q=home/index');
			}
		}
		Backend::add('Sub Title', 'Install Backend Application');
		Backend::addContent('<p class="large loud">Something went wrong with the installation</p>');
	}
	
	/**
	 * Get and save the database settings
	 */
	public function post_install_db() {
		//Get the values
		$username = Controller::getVar('username');
		$password = Controller::getVar('password');
		$database = Controller::getVar('database');
		$hostname = Controller::getVar('hostname');
		if (empty($username) || empty($password) || empty($database)
				|| empty($hostname)) {
			return self::ERR_DB_INSUFFICIENT_INFO;
		}
		
		//Connect to the DB
		$dsn = array();
		$driver = 'mysql';
		$dsn[] = 'dbname=' . $database;
		$dsn[] = 'host=' . $hostname;
		$dsn = strtolower($driver) . ':' . implode(';', $dsn);
		try {
			$conn = new PDO(
						$dsn,
						$username,
						$password
					);
		} catch (Exception $e) {
			return self::ERR_DB_CANT_CONNECT;
		}
		if (!($conn instanceof PDO)) {
			return self::ERR_DB_CANT_CONNECT;
		}
		//Set the values
		Backend::setConfig('database.alias', 'default');
		Backend::setConfig('database.database', $database);
		Backend::setConfig('database.username', $username);
		Backend::setConfig('database.password', $password);
		Backend::setConfig('database.hostname', $hostname);
		
		//Add the DB settings to the Backend
		Backend::addDB('default', array(
			'alias'    => 'default',
			'database' => $database,
			'username' => $username,
			'password' => $password,
			'hostname' => $hostname,
		));
		
		//Reinstall the components
		return self::installComponents(true);
	}
	
	public function html_install_db($result) {
		if ($result === true) {
			ConfigValue::set('DatabaseInstalled', date('Y-m-d H:i:s'));
			Backend::addSuccess('Set up DB Connection');
			//Setup the super user
			Controller::redirect('?q=backend_user/super_signup');
		} else {
			$vars = array(
				'username' => Controller::getVar('username'),
				'password' => Controller::getVar('password'),
				'hostname' => Controller::getVar('hostname'),
				'database' => Controller::getVar('database'),
			);
			Backend::addContent(Render::renderFile('admin.install_db.tpl.php', $vars));
			Backend::addError(self::getError($result));
		}
	}

	/**
	 * Run this request daily to run daily maintenance scripts
	 */
	function get_daily(array $options = array()) {
		$components = Component::getActive();
		$result = true;
		foreach($components as $component) {
			if (is_callable(array($component['name'], 'daily'))) {
				$object = new $component['name']();
				call_user_func_array(array($object, 'daily'), $options) && $result;
			}
		}
		return $result;
	}
	
	public function json_daily($result) {
		if ($result) {
			//Exit without outputting anything. To be used in crons
			die;
		}
		return $result;
	}

	/**
	 * Run this request weekly to run weekly maintenance scripts
	 */
	function get_weekly(array $options = array()) {
		$components = Component::getActive();
		$result = true;
		foreach($components as $component) {
			if (is_callable(array($component['name'], 'weekly'))) {
				$object = new $component['name']();
				$result = call_user_func_array(array($object, 'weekly'), $options) && $result;
			}
		}
		return $result;
	}
	
	public function json_weekly($result) {
		if ($result) {
			//Exit without outputting anything. To be used in crons
			die;
		}
		return $result;
	}
	
		function html_index($result) {
			Backend::add('Sub Title', 'Manage Application');
			Backend::add('result', $result);

			$admin_links = array();
			$components = Component::getActive();
			foreach($components as $component) {
				if (method_exists($component['name'], 'admin_links')) {
					$admin_links[$component['name']] = call_user_func(array($component['name'], 'admin_links'));
				}
			}
			Backend::add('admin_links', $admin_links);
			Backend::addContent(Render::renderFile('admin.index.tpl.php'));
		}

	public static function hook_post_display($data, $controller) {
		$user = BackendUser::check();
		if ($user && count(array_intersect(array('superadmin', 'admin'), $user->roles))) {
			Links::add('Manage Application', '?q=admin', 'secondary');
		}
		return $data;
	}
	
	private static function installComponents($with_db = false) {
		$components = Component::getCoreComponents($with_db);
		if (!$components) {
			Backend::addError('Could not get components to pre install');
			return false;
		}

		//Save original LogToFile setting
		$original = ConfigValue::get('LogToFile', false);
		$install_log_file = 'install_log_' . date('Ymd_His') . '.txt';
		ConfigValue::set('LogToFile', $install_log_file);

		//Pre Install components
		Backend::addNotice(PHP_EOL . PHP_EOL . 'Installation started at ' . date('Y-m-d H:i:s'));
		$components = array_flatten($components, null, 'name');
		foreach($components as $component) {
			if (class_exists($component, true) && method_exists($component, 'pre_install')) {
				Backend::addNotice('Pre Installing ' . $component);
				if (!call_user_func_array(array($component, 'pre_install'), array())) {
					Backend::addError('Error on pre install for ' . $component);
					return false;
				}
			}
		}
		
		//Install Components
		foreach($components as $component) {
			if (class_exists($component, true) && method_exists($component, 'install')) {
				Backend::addNotice('Installing ' . $component);
				if (!call_user_func_array(array($component, 'install'), array())) {
					Backend::addError('Error on installing ' . $component);
					return false;
				}
			}
		}

		//Restore Original
		ConfigValue::set('LogToFile', $original);
		return true;
	}
	
	public static function install(array $options = array()) {
		$result = parent::install($options);
		if (!Backend::getDB('default')) {
			return $result;
		}

		$result = Hook::add('display', 'post', __CLASS__, array('global' => true, 'mode' => 'html')) && $result;

		$result = Permission::add('nobody', 'daily', 'admin') && $result;
		$result = Permission::add('anonymous', 'daily', 'admin') && $result;
		$result = Permission::add('authenticated', 'daily', 'admin') && $result;
		$result = Permission::add('nobody', 'weekly', 'admin') && $result;
		$result = Permission::add('anonymous', 'weekly', 'admin') && $result;
		$result = Permission::add('authenticated', 'weekly', 'admin') && $result;

		return $result;
	}
}

