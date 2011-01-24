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
		Backend::addContent('<ul>');
		Backend::addContent('<li><a href="?q=gate_manager/roles">Roles</a></li>');
		Backend::addContent('<li><a href="?q=gate_manager/permissions">Permissions</a></li>');
		Backend::addContent('</ul>');
		return true;
	}
	
	public function action_roles($id = false) {
		$toret = new stdClass();
		if ($id) {
			$toret->role = Role::retrieve($id, 'dbobject');
			if ($toret->role) {
				$query = new SelectQuery('Permission');
				$query->filter('`role` = :role');
				$toret->permissions = $query->fetchAll(array(':role' => $toret->role->array['name']));

				$query = new SelectQuery('Assignment');
				$query
					->leftJoin(BackendAccount::getName(), array('`' . BackendAccount::getTable() . '`.`id` = `assignments`.`access_id`'))
					->filter("`assignments`.`access_type` = 'users'")
					->filter('`role_id` = :role OR `role_id` = 0');
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
			Backend::addContent(Render::renderFile('role_display.tpl.php'));
		} else {
			Backend::add('Sub Title', 'GateKeeper Roles');
			Backend::add('Object', $result->roles);
			Backend::addContent(Render::renderFile('role_list.tpl.php'));
		}
	}
	
	public function post_permissions($component = false) {
		$parameters = array();
		$query = new DeleteQuery('Permission');
		$query
			->filter("`role` != 'nobody'")
			->filter("`role` != 'superadmin'");
		if ($component) {
			$query->filter('`subject` = :component');
			$parameters[':component'] = class_for_url($component);
		}
		$result = $query->execute($parameters);
		if ($result === false) {
			Backend::addError('Could not empty permissions table');
			return false;
		}

		$permission = new PermissionObj();
		$count = 0;
		foreach(Controller::getPayload() as $key => $roles) {
			if (strpos($key, '::') === false) {
				continue;
			}
			list($subject, $action) = explode('::', $key, 2);
			foreach($roles as $role => $value) {
				$data = array(
					'subject' => $subject,
					'action'  => $action,
					'role'    => $role,
				);
				if ($permission->replace($data)) {
					$count ++;
				}
			}
		}
		return $count;
	}
	
	public function get_permissions($component = false) {
		$toret = new stdClass();

		//Base Permissions
		$parameters = array();
		$query = new SelectQuery('Permission');
		$query
			->distinct()
			->field(array('action', 'subject'))
			->filter('`active` = 1')
			//->filter("`role` = 'nobody'")
			->filter('`subject_id` = 0')
			->group('`subject`, `action` WITH ROLLUP');
		if ($component) {
			$query->filter('`subject` = :component');
			$parameters[':component'] = class_for_url($component);
		}
		$toret->base_perms = $query->fetchAll($parameters);
		
		//Roles
		$query = new SelectQuery('Role');
		$query->filter('`active` = 1');
		$toret->roles = $query->fetchAll();

		//Activated Permissions
		$parameters = array();
		$query = new SelectQuery('Permission', array('fields' => "CONCAT(`subject`, '::', `action`), GROUP_CONCAT(DISTINCT `role` ORDER BY `role`) AS `roles`"));
		$query
			->filter('`active` = 1')
			->filter('`subject_id` = 0')
			->filter("`role` != 'nobody'")
			->group('`subject`, `action`');
		if ($component) {
			$query->filter('`subject` = :component');
			$parameters[':component'] = class_for_url($component);
		}
		$permissions = $query->fetchAll($parameters, array('with_key' => 1));
		$toret->permissions = array();
		foreach($permissions as $key => $value) {
			$toret->permissions[$key] = explode(',', current($value));
		}
		return $toret;
	}
	
	public function html_permissions($result) {
		if (is_post()) {
			if ($result === false) {
				Backend::addError('Could not update Permissions');
			} else {
				Backend::addSuccess($result . ' Permissions Updated');
			}
			Controller::redirect('previous');
		}
		//GET
		if (!empty(Controller::$parameters[0])) {
			Backend::add('Sub Title', class_name(Controller::$parameters[0]) . ' Permissions');
			Links::add('All Permissions', '?q=gate_manager/permissions', 'secondary');

		} else {
			Backend::add('Sub Title', Backend::getConfig('application.Title') . ' Permissions');
		}
		Backend::addContent(Render::renderFile('gate_manager.permissions.tpl.php', (array)$result));
	}
	
	public static function admin_links() {
		return array(
			array('text' => 'Manage Roles'      , 'href' => '?q=gate_manager/roles'),
			array('text' => 'Manage Permissions', 'href' => '?q=gate_manager/permissions'),
		);
	}
}
