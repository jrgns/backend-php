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
		$toret = new ValueObj();
		$toret = $toret->retrieve($name);
		$toret = !empty($toret['value']) ? unserialize(base64_decode($toret['value'])) : $default;
		return $toret;
	}
	
	public static function set($name, $new_value) {
		$new_value = base64_encode(serialize($new_value));

		$old_val = new ValueObj();
		$old_val = $old_val->retrieve($name);
		if (!is_null($old_val)) {
			$value = new ValueObj($old_val['id']);
			if ($value) {
				$toret = $value->update(array('value' => $new_value));
			}
		} else {
			$value = new ValueObj();
			$data = array(
				'name' => $name,
				'value' => $new_value,
			);
			$toret = $value->create($data);
		}
		return $toret;
	}
}
