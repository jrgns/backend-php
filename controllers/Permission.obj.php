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
}

