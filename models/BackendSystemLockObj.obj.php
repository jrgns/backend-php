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
}
