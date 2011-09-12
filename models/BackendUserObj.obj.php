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
				'email'     => array('type' => 'email', 'string_size' => 255),
				'website'   => 'website',
				'mobile'    => array('type' => 'telnumber', 'string_size' => 15),
				'username'  => array('type' => 'string', 'string_size' => 60),
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
				'user' => array('type' => 'unique', 'fields' => array(
	                        'email', 'username', 'mobile'
	                    )
                    ),
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

			//We need either an email, mobile number or username to register a user
			//Lower ASCII only
			if (!empty($data['username'])) {
			    $data['username'] = filter_var(trim($data['username']), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
			    //TODO Make the banned usernames configurable
			    $banned_usernames = array('root', 'admin', 'superadmin', 'superuser', 'webadmin', 'postmaster', 'webdeveloper', 'webmaster', 'administrator', 'sysadmin');
			    if (in_array($data['username'], $banned_usernames) && BackendUser::hasSuperUser()) {
				    Backend::addError('Please choose a valid username');
				    return false;
			    }
		    }
		    if (empty($data['username']) && empty($data['email']) && empty($data['mobile'])) {
		        Backend::addError('Please provide a username');
		    }
		    //If the username is an email address, make it the email address
		    if (!empty($data['username']) && filter_var($data['username'], FILTER_VALIDATE_EMAIL)) {
		        if (!empty($data['email'])) {
    		        list($data['username'], $data['email']) = array($data['email'], $data['username']);
		        } else {
		            $data['email'] = $data['username'];
		            unset($data['username']);
	            }
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

	public function getUsername() {
	    if (!empty($this->array['username'])) {
	        return $this->array['username'];
        }
	    if (!empty($this->array['email'])) {
	        return $this->array['email'];
        }
	    if (!empty($this->array['mobile'])) {
	        return $this->array['mobile'];
        }
        return false;
	}

	public function getRetrieveSQL() {
		list($query, $parameters)  = $this->getSelectSQL();

		$filter = 'BINARY `' . $this->getMeta('id_field') . '` = :parameter';
		$filter .= ' OR `username` = :parameter';
		$filter .= ' OR `email` = :parameter';
		$filter .= ' OR `mobile` = :parameter';
		$query->filter($filter);
		return $query;
	}
}
