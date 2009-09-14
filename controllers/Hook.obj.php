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
class Hook extends TableCtl {
	public static function get($hook, $type = 'pre') {
		$params = array(':type' => $type, ':hook' => $hook);
		$query = 'SELECT * FROM `hooks` WHERE `hook` = :hook AND `type` = :type AND `active` = 1';
		if (Controller::$mode) {
			$query .= ' AND `mode` IN (:mode, \'*\')';
			$params[':mode'] = Controller::$mode;
		}
		$query .= ' ORDER BY `sequence`';
		$query = new Query($query);
		$toret = $query->fetchAll($params);
		return $toret;
	}
	
	public static function run($hook_name, $type, array $parameters = array(), array $options = array()) {
		$toret        = array_key_exists('toret', $options) ? $options['toret'] : null;
		$return_index = array_key_exists('return_index', $options) ? $options['return_index'] : null;
		if (is_null($return_index) && count($parameters)) {
			$return_index = 0;
		}
		if ($hooks = self::get($hook_name, $type)) {
			foreach($hooks as $hook) {
				if (class_exists($hook['class'], true) && method_exists($hook['class'], $hook['method'])) {
					//var_dump('Running ' . $hook['class'] . '::' . $hook['method'] . ' for hook ' . $hook_name . '-' . $type);
					$toret = call_user_func_array(array($hook['class'], $hook['method']), $parameters);
					if (count($parameters) && !is_null($return_index)) {
						$parameters[$return_index] = $toret;
					}
				}
			}
		}
		return $toret;
	}
}
