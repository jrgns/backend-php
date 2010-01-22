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
class Assignment extends TableCtl {
	function action_list($start, $count) {
		$Assignments = new AssignmentObj();
		$conditions = array(
			'`assignments`.`active` = 1',
		);
		$joins = array(
			array('type' => 'LEFT', 'table' => '`roles`', 'conditions' => array('`roles`.`id` = `assignments`.`role_id`', '`roles`.`active` = 1')),
		);
		$fields = array(
			'`assignments`.*',
			'`roles`.`name` AS `role`',
		);
		list ($query, $params) = $Assignments->getSelectSQL(array('conditions' => $conditions, 'joins' => $joins, 'fields' => $fields));
		$Assignments->load(array('query' => $query, 'parameters' => $params));
		Backend::add('Assignments', $Assignments);
		Controller::addContent(Render::renderFile('assignment_list.tpl.php'));
	}

	public static function getDefaults() {
		$toret = array(
			//All anonymous visitors to the site will be classified as visitors
			array('role' => 'anonymous', 'access_type' => 'visitor', 'access_id' => '*'),
			//All registered visitors to the site will be classified as users
			array('role' => 'authenticated', 'access_type' => 'users', 'access_id' => '*'),
			//No one will be registered as nobody
			array('role' => 'nobody', 'access_type' => 'nobody', 'access_id' => '0'),
			//The user with id 1 will be super admin
			array('role' => 'superadmin', 'access_type' => 'users', 'access_id' => '1'),
		);
		return $toret;
	}

	public static function install(array $options = array()) {
		$options['drop_table'] = array_key_exists('drop_table', $options) ? $options['drop_table'] : true;
		$toret = parent::install($options);

		foreach(self::getDefaults() as $assignment) {
			if (GateKeeper::assign($assignment['role'], $assignment['access_type'], $assignment['access_id'])) {
				Controller::addSuccess('Added assignment to ' . $assignment['role']);
				$toret = $toret && true;
			} else {
				Controller::addError('Could not add assignment to ' . $assignment['role']);
				$toret = false;
			}
		}
		return $toret;
	}
}
