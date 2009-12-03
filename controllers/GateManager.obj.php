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
	
	public function action_roles($id = false) {
		$toret = new stdClass();
		if ($id) {
			$toret->role = Role::retrieve($id, 'dbobject');
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
		if (!empty($result->role)) {
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
	
	public static function admin_links() {
		return array(
			array('text' => 'Manage Roles'      , 'href' => '?q=gate_manager/roles'),
			array('text' => 'Manage Permissions', 'href' => '?q=gate_manager/permissions'),
			array('text' => 'Manage Assignments', 'href' => '?q=gate_manager/assignments'),
		);
	}
}
