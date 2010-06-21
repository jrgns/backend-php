<?php
/**
 * The file that defines the AreaCtl class.
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
 * Default class to handle Area specific functions
 */
class AreaCtl {
	static private $error_msgs = array();

	/**
	 * The standard action for an Area
	 */
	public final function action() {
		$toret = null;
		if (array_key_exists('msg', $_REQUEST)) {
			Backend::addError(self::getError($_REQUEST['msg']));
		}
		$method = 'action_' . Controller::$action;
		if (Controller::$debug) {
			var_dump('Checking Method ' . $method . ' for ' . get_class($this));
		}
		if (!$this->checkPermissions() && Value::get('CheckHTTPAuth', false)) {
			//If the client is denied, challenge them for HTTP Digest Auth credentials
			$auth = BackendAccount::getHTTPAuth();
			$auth->challenge();
		}
		if ($this->checkPermissions()) {
			if (method_exists($this, $method)) {
				$toret = call_user_func_array(array($this, $method), Controller::$parameters);
			}
		} else {
			Controller::whoops(array('title' => 'Permission Denied', 'message' => 'You do not have permission to ' . Controller::$action . ' ' . get_class($this)));
			$toret = false;
		}
		return $toret;
	}
	
	/**
	 * Return a area specific error
	 *
	 * Override this function if you want to customize the errors returned for an area.
	 */
	public static function getError($num, $class_name = false) {
		$msg = 'Unknown Error Message';
		$class_name = $class_name ? $class_name : class_name(Controller::$area);
		if (class_exists($class_name, true)) {
			$vars = eval('return ' . $class_name . '::$error_msgs;');
			$msg = empty($vars[$num]) ? 'Unknown Error Message for ' . $class_name : $vars[$num];
		}
		return $msg;
	}
	
	/**
	 * Return Tab Links for this area
	 *
	 * Override this function if you want to customize the Tab Links for an area.
	 */
	protected function getTabLinks($action) {
		$links = array();
		return $links;
	}
	
	/**
	 * Check permissions for this area
	 *
	 * Override this function if you want to customize the permissions for an area. BUT preferably use the DB...
	 */
	public function checkPermissions(array $options = array()) {
		$toret = true;
		$action = !empty($options['action']) ? $options['action'] : (
			!empty(Controller::$action) ? Controller::check_reverse_map('action', Controller::$action) : '*'
		);
		$subject = !empty($options['subject']) ? $options['subject'] : (
			!empty(Controller::$area) ? Controller::check_reverse_map('area', Controller::$area) : '*'
		);
		if (count(Controller::$parameters) === 1) {
			$subject_id = !empty($options['subject_id']) ? $options['subject_id'] : (
				!empty(Controller::$parameters[0]) ? Controller::check_reverse_map('id', Controller::$parameters[0]) : 0
			);
		} else {
			$subject_id = 0;
		}
		
		if (Value::get('admin_installed', false)) {
			$roles = GateKeeper::permittedRoles($action, $subject, $subject_id);
			if (!empty($_SESSION['user'])) {
				if (Controller::$debug) {
					if (is_object($_SESSION['user']) && property_exists($_SESSION['user'], 'roles')) {
						Backend::addNotice('Current user roles: ' . serialize($_SESSION['user']->roles));
					} else {
						Backend::addError('No user roles');
						$_SESSION['user']->roles = array();
					}
				}
				if ($roles) {
					$intersect = array_intersect($_SESSION['user']->roles, $roles);
					$toret = count($intersect) ? true : false;
				} else {
					$toret = $_SESSION['user']->roles;
				}
			} else if (!in_array('anonymous', $roles)) {
				$toret = false;
			}
		} else if (!($subject == 'admin' && in_array($action, array('install', 'pre_install', 'post_install')))) {
			$toret = false;
		}
		return $toret;
	}

	public static function checkParameters($parameters) {
		return $parameters;
	}
	
	public static function install(array $options = array()) {
		$toret = false;
		$class = get_called_class();
		if ($class && class_exists($class, true)) {
			$toret = true;
			$methods = get_class_methods($class);
			$methods = array_filter($methods, create_function('$var', 'return substr($var, 0, strlen(\'action_\')) == \'action_\';'));
			$methods = array_map(create_function('$var', 'return substr($var, strlen(\'action_\'));'), $methods);
			foreach($methods as $action) {
				Permission::add('nobody', $action, class_for_url($class));
			}
		}
		return $toret;
	}
}
