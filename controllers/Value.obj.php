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
class Value extends AreaCtl {
	function action_test() {
		self::set('test', 'on');
		var_dump(self::get('test'));
		self::set('test', 'off');
		var_dump(self::get('test'));
		die;
	}
	
	public static function get($name, $default = null) {
		$toret = new ValueObj();
		$toret = $toret->retrieve($name);
		return !empty($toret['value']) ? $toret['value'] : $default;
	}
	
	public static function set($name, $new_value) {
		$data = self::get($name);
		if ($data) {
			$value = new ValueObj($data['id']);
			if ($value) {
				$value->update(array('value' => $new_value));
			}
		} else {
			$value = new ValueObj();
			$data = array(
				'name' => $name,
				'value' => $new_value,
			);
			$value->create($data);
		}
	}
}
