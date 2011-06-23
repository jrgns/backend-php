<?php
/**
 * The class file for Role
 */
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

/**
 * This is the controller for the table roles.
 */
class Role extends TableCtl {
	public static function add($name, $description, array $options = array()) {
		$id     = array_key_exists('id', $options)     ? $options['id']     : null;
		$active = array_key_exists('active', $options) ? $options['active'] : null;

		$data = array(
			'name'        => $name,
			'description' => $description,
			'active'      => $active,
		);
		if (!is_null($active)) {
			$data['id'] = $id;
		}

		$RoleObj = new RoleObj();
		if ($RoleObj->replace($data)) {
			Backend::addSuccess('Added role ' . $data['name']);
			$toret = true;
		} else {
			Backend::addError('Could not add role ' . $data['name']);
			$toret = false;
		}
		return $toret;
	}

	public function action_create($id = false) {
		if (is_get()) {
			$obj = Controller::getVar('obj');
			$obj = $obj ? $obj : array();
			$obj['active'] = 1;
			Controller::setVar('obj', $obj);
		}
		$result = parent::action_create();
		return $result;
	}

	public static function getDefaults() {
		$toret = array(
			array('id' => 1, 'name' => 'nobody', 'description' => 'No one. No, really, no one.', 'active' => 1),
			array('id' => 2, 'name' => 'anonymous', 'description' => 'The standard, anonymous user with minimum rights', 'active' => 1),
			array('id' => 3, 'name' => 'authenticated', 'description' => 'A registered user', 'active' => 1),
			array('id' => 4, 'name' => 'superadmin', 'description' => 'The user with all the rights', 'active' => 1),

		);
		return $toret;
	}

	public static function install(array $options = array()) {
		$options['drop_table'] = array_key_exists('drop_table', $options) ? $options['drop_table'] : true;
		$toret = parent::install($options);

		foreach(self::getDefaults() as $role) {
			$toret = self::add($role['name'], $role['description'], array('id' => $role['id'], 'active' => $role['active'])) && $toret;
		}
		return $toret;
	}
}
