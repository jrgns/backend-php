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
class System extends TableCtl {
	public function init() {
	}
	
	public function html_display() {
		Backend::add('Sub Title', 'Administer this Website');
	}
	
	public function action_check() {
		$roles = GateKeeper::getRoles();
		if (!$roles || !count($roles)) {
			if (Controller::$debug) {
				Controller::addNotice('No roles setup, addings some');
			}
			$roles = $this->getDefaultRoles();
			if ($roles) {
				foreach($roles as $role) {
					GateKeeper::assign($role['role'], $role['access_type'], $role['access_id']);
					if (Controller::$debug) {
						Controller::addSuccess('Added role ' . $role['role']);
					}
				}
			}
			
			$permits = $this->getDefaultPermissions();
			if ($permits) {
				foreach($permits as $permit) {
					GateKeeper::permit($permit['role'], $permit['control'], $permit['action'], $permit['subject'], $permit['subject_id']);
					if (Controller::$debug) {
						Controller::addSuccess('Added permission to ' . $role['action'] . ' to ' . $permit['role']);
					}
				}
			}
		} else {
			if (Controller::$debug) {
				var_dump($roles);
			}
		}
	}
}
