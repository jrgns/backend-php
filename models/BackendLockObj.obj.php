<?php
class BackendLockObj extends DBObject {
	function __construct($meta = array(), array $options = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		$meta['database'] = 'bi_api';
		$meta['table'] = 'backend_locks';
		$meta['name'] = 'BackendLock';
		$meta['fields'] = array(
			'id' => array('field' => 'id', 'type' => 'primarykey', 'null' => false, 'default' => NULL),
			'name' => array('field' => 'name', 'type' => 'string', 'null' => false, 'default' => NULL, 'string_size' => 255),
			'type' => array('field' => 'type', 'type' => 'number', 'null' => false, 'default' => NULL),
			'expire' => array('field' => 'expire', 'type' => 'datetime', 'null' => true, 'default' => NULL),
			'locked' => array('field' => 'locked', 'type' => 'boolean', 'null' => false, 'default' => NULL),
			'modified' => array('field' => 'modified', 'type' => 'lastmodified', 'null' => false),
			'added' => array('field' => 'added', 'type' => 'dateadded', 'null' => false, 'default' => NULL),
		);

		$meta['keys'] = array(
			'name' => array(
				'type'   => 'unique',
				'fields' => array('name'),
			),
		);
		return parent::__construct($meta, $options);
	}

	public function get($name = false, $type = BackendLock::LOCK_CUSTOM, $expire = null) {
		if (!$this->array) {
			//No name specified, can't create, return null
			if (!$name) {
				return null;
			}
			//Create the lock
			$data = array(
				'name'   => $name,
				'type'   => $type,
				'locked' => true,
			);
			if (!is_null($expire)) {
				$data['expire'] = $expire;
			}
			return $this->create($data) ? $this : false;
		}
		if (!$this->check()) {
			return false;
		}
		return $this->update(array('locked' => true, 'type' => $type, 'expire' => $expire)) ? $this : false;
	}
	
	public function release() {
		if (!$this->array) {
			return null;
		}
		return $this->update(array('locked' => false)) ? $this : false;
	}
	
	/**
	 * Check wether the lock is available
	 */
	public function check() {
		if (!$this->array) {
			return null;
		}
		//Check the lock and it's expire date
		if ($this->array['locked'] && !($this->array['expire'] && time() >= strtotime($this->array['expire']))) {
			return false;
		}
		return true;
	}

	function validate($data, $action, $options = array()) {
		$toret = true;
		$data = parent::validate($data, $action, $options);
		if ($data) {
			if (array_key_exists('type', $data)) {
				if (!in_array($data['type'], BackendLock::$types)) {
					Backend::addError('Invalid Lock Type');
					$toret = false;
				}
			}
			if (!empty($data['expire'])) {
				if (strtotime($data['expire']) < time()) {
					Backend::addError('Expiry date in the past');
					$toret = false;
				}
			}
		}
		return $toret ? $data : false;
	}
}