<?php
class Account extends BackendAccount {
	public static function install(array $options = array()) {
		Value::set('BackendAccount', 'Account');
		return true;
	}
}
