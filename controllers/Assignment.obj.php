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
	function action_list($count) {
		$Assignments = new AssignmentObj();
		$conditions = array(
			'`assignments`.`active` = 1',
		);
		$joins = array(
			'LEFT JOIN `roles` ON `roles`.`id` = `assignments`.`role_id` AND `roles`.`active` = 1',
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
}
