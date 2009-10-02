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
		
		$method = 'action_' . Controller::$action;
		if (method_exists($this, $method)) {
			if ($this->checkPermissions()) {
				$toret = $this->$method();
			} else {
				Controller::whoops(array('title' => 'Permission Denied', 'message' => 'You do not have permission to ' . Controller::$action . ' ' . get_class($this)));
				$toret = false;
			}
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
		$class_name = $class_name ? $class_name : class_name(Controller::$area);
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
		//TODO Check permissions!
		if ($this->checkPermissions(array('action' => 'list'))) {
			$links[] = array('link' => '?q=' . Controller::$area . '/list', 'text' => 'List');
		}
		if ($this->checkPermissions(array('action' => 'create'))) {
			$links[] = array('link' => '?q=' . Controller::$area . '/create', 'text' => 'Create');
		}
		return $links;
	}
	
	/**
	 * Check permissions for this area
	 *
	 * Override this function if you want to customize the permissions for an area.
	 */
	public function checkPermissions(array $options = array()) {
		$toret = true;
		$action = !empty($options['action']) ? $options['action'] : (!empty(Controller::$action) ? Controller::check_reverse_map('action', Controller::$action) : '*');
		$subject = !empty($options['subject']) ? $options['subject'] : (!empty(Controller::$area) ? Controller::check_reverse_map('area', Controller::$area) : '*');
		$subject_id = !empty($options['subject_id']) ? $options['subject_id'] : (!empty(Controller::$id) ? Controller::check_reverse_map('id', Controller::$id) : 0);

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
		return $toret;
	}

	public static function checkTuple($tuple) {
		return $tuple;
	}
}
