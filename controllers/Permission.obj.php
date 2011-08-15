<?php
/**
 * Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * @license http://www.eclipse.org/legal/epl-v10.html
 * @package Controllers
 *
 * Contributors:
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 */
class Permission extends TableCtl {
	/**
	 * Perhaps subject can be omitted, and defaults to a value that will allow the an action
	 * for all subjects? Eg, display.
	 */
	public static function add($role, $action, $subject, $subject_id = 0, array $options = array()) {
		if (!Backend::getDB('default')) {
			return false;
		}

		//Loop through arrays
		if (is_array($role)) {
			$result = 0;
			foreach($role as $one_role) {
				if (self::add($one_role, $action, $subject, $subject_id, $options)) {
					$result++;
				}
			}
			return $result;
		}
		if (is_array($action)) {
			$result = 0;
			foreach($action as $one_action) {
				if (self::add($role, $one_action, $subject, $subject_id, $options)) {
					$result++;
				}
			}
			return $result;
		}
		if (is_array($subject)) {
			$result = 0;
			foreach($subject as $one_subject) {
				if (self::add($role, $role, $one_subject, $subject_id, $options)) {
					$result++;
				}
			}
			return $result;
		}

		if (is_array($subject_id)) {
			$options    = $subject_id;
			$subject_id = 0;
		}
		$control = array_key_exists('control', $options) ? $options['control'] : '100';
		$system  = array_key_exists('system',  $options) ? $options['system']  : 0;

		$data = array(
			'role'       => $role,
			'action'     => $action,
			'subject'    => class_for_url($subject),
			'subject_id' => $subject_id,
			'control'    => $control,
			'system'     => $system,
			'active'     => 1,
		);

		$permission = new PermissionObj();
		if ($permission->replace($data)) {
			Backend::addSuccess('Added permission to ' . $action . ' for ' . $role);
			$toret = true;
		} else {
			Backend::addError('Could not add permission to ' . $action . ' for ' . $role);
			$toret = false;
		}
		return $toret;
	}

	public static function check($action = '*', $subject = '*', $subject_id = 0) {
		if (!BACKEND_WITH_DATABASE) {
			return true;
		}

		static $cache = array();
		if (is_object($subject)) {
			$subject = get_class($subject);
		}

		$key = serialize(array($action, $subject, $subject_id));
		if (array_key_exists($key, $cache)) {
			//return $cache[$key];
		}

		$roles = GateKeeper::permittedRoles($action, class_for_url($subject), $subject_id);
		$user  = BackendUser::check();
		$user  = (!$user && !empty($_SESSION['BackendUser'])) ? $_SESSION['BackendUser'] : $user;
		if (!$user && !in_array('anonymous', $roles)) {
			if (Controller::$debug) {
				Backend::addNotice('Anonymous User');
			}
			$cache[$key] = true;
			return true;
		}
		if ($subject != '*' && !Component::isActive(class_name($subject))) {
			if (Controller::$debug) {
				Backend::addNotice('Invalid Component: ' . class_name($subject));
			}
			$cache[$key] = false;
			return false;
		}
		if (empty($user->roles)) {
			if (Controller::$debug) {
				Backend::addNotice('No User Roles');
			}
			$cache[$key] = false;
			return false;
		}
		$intersect = is_array($roles) ? array_intersect($user->roles, $roles) : $user->roles;
		if (Controller::$debug >= 2) {
			Backend::addNotice('Valid roles found: ' . json_encode($intersect));
		}
		$result = count($intersect) ? true : false;
		$cache[$key] = $result;
		return $result;
	}

	public static function getDefaults() {
		$toret = array(
			array('role' => 'anonymous', 'control' => '100', 'action' => 'display', 'subject' => 'content', 'subject_id' => '*'),
			array('role' => 'superadmin', 'control' => '111', 'action' => '*', 'subject' => '*', 'subject_id' => '*'),
		);
		return $toret;
	}

	public static function pre_install() {
		$toret = self::installModel(__CLASS__ . 'Obj', array('drop_table' => true));
		return $toret;
	}

	public static function install(array $options = array()) {
		$options['install_model'] = array_key_exists('install_model', $options) ? $options['install_model'] : false;
		$toret = parent::install($options);

		foreach(self::getDefaults() as $permit) {
			GateKeeper::permit($permit['role'], $permit['action'], $permit['subject'], $permit['subject_id'], $permit['control']);
			if (Controller::$debug) {
				Backend::addSuccess('Added permission to ' . $permit['action'] . ' to ' . $permit['role']);
			}
		}
		return $toret;
	}
}
