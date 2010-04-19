<?php
/**
 * Definition file for the GateKeeper class
 *
 * Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * @license http://www.eclipse.org/legal/epl-v10.html
 * @package Core
 *
 * Contributors:
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 */

/**
 * Role Based Access Control Class
 * For id's, we'll use a 0 for All ids, -1 for none, -2 for the current user id
 * Based on http://www.packtpub.com/article/access-control-in-php-5-cms-part1 and http://www.packtpub.com/article/access-control-in-php-5-cms-part2
 * @todo Figure out how to use the control field in permissions
 * @todo Write a minimizeRoleset function
 * @todo Figure out what barredRole should do
 */
class GateKeeper {
	public static function check(array $user_roles, $action = '*', $subject = '*', $subject_id = 0) {
		$roles = self::permittedRoles($action, $subject, $subject_id);
		$intersect = array_intersect($user_roles, $roles);
		$toret = count($intersect) ? true : false;
		return $toret;
	}
	
	public static function permit($role, $action, $subject, $subject_id, $control) {
		$toret = false;
		$params = array(':role' => $role, ':control' => $control, ':action' => $action, ':subject' => $subject, ':subject_id' => $subject_id);
		$query = new SelectQuery('Permission');
		$query
			->filter('`role` = :role')
			->filter('`control` = :control')
			->filter('`action` = :action')
			->filter('`subject` = :subject')
			->filter('`subject_id` = :subject_id')
			->filter('system = 0');
		$id = $query->fetchColumn($params);
		if ($id) {
			$query = new UpdateQuery('Permission');
			$query
				->data(array('control' => ':control'))
				->filter('`id` = :id');
			return $query->execute(array(':id' => $id, ':control' => $control));
		} else {
			$data = array('role', 'control', 'action', 'subject', 'subject_id');
			$data = array_combine($data, array_values($params));
			$query = new InsertQuery('Permission');
			$query->data($data);
			return $query->execute();
		}
	}
	
	public static function assign($role_id, $access_type, $access_id) { 
		$toret = false;
		//if (!self::barredRole($role)) {
			if (!is_numeric($role_id)) {
				$role_id = Role::retrieve($role_id);
				$role_id = $role_id['id'];
			}
			$params = array(':role_id' => $role_id, ':access_type' => $access_type, ':access_id' => $access_id);
			$query = new SelectQuery('Assignment');
			$query
				->filter('`role_id`= :role_id')
				->filter('`access_type` = :access_type')
				->filter('`access_id` = :access_id');
			$id = $query->fetchColumn($params);
			if ($id) {
				$toret = true;
			} else {
				$keys = array('role_id', 'access_type', 'access_id');
				$data = array_combine($keys, array_values($params));
				$query = new InsertQuery('Assignment');
				$query->data($data);
				$toret = $query->execute() ? true : false;
			}
		//}
		return $toret;
	}

	public static function dropPermissions($action, $subject, $subject_id) { 
		$params = array(':action' => $action, ':subject' => $subject, ':subject_id' => $subject_id);
		$query = new DeleteQuery('Permission');
		$query
			->filter('`action` = :action')
			->filter('`subject` = :subject')
			->filter('`subject_id` = :subject_id')
			->filter('system = 0');
		return $query->execute($params);
	}
  
	public static function permittedRoles($action = '*', $subject = '*', $subject_id = 0) {
		if (Controller::$debug) {
			Backend::addNotice('Checking action ' . $action . ' for ' . $subject . ' ' . $subject_id);
		}

		$roles = self::permissionHolders($action, $subject, $subject_id);
		$specific = false;
		if ($roles) {
			$toret = array();
			$most_spec = array(
				0 => array(),
				1 => array(),
				2 => array(),
				3 => array(),
			);
			foreach ($roles as $permission) {
				$toret[$permission['role']] = $permission['role'];
				if ($action != '*' && $permission['action'] == $action) {
					if ($subject != '*' && $permission['subject'] == $subject) {
						if ($subject_id != 0 && $permission['subject_id'] == $subject_id) {
							$specific = true;
							$most_spec[3][$permission['role']] = $permission['role'];
						} else if ($permission['subject_id'] == 0) {
							$most_spec[2][$permission['role']] = $permission['role'];
						}
					} else if ($permission['subject'] == '*') {
						$most_spec[1][$permission['role']] = $permission['role'];
					}
				} else if ($permission['action'] == '*') {
					$most_spec[0][$permission['role']] = $permission['role'];
				}
			}
			$most_spec = array_filter($most_spec);
		} else {
			$toret = false;
		}
		$toret = $specific ? end($most_spec) : $toret;
		if (Controller::$debug) {
			Backend::addNotice('Roles found: ' . serialize($toret));
		}
		return $toret;
	}
	
	public static function getRoles() {
		$toret = false;
		$where = array();
		$params = array();

		if (!count($where)) {
			$where[] = '1';
		}
		$query = new SelectQuery('Permission');
		$query->filter($where);

		$rows = $query->fetchAll($params);
		if ($rows) {
			$toret = array();
			foreach($rows as $row) {
				$toret[$row['id']] = $row['role'];
			}
		}
		return $toret;
	}
	
	private static function permissionHolders($action = '*', $subject = '*', $subject_id = 0) {
		$toret = false;
		$query = new SelectQuery('Permission');
		$params = array();
		if ($action != '*') {
			$query->filter("(`action` = :action OR `action` = '*')");
			$params[':action'] = $action;
		}
		if ($subject != '*') {
			$query->filter("(`subject` = :subject OR `subject` = '*')");
			$params[':subject'] = $subject;
		}
		if ($subject_id != '0') {			
			$query->filter("(`subject_id` = :subject_id OR `subject_id` = 0)");
			$params[':subject_id'] = $subject_id;
		}
		$toret = $query->fetchAll($params);
		return $toret;
	}
	
	private static function barredRole($role) {
		return in_array($role, array('anonymous', 'authorized', 'nobody'));
	}

	public static function dropAccess($access_type, $access_id) { 
		$toret = false;
		$params = array(':access_type' => $access_type, ':access_id' => $access_id);
		$query = new DeleteQuery('Assignment');
		$query
			->filter('`access_type` = :access_type')
			->filter('`access_id` = :access_id');
		$toret = $query->execute($params) ? true : false;
		return $toret;
	}

	public static function assignRoleSet($roleset, $access_type, $access_id) { 
		if (self::dropAccess($access_type, $access_id)) {
			//$roleset = $this->authoriser->minimizeRoleSet($roleset); 
			foreach($roleset as $role) {
				self::assign($role, $access_type, $access_id); 
			}
		}
	}
}
