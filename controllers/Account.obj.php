<?php
class Account extends BackendAccount {
	public static function install(array $options = array()) {
		ConfigValue::set('BackendAccount', 'Account');
		return parent::install($options);
	}
}
