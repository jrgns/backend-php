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
	private static $cache = array();
	function action_test() {
		self::set('test', 'on');
		var_dump(self::get('test'));
		self::set('test', 'off');
		var_dump(self::get('test'));
		die('Value::action_test');
	}
	
	public static function get($name, $default = null) {
		$toret = $default;
		//The only call allowed without BACKEND_INSTALLED = true, is to get the value for BACKEND_INSTALLED
		if (!defined('BACKEND_INSTALLED') || BACKEND_INSTALLED) {
			if (isset(self::$cache[$name])) {
				$toret = self::$cache[$name];
			} else {
				$toret = Value::retrieve($name);
				if ($toret) {
					$toret = !empty($toret['value']) ? $toret['value'] : $default;
				} else if (!is_null($default)) {
					$toret = $default;
					Value::set($name, $default);
				}
			}
		}
		self::$cache[$name] = $toret;
		return $toret;
	}
	
	public static function set($name, $new_value) {
		//$new_value = base64_encode(serialize($new_value));

		$value = new ValueObj();
		$data = array(
			'name' => $name,
			'value' => $new_value,
		);
		$toret = $value->replace($data);
		self::$cache[$name] = $new_value;
		return $toret;
	}
	
	public function action_admin() {
		$query = new SelectQuery('values');
		$result = new stdClass();
		$result->values = $query->fetchAll();

		$value = new ValueObj();
		$result->obj_values = $value->fromPost();
		return $result;
	}
	
	public function html_admin($result) {
		$values = (array)$result;
		$values['action_url'] = 'value/replace';
		$values['action_name'] = 'Update';
		Backend::addContent(Render::renderFile('values.tpl.php', $values));
	}
	
	public static function admin_links() {
		return array(
			array('href' => '?q=value/admin', 'text' => 'Values')
		);
	}

	public static function pre_install() {
		$toret = self::installModel(__CLASS__ . 'Obj', array('drop_table' => true));
		return $toret;
	}

	public static function install(array $options = array()) {
		$options['install_model'] = array_key_exists('install_model', $options) ? $options['install_model'] : false;
		$toret = parent::install($options);
		return $toret;
	}
}
