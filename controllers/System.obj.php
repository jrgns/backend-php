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
	
	protected function getDefaultPermissions() {
		$toret = array(
			array('role' => 'anonymous', 'control' => '100', 'action' => 'display', 'subject' => 'content', 'subject_id' => '*'),
			array('role' => 'superadmin', 'control' => '111', 'action' => '*', 'subject' => '*', 'subject_id' => '*'),
		);
		return $toret;
	}
	
	protected function getDefaultRoles() {
		$toret = array(
			//All anonymous visitors to the site will be classified as visitors
			array('role' => 'anonymous', 'access_type' => 'visitor', 'access_id' => '*'),
			//All registered visitors to the site will be classified as users
			array('role' => 'registered', 'access_type' => 'user', 'access_id' => '*'),
			//No one will be registered as nobody
			array('role' => 'nobody', 'access_type' => 'nobody', 'access_id' => '0'),
			//The user with id 1 will be super admin
			array('role' => 'superadmin', 'access_type' => 'user', 'access_id' => '1'),
		);
		return $toret;
	}
}
