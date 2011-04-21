<?php
class BackendSystemLockObj extends BackendLockObj {
	public function __construct(BackendLockObj $object) {
		foreach($object as $name => $value) {
			$this->$name = $value;
		}
	}
	
	function validate($data, $action, $options = array()) {
		$toret = true;
		$data = parent::validate($data, $action, $options);
		if ($data) {
			if (empty($data['expire'])) {
				Backend::addError('System Locks must expire');
				$toret = false;
			}
		}
		return $toret ? $data : false;
	}

	public function get($name = false, $type = BackendLock::LOCK_CUSTOM, $expire = null, $password = null) {
		if (empty($password)) {
			if (Component::isActive('BackendError')) {
				BackendError::add('Missing BackendSystemLock Password', 'No password was supplied for the system lock named ' . $name);
			}
			return null;
		}
		$result = parent::get($name, $type, $expire);
		if ($result) {
			ConfigValue::set('LockPassword_' . $this->array['name'], $password);
		}
		
	}

	public function check() {
		$result = parent::check();
		if ($result === false && $password = Controller::getVar('lock_password_' . $this->array['name'])) {
			if ($password == ConfigValue::get('LockPassword_' . $this->array['name'], false)) {
				return true;
			}
		}
		return $result;
	}
}
