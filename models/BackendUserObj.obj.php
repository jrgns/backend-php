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
class BackendUserObj extends DBObject {
	function __construct($meta = array(), array $options = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		if (!array_key_exists('table', $meta)) {
			$meta['table'] = 'backend_users';
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
		if (!array_key_exists('order', $meta)) {
			$meta['order'] = '`username`';
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
		$data = parent::validate($data, $action, $options);
		if (!$data) {
			return $data;
		}
		switch ($action) {
		case 'create':
			$data['active'] = array_key_exists('active', $data) ? $data['active'] : true;
			//Lower ASCII only
			$data['username'] = filter_var(trim($data['username']), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
			//TODO Make the banned usernames configurable
			$banned_usernames = array('root', 'admin', 'superadmin', 'superuser', 'webadmin', 'postmaster', 'webdeveloper', 'webmaster', 'administrator', 'sysadmin');
			if (in_array($data['username'], $banned_usernames) && BackendUser::hasSuperUser()) {
				Backend::addError('Please choose a valid username');
				return false;
			}
			$data['salt'] = get_random('numeric');
			$data['password'] = md5($data['salt'] . $data['password'] . Controller::$salt);
			if (ConfigValue::get('application.confirmUser')) {
				$data['confirmed'] = false;
			} else {
				$data['confirmed'] = array_key_exists('confirmed', $data) ? $data['confirmed'] : true;
			}
			break;
		case 'update':
			if (!empty($data['password'])) {
				$data['password'] = md5($this->array['salt'] . $data['password'] . Controller::$salt);
			}
			break;
		}
		return $data;
	}

	public function getRetrieveSQL() {
		list($query, $parameters)  = $this->getSelectSQL();

		$filter = '`' . $this->getMeta('id_field') . '` = :parameter';
		$filter .= ' OR `username` = :parameter';
		$query->filter($filter);
		return $query;
	}
}
