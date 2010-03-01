<?php
class Account extends BackendAccount {
	public static function install(array $options = array()) {
		Value::set('BackendAccount', get_class($this));
	}
}
