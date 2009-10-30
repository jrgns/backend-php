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
	public function action() {
		$toret = null;
		if (array_key_exists('msg', $_REQUEST)) {
			Controller::addError(self::getError($_REQUEST['msg']));
		}
		$method = 'action_' . Controller::parameter('action');
		if (Controller::$debug) {
			var_dump('Checking Method ' . $method);
		}
		if (method_exists($this, $method)) {
			if ($this->checkPermissions()) {
				if (Controller::$view->mode == 'html') {
					$comp_script = '/scripts/' . Controller::parameter('area') . '.component.js';
					$comp_style  = '/styles/' . Controller::parameter('area') . '.component.css';
					if (file_exists(SITE_FOLDER . $comp_script)) {
						Controller::addScript(SITE_LINK . $comp_script);
					}
					if (file_exists(SITE_FOLDER . $comp_style)) {
						Controller::addStyle(SITE_LINK . $comp_style);
					}
				}
				$toret = $this->$method();
			} else {
				Controller::whoops(array('title' => 'Permission Denied', 'message' => 'You do not have permission to ' . Controller::parameter('action') . ' ' . get_class($this)));
				$toret = false;
			}
		} else if (Controller::$debug) {
			Controller::addError('Method ' . get_class($this) . '::' . $method . ' does not exist');
		}
		return $toret;
	}
	
	/**
	 * Return a area specific error
	 *
	 * Override this function if you want to customize the errors returned for an area.
	 */
	public static function getError($num, $class_name = false) {
		$msg = false;
		$class_name = $class_name ? $class_name : class_name(Controller::parameter('area'));
		if (class_exists($class_name, true)) {
			$vars = get_class_vars($class_name);
			var_dump($vars);
			$msg = empty($vars['error_msgs'][$num]) ? false : $vars['error_msgs'][$num];
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
	 * Override this function if you want to customize the permissions for an area.
	 */
	public function checkPermissions(array $options = array()) {
		$toret = true;
		$action = !empty($options['action']) ? $options['action'] : (
			!empty(Controller::$parameters['action']) ? Controller::check_reverse_map('action', Controller::$parameters['action']) : '*'
		);
		$subject = !empty($options['subject']) ? $options['subject'] : (
			!empty(Controller::$parameters['area']) ? Controller::check_reverse_map('area', Controller::$parameters['area']) : '*'
		);
		$subject_id = !empty($options['subject_id']) ? $options['subject_id'] : (
			!empty(Controller::$parameters['id']) ? Controller::check_reverse_map('id', Controller::$parameters['id']) : 0
		);

		$installed = Value::get('admin_installed', false);
		if ($installed) {
			$roles = GateKeeper::permittedRoles($action, $subject, $subject_id);
			if (!empty($_SESSION['user'])) {
				if (Controller::$debug) {
					if (is_object($_SESSION['user']) && property_exists($_SESSION['user'], 'roles')) {
						Controller::addNotice('Current user roles: ' . serialize($_SESSION['user']->roles));
					} else {
						Controller::addError('No user roles');
						$_SESSION['user']->roles = array();
					}
				}
				if ($roles) {
					$intersect = array_intersect($_SESSION['user']->roles, $roles);
					$toret = count($intersect) ? true : false;
				} else {
					$toret = $_SESSION['user']->roles;
				}
			}
		} else {
			if (!($subject == 'admin' && in_array($action, array('install', 'post_install')))) {
				$toret = false;
			}
		}
		return $toret;
	}

	public static function checkTuple($tuple) {
		return $tuple;
	}
}
