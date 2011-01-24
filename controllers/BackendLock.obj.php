<?php
/**
 * The class file for BackendLock
 *
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 * @copyright Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * @license http://www.eclipse.org/legal/epl-v10.html
 * @package ControllerFiles
 */
 
/**
 * This is the controller for the table backend_locks.
 *
 * This module provides various locks with which concurrency can be prevented.
 * There are a number of types of locks:
 * System   - This will lock up the whole system, and by nature must have an expiry date.
 * Area     - This will lock up an area.
 * Action   - This will lock up a specific action in an area.
 * Custom   - Coder defined lock.
 * @package Controllers
 */
class BackendLock extends TableCtl {
	const LOCK_SYSTEM = 1;
	const LOCK_AREA   = 2;
	const LOCK_ACTION = 3;
	const LOCK_CUSTOM = 4;
	
	public static $types = array(
		self::LOCK_SYSTEM,
		self::LOCK_AREA,
		self::LOCK_ACTION,
		self::LOCK_CUSTOM,
	);
	
	public static function retrieve($parameter = false, $return = 'array', array $options = array()) {
		$result = parent::retrieve($parameter, $return, $options);
		if (!($result instanceof BackendLockObj) || !$result->array) {
			return $result;
		}
		switch($result->array['type']) {
		case self::LOCK_SYSTEM:
			return new BackendSystemLockObj($result);
			break;
		default:
			return $result;
			break;
		}
	}
	
	public function action_test() {
		/*$lock = BackendLock::get('testing', BackendLock::LOCK_CUSTOM);
		if (!$lock) {
			Backend::addError('Could not aquire lock');
		} else {
			Backend::addSuccess('Testing is ' . ($lock->check() ? 'Available' : 'Not Available'));
		}
		$lock = BackendLock::release('testing');
		Backend::addSuccess('Testing is ' . ($lock->check() ? 'Available' : 'Not Available'));
		if ($lock = BackendLock::get('testing_expiry', BackendLock::LOCK_CUSTOM, '2010-01-01')) {
			Backend::addSuccess('Testing Expiry is ' . ($lock->check() ? 'Available' : 'Not Available'));
		}
		if ($lock = BackendLock::get('testing_type', 5)) {
			Backend::addSuccess('Testing Type is ' . ($lock->check() ? 'Available' : 'Not Available'));
		}*/
		if ($lock = BackendLock::get('testing_system', BackendLock::LOCK_SYSTEM)) {
			Backend::addSuccess('Testing Type is ' . ($lock->check() ? 'Available' : 'Not Available'));
		} else {
			Backend::addSuccess('Should not get System lock (no expiry or password)');
		}
		if ($lock = BackendLock::get('testing_system', BackendLock::LOCK_SYSTEM, 'now + 1 minutes', 'Abc123')) {
			Backend::addSuccess('Testing Type is ' . ($lock->check() ? 'Available' : 'Not Available'));
		} else {
			Backend::addError('Could not get System lock');
		}
		return false;
	}
	
	public static function get($name, $type = self::LOCK_CUSTOM, $expire = null, $password = null) {
		$lock = BackendLock::retrieve($name, 'dbobject');
		return $lock->get($name, $type, $expire, $password);
	}
	
	public static function release($name) {
		$lock = BackendLock::retrieve($name, 'dbobject');
		return $lock->release();
	}

	public static function check($name) {
		$lock = BackendLock::retrieve($name, 'dbobject');
		return $lock->check();
	}
	
	public static function hook_init() {
		//Check for any system locks
		$query = new SelectQuery('BackendLock');
		$query
			->field('`name`')
			->filter('`type` = :type')
			->filter('`locked` = 1')
			->filter('`expire` > NOW()');
		while($lock_name = $query->fetchColumn(array(':type' => self::LOCK_SYSTEM))) {
			$lock = BackendLock::retrieve($lock_name, 'dbobject');
			if (!$lock->check()) {
				//A Lock isn't available, so the request must be aborted.
				Controller::whoops(
					'Service Unavailable',
					array(
						'message'   => 'System Offline until ' . $lock->array['expire'] . '. Locked under ' . $lock->array['name'],
						'code_hint' => 503,
					)
				);
				header('X-Backend-Lock: ' . $lock->array['name']);
				header('X-Backend-Lock-Expire: ' . $lock->array['expire']);
			}
		}
	}

	public static function install(array $options = array()) {
		$toret = parent::install($options);
		Hook::add('init', 'pre', get_called_class()) && $toret;
		return $toret;
	}
}
