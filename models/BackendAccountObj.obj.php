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
class BackendAccountObj extends DBObject {
	function __construct($meta = array(), array $options = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		if (!array_key_exists('table', $meta)) {
			$meta['table'] = 'users';
		}
		if (!array_key_exists('name', $meta)) {
			$meta['name'] = 'User';
		}
		if (!array_key_exists('fields', $meta)) {
			$meta['fields'] = array(
				'id'        => 'primarykey',
				'name'      => 'string',
				'surname'   => 'string',
				'email'     => 'email',
				'website'   => 'website',
				'mobile'    => 'telnumber',
				'username'  => array('type' => 'string', 'required' => true),
				'password'  => array('type' => 'password', 'required' => true),
				'salt'      => 'salt',
				'confirmed' => 'boolean',
				'active'    => 'boolean',
				'modified'  => 'lastmodified',
				'added'     => 'dateadded',
			);
		}
		if (!array_key_exists('keys', $meta)) {
			$meta['keys'] = array(
				'username' => 'unique',
				'email'    => 'unique',
			);
		}
		return parent::__construct($meta, $options);
	}

	public function read($options = array()) {
		$result = parent::read($options);
		if ($result) {
			$query = new SelectQuery('Assignment');
			$query
				->distinct()
				->field('`roles`.`name`')
				->leftJoin('Role', '`roles`.`id` = `assignments`.`role_id`')
				->filter("`assignments`.`access_type` = 'users'")
				->filter('`assignments`.`access_id` = :user_id OR `assignments`.`access_id` = 0')
				->order('`roles`.`name`');
			$roles = $query->fetchAll(array(':user_id' => $this->getMeta('id')), array('column' => 0));
			$roles = empty($roles) ? array() : $roles;
			if ($this->object) {
				$this->object->roles = $roles;
			}
			if ($this->array) {
				$this->array['roles'] = $roles;
			}
		}
		return $result;
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
					Backend::addError('Please choose a username');
				}
			} else {
				//Lower ASCII only
				$data['username'] = filter_var(trim($data['username']), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
				if (in_array($data['username'], $banned_usernames) && empty($_SESSION['just_installed'])) {
					$toret = false;
					Backend::addError('Please choose a valid username');
				}
			}
		}
		if ($toret && $action == 'create') {
			$data['salt'] = get_random('numeric');
			$data['password'] = md5($data['salt'] . $data['password'] . Controller::$salt);
			if (Backend::getConfig('backend.application.user.confirm')) {
				$data['confirmed'] = false;
			} else {
				$data['confirmed'] = array_key_exists('confirmed', $data) ? $data['confirmed'] : true;
			}
		}
		return $toret ? $data : false;
	}
}
