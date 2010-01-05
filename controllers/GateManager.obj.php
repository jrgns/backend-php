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
		Controller::addContent('<li><a href="?q=gate_manager/roles">Roles</a></li>');
		Controller::addContent('<li><a href="?q=gate_manager/permissions">Permissions</a></li>');
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
	
	public function action_update_permissions() {
		$toret = true;
		if (is_post()) {
			$query = new DeleteQuery('permissions');
			if ($query->filter("`role` != 'nobody'")->filter("`role` != 'superadmin'")->execute()) {
				$permission = new PermissionObj();
				foreach($_POST as $key => $roles) {
					list($subject, $action) = explode('_', $key);
					foreach($roles as $role => $value) {
						$data = array(
							'subject' => $subject,
							'action'  => $action,
							'role'    => $role,
						);
						$toret = $permission->replace($data) && $toret;
					}
				}
			} else {
				Controller::addError('Could not empty permissions table');
			}
		}
		return $toret;
	}
	
	public function html_update_permissions($result) {
		if ($result) {
			Controller::addSuccess('Permissions updated');
		} else {
			Controller::addError('Could not update Permissions');
		}
		Controller::redirect('?q=gate_manager/permissions');
	}
	
	public function action_permissions() {
		$toret = new stdClass();
		$query = new SelectQuery('permissions');
		$query
			->filter('`active` = 1')
			->filter("`role` = 'nobody'")
			->filter('`subject_id` = 0')
			->group('`subject`, `action` WITH ROLLUP');
		$toret->base_perms = $query->fetchAll();
		
		$query = new SelectQuery('roles');
		$query->filter('`active` = 1');
		$toret->roles = $query->fetchAll();

		$query = new SelectQuery('permissions', array('fields' => "CONCAT(`subject`, '_', `action`), GROUP_CONCAT(DISTINCT `role` ORDER BY `role`) AS `roles`"));
		$query
			->filter('`active` = 1')
			->filter('`subject_id` = 0')
			->filter("`role` != 'nobody'")
			->group('`subject`, `action`');
		$permissions = $query->fetchAll(array(), array('with_key' => 1));
		$toret->permissions = array();
		foreach($permissions as $key => $value) {
			$toret->permissions[$key] = explode(',', current(current($value)));
		}
		return $toret;
	}
	
	public function html_permissions($result) {
		Controller::addContent(Render::renderFile('permission_list.tpl.php', (array)$result));
	}
	
	public static function admin_links() {
		return array(
			array('text' => 'Manage Roles'      , 'href' => '?q=gate_manager/roles'),
			array('text' => 'Manage Permissions', 'href' => '?q=gate_manager/permissions'),
		);
	}
}
