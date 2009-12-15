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
		$toret = array(
			array('text' => 'Gate Manager', 'link' => '?q=gate_manager'),
		);
		switch ($action) {
		case 'permissions':
			$toret[] = array('text' => 'Add Permission', 'link' => '?q=permission/create');
			break;
		case 'roles':
			$toret[] = array('text' => 'Add Role', 'link' => '?q=role/create');
			break;
		}
		return $toret;
	}
	
	public function html_index($object) {
		Backend::add('Sub Title', 'Administer the GateKeeper');
		Controller::addContent('<ul>');
		Controller::addContent('<li><a href="">Roles</a></li>');
		Controller::addContent('<li><a href="?q=permission">Permissions</a></li>');
		Controller::addContent('<li><a href="?q=assignment">Assignments</a></li>');
		Controller::addContent('</ul>');
		return true;
	}
	
	public function action_roles($id = false) {
		$toret = new stdClass();
		if ($id) {
			$toret->role = Role::retrieve($id, 'dbobject');
			if ($toret->role) {
				$query = new CustomQuery("SELECT * FROM `permissions` WHERE `role` = :role");
				$toret->permissions = $query->fetchAll(array(':role' => $toret->role->array['name']));

				$query = new CustomQuery("SELECT * FROM `assignments` LEFT JOIN `users` ON `users`.`id` = `assignments`.`access_id` WHERE `assignments`.`access_type` = 'users' AND (`role_id` = :role OR `role_id` = 0)");
				$toret->assignments = $query->fetchAll(array(':role' => $toret->role->array['id']));
			} else {
				$toret->permissions = null;
			}
		} else {
			$toret->roles = Role::retrieve();
		}
		return $toret;
	}
	
	public function html_roles($result) {
		Backend::add('TabLinks', $this->getTabLinks('roles'));
		if (!empty($result->role)) {
			Backend::add('Sub Title', 'Role: ' . $result->role->array['name']);
			Backend::add('Result', $result);
			Controller::addContent(Render::renderFile('role_display.tpl.php'));
		} else {
			Backend::add('Sub Title', 'GateKeeper Roles');
			Backend::add('Object', $result->roles);
			Controller::addContent(Render::renderFile('role_list.tpl.php'));
		}
	}
	
	public function action_permissions($id = false) {
		$toret = new stdClass();
		if ($id) {
			$toret->permission = Permission::retrieve($id, 'dbobject');
			if ($toret->permission) {
				$query = new CustomQuery("SELECT * FROM `permissions` WHERE `role` = :role");
				$toret->roles = $query->fetchAll(array(':role' => $toret->permission->array['name']));
			} else {
				$toret->roles = null;
			}
		} else {
			$toret->permissions = Role::retrieve();
		}
		return $toret;
	}
	
	public function html_permissions($result) {
		Backend::add('TabLinks', $this->getTabLinks('permissions'));
		if (!empty($result->permission)) {
			Backend::add('Sub Title', 'GateKeeper Permissions');
			Backend::add('Result', $result);
			Controller::addContent(Render::renderFile('permission_display.tpl.php'));
		} else {
			Backend::add('Sub Title', 'GateKeeper Permissions');
			Backend::add('Object', $result->permissions);
			Controller::addContent(Render::renderFile('permission_list.tpl.php'));
		}
	}
	
	public static function admin_links() {
		return array(
			array('text' => 'Manage Roles'      , 'href' => '?q=gate_manager/roles'),
			array('text' => 'Manage Permissions', 'href' => '?q=gate_manager/permissions'),
			array('text' => 'Manage Assignments', 'href' => '?q=gate_manager/assignments'),
		);
	}
}
