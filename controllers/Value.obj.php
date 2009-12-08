<?php
/**
 * Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * @license http://www.eclipse.org/legal/epl-v10.html
 * @package Controllers
 *
 * Contributors:
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 */
class Value extends TableCtl {
	function action_test() {
		self::set('test', 'on');
		var_dump(self::get('test'));
		self::set('test', 'off');
		var_dump(self::get('test'));
		die('Value::action_test');
	}
	
	public static function get($name, $default = null) {
		$toret = $default;
		if (defined('BACKEND_INSTALLED') && BACKEND_INSTALLED) {
			$toret = Value::retrieve($name);
			$toret = !empty($toret['value']) ? unserialize(base64_decode($toret['value'])) : $default;
		}
		return $toret;
	}
	
	public static function set($name, $new_value) {
		$new_value = base64_encode(serialize($new_value));

		$value = new ValueObj();
		$data = array(
			'name' => $name,
			'value' => $new_value,
		);
		$toret = $value->replace($data);
		return $toret;
	}

	public static function pre_install() {
		$toret = self::installModel(__CLASS__ . 'Obj');
	}

	public static function install(array $options = array()) {
		$options['install_model'] = array_key_exists('install_model', $options) ? $options['install_model'] : false;
		$toret = parent::install($options);
		return $toret;
	}
}
