<?php
/**
 * @author J Jurgens du Toit (JadeIT cc) <jurgens.dutoit@gmail.com> - initial API and implementation
 * @copyright Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available {@link http://www.eclipse.org/legal/epl-v10.html here}
 * @license http://www.eclipse.org/legal/epl-v10.html Eclipse Public License v1.0
 * @package Models
 *
 * Contributors:
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 */
class AccountObj extends DBObject {
	function __construct($meta = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'users';
		$meta['name'] = 'User';
		$meta['fields'] = array(
			'id' => 'primarykey',
			'name' => 'string',
			'surname' => 'string',
			'email' => 'email',
			'mobile' => 'telnumber',
			'username' => 'string',
			'password' => 'password',
			'salt' => 'salt',
			'confirmed' => 'boolean',
			'active' => 'boolean',
			'modified' => 'lastmodified',
			'added' => 'dateadded',
		);
		/*$meta['children'] = array(
			'roles' => array('model' => 'UserRole', 'conditions' => array('user_id' => 'id')),
		);*/
		return parent::__construct($meta);
	}
	
	function validate($data, $action, $options = array()) {
		$toret = false;

		$data = parent::validate($data, $action, $options);
		if ($data) {
			$banned_usernames = array('root', 'admin', 'superadmin', 'superuser', 'webadmin', 'postmaster', 'webdeveloper', 'webmaster', 'administrator', 'sysadmin');
			$toret = true;
			if ($action == 'create') {
				$data['active'] = array_key_exists('active', $data) ? $data['active'] : true;
			}
			if (empty($data['username'])) {
				if ($action == 'create') {
					$toret = false;
					Controller::addError('Please choose a username');
				}
			} else {
				//Lower ASCII only
				$data['username'] = filter_var(trim($data['username']), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
				if (in_array($data['username'], $banned_usernames)) {
					$toret = false;
					Controller::addError('Please choose a valid username');
				}
			}
		}
		if ($toret && $action == 'create') {
			$data['salt'] = get_random('numeric');
			$data['password'] = md5($data['salt'] . $data['password'] . Controller::$salt);
			if (Backend::getConfig('backend.application.user.confirm')) {
				$data['confirmed'] = false;
			} else {
				$data['confirmed'] = true;
			}
		}
		return $toret ? $data : false;
	}
}
