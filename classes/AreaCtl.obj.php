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
	public static $error_msgs = array();

	/**
	 * The standard action for an Area
	 */
	public final function action() {
		$toret = null;
		$error_number = Controller::getVar('err');
		if (!empty($error_number)) {
			Backend::addError(self::getError($error_number));
		}

		if (Controller::$debug) {
			Backend::addNotice('Checking Method ' . Controller::$action . ' for ' . get_class($this));
		}

		$request_method = strtolower(Controller::getMethod()) . '_' . Controller::$action;
		$action_method  = 'action_' . Controller::$action;
		$view_method    = Controller::$view->mode . '_' . Controller::$action;

		//Determine / check method
		$method = false;
		if (method_exists($this, $request_method)) {
			$method = $request_method;
		} else if (method_exists($this, $action_method)) {
			$method = $action_method;
		} else if (method_exists($this, $view_method)) {
			$method = true;
		}

		if (!$method) {
			Controller::whoops('Unknown Method', array('message' => 'Method ' . Controller::$area . '::' . Controller::$action . ' does not exist'));
			return null;
		}

		//Check permissions on existing method

		if (Controller::getCheckPermissions() && !$this->checkPermissions()) {
			//TODO Add a permission denied hook to give the controller a chance to handle the permission denied
			Controller::whoops('Permission Denied', array('message' => 'You do not have permission to ' . Controller::$action . ' ' . get_class($this)));
			return null;
		}

		if ($method === true) {
			//View method, return null;
			return null;
		}

		if (Controller::$debug) {
			Backend::addNotice('Running ' . get_class($this) . '::' . $method);
		}
		return call_user_func_array(array($this, $method), Controller::$parameters);
	}

	/**
	 * Return a area specific error
	 *
	 * Override this function if you want to customize the errors returned for an area.
	 */
	public static function getError($num, $class_name = false) {
		$msg = 'Unknown Error Message';
		$class_name = $class_name ? $class_name : get_called_class();
		if (class_exists($class_name, true)) {
			$vars = eval('return ' . $class_name . '::$error_msgs;');
			$msg = empty($vars[$num]) ? 'Unknown Error Message for ' . $class_name . ' (' . $num . ')' : $vars[$num];
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
	 * Return the human friendly name for the controller
	 */
	public function getHumanName() {
		return get_class($this);
	}

	/**
	 * Check permissions for this area
	 *
	 * Override this function if you want to customize the permissions for an area. BUT preferably use the DB...
	 */
	public function checkPermissions(array $options = array()) {
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

		if (ConfigValue::get('AdminInstalled', false)) {
			return Permission::check($action, $subject, $subject_id);
		} else if (!($subject == 'admin' && in_array($action, array('pre_install', 'check_install', 'install')))) {
			return false;
		}
		return true;
	}

	public static function checkParameters($parameters) {
		return $parameters;
	}

	public static function define_install() {
		return array(
			'description' => 'Install the component',
			'return'      => array(
				'description' => 'Whether or not the installation was succesful.',
				'type'        => 'boolean',
			),
		);
	}

	public function action_install() {
		return call_user_func(array(get_class($this), 'install'));
	}

	public static function install(array $options = array()) {
		if (!Backend::getDB('default')) {
			return true;
		}
		$class = get_called_class();
		if (!$class || !class_exists($class, true)) {
			return false;
		}

		//Purge permissions first
		$query = new DeleteQuery('Permission');
		$query
			->filter('`subject` = :subject')
			->filter('`system` = 0');
		$query->execute(array(':subject' => class_for_url($class)));
		$methods = get_class_methods($class);
		$methods = array_filter($methods, create_function('$var', '$temp = explode(\'_\', $var, 2); return count($temp) == 2 && in_array(strtolower($temp[0]), array(\'action\', \'get\', \'post\', \'put\', \'delete\'));'));
		$methods = array_map(create_function('$var', 'return preg_replace(\'/^(action|get|post|put|delete)_/\', \'\', $var);'), $methods);

		$result = true;
		foreach($methods as $action) {
			$result = Permission::add('nobody', $action, class_for_url($class)) && $result;
		}
		return $result;
	}

	public function get_home() {
		return $this->getHomeMethods();
	}

	public function html_home($methods) {
		Backend::addContent(Render::file('std_home.tpl.php', array('methods' => $methods)));
	}

	public function getHomeMethods() {
		$class = get_called_class();
		if (!$class || !class_exists($class, true)) {
			return false;
		}
		$methods = get_class_methods($class);
		$methods = array_filter($methods, create_function('$var', '$temp = explode(\'_\', $var, 2); return count($temp) == 2 && in_array(strtolower($temp[0]), array(\'action\', \'get\', \'post\', \'put\', \'delete\'));'));
		$methods = array_map(create_function('$var', 'return preg_replace(\'/^(action|get|post|put|delete)_/\', \'\', $var);'), $methods);
		if ($home_key = array_search('home', $methods)) {
			unset($methods[$home_key]);
		}
		$methods = array_filter($methods, create_function('$var', 'return Permission::check($var, "' . $class . '");'));
		$methods = array_unique($methods);
		asort($methods);
		return $methods;
	}
}
