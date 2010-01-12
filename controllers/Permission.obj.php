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
		if (is_array($subject_id)) {
			$options    = $subject_id;
			$subject_id = 0;
		}
		$control = array_key_exists('control', $options) ? $options['control'] : '100';
		$system  = array_key_exists('system',  $options) ? $options['system']  : 0;
		
		$data = array(
			'role'       => $role,
			'action'     => $action,
			'subject'    => $subject,
			'subject_id' => $subject_id,
			'control'    => $control,
			'system'     => $system,
			'active'     => 1,
		);

		$permission = new PermissionObj();
		if ($permission->replace($data)) {
			Controller::addSuccess('Added permission to ' . $action . ' for ' . $role);
			$toret = true;
		} else {
			Controller::addError('Could not add permission to ' . $action . ' for ' . $role);
			$toret = false;
		}
		return $toret;
	}
	
	public static function check($action = '*', $subject = '*', $subject_id = 0) {
		$toret = false;
		$user = Account::checkUser();
		if (!empty($user->roles)) {
			$user_roles = $user->roles;
			$roles = GateKeeper::permittedRoles($action, $subject, $subject_id);
			$intersect = array_intersect($user_roles, $roles);
			$toret = count($intersect) ? true : false;
		}
		return $toret;
		
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
				Controller::addSuccess('Added permission to ' . $permit['action'] . ' to ' . $permit['role']);
			}
		}
		return $toret;
	}
}

