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
class GateManager extends AreaCtl {
	function getTabLinks($action) {
		return array(
			array('text' => 'Roles', 'link' => '?q=gate_manager/roles'),
			array('text' => 'Permissions', 'link' => '?q=gate_manager/permissions'),
			array('text' => 'Assignments', 'link' => '?q=gate_manager/assignments'),
		);
	}
	
	public function init() {
	}
	
	public function html_display($object) {
		Backend::add('Sub Title', 'Administer the GateKeeper');
		Controller::addContent('<ul><li><a href="">Roles</a></li></ul>');
		Controller::addContent('<ul><li><a href="?q=permission">Permissions</a></li></ul>');
		Controller::addContent('<ul><li><a href="?q=assignment">Assignments</a></li></ul>');
		return true;
	}
	
	public function action_roles() {
		$toret = new stdClass();
		$id = Controller::parameter('id');
		if ($id) {
			$toret->role = Role::retrieve(array('id' => $id));
			if ($toret->role) {
				$query = new CustomQuery("SELECT * FROM `permissions` WHERE `role` = :role");
				$toret->permissions = $query->fetchAll(array(':role' => $toret->role->array['name']));
			} else {
				$toret->permissions = null;
			}
		} else {
			$toret->roles = Role::retrieve();
		}
		return $toret;
	}
	
	public function html_roles($result) {
		Backend::add('TabLinks', $this->getTabLinks('permissions'));
		if (Controller::$id) {
			Backend::add('Sub Title', 'GateKeeper Roles');
			if ($result->role) {
				Backend::add('Sub Title', 'Role: ' . $result->role->array['name']);
				Backend::add('Result', $result);
				Controller::addContent(Render::renderFile('role_display.tpl.php'));
			}
		} else {
			Backend::add('Sub Title', 'GateKeeper Roles');
			Backend::add('Object', $result->roles);
			Controller::addContent(Render::renderFile('role_list.tpl.php'));
		}
	}
	
	public function html_permissions($object) {
		Backend::add('Sub Title', 'GateKeeper Permissions');
		Backend::add('TabLinks', $this->getTabLinks('permissions'));
		$Permissions = new PermissionObj();
		$Permissions->load();
		Backend::add('Object', $Permissions);
		Controller::addContent(Render::renderFile('std_list.tpl.php'));
	}
	
	public function html_assignments($object) {
		Backend::add('Sub Title', 'GateKeeper Assignments');
		Backend::add('TabLinks', $this->getTabLinks('assignments'));
		$Assignments = new AssignmentObj();
		$Assignments->load();
		Backend::add('Object', $Assignments);
		Controller::addContent(Render::renderFile('std_list.tpl.php'));
	}
	
	public function action_check() {
		$roles = GateKeeper::getRoles();
		if (!$roles || !count($roles)) {
			if (Controller::$debug) {
				Controller::addNotice('No roles setup, addings some');
			}
			self::install();
		} else {
			if (Controller::$debug) {
				var_dump($roles);
			}
		}
	}
	
	public function action_install() {
		self::install();
	}
	
	public static function install() {
		$toret = true;
		$roles = self::getDefaultRoles();
		if ($roles) {
			$RoleObj = new RoleObj();
			$RoleObj->truncate();

			foreach($roles as $role) {
				if ($RoleObj->create($role)) {
					Controller::addSuccess('Added role ' . $role['name']);
				} else {
					$toret = false;
				}
			}
		}
		
		$assigns = self::getDefaultAssignments();
		if ($assigns) {
			$AssignmentObj = new AssignmentObj();
			$AssignmentObj->truncate();

			foreach($assigns as $assignment) {
				if ($AssignmentObj->create($assignment)) {
					Controller::addSuccess('Added assignment ' . $assignment['access_type'] . ' to ' . $assignment['role_id']);
				} else {
					$toret = false;
				}
			}
		}

		$permits = self::getDefaultPermissions();
		if ($permits) {
			$PermissionObj = new PermissionObj();
			$PermissionObj->truncate();

			foreach($permits as $permit) {
				$toret = Permission::add($permit['role'], $permit['action'], $permit['subject'], $permit['subject_id']) && $toret;
			}
		}
		return $toret;
	}
	
	protected static function getDefaultRoles() {
		$toret = array(
			array('id' => 1, 'name' => 'nobody', 'description' => 'No one. No, really, no one.', 'active' => 1),
			array('id' => 2, 'name' => 'anonymous', 'description' => 'The standard, anonymous user with minimum rights', 'active' => 1),
			array('id' => 3, 'name' => 'authenticated', 'description' => 'A registered user', 'active' => 1),
			array('id' => 4, 'name' => 'superadmin', 'description' => 'The user with all the rights', 'active' => 1),
			
		);
		return $toret;
	}

	protected static function getDefaultAssignments() {
		$toret = array(
			//No one will be registered as nobody
			array('role_id' => 1, 'access_type' => 'nobody', 'access_id' => '0'),
			//All anonymous visitors to the site will be classified as visitors
			array('role_id' => 2, 'access_type' => 'visitor', 'access_id' => '*'),
			//All registered visitors to the site will be classified as users
			array('role_id' => 3, 'access_type' => 'users', 'access_id' => '*'),
			//The user with id 1 will be super admin
			array('role_id' => 4, 'access_type' => 'users', 'access_id' => '1'),
		);
		return $toret;
	}
	
	protected static function getDefaultPermissions() {
		$toret = array(
			array('role' => 'anonymous', 'control' => '100', 'action' => 'display', 'subject' => 'content', 'subject_id' => '*'),
			array('role' => 'superadmin', 'control' => '111', 'action' => '*', 'subject' => '*', 'subject_id' => '*'),
		);
		return $toret;
	}
}
