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
	public static function add($hook, $type, $class, array $options = array()) {
		$mode        = array_key_exists('mode', $options)        ? $options['mode'] : '*';
		$name        = array_key_exists('name', $options)        ? $options['name'] : ucwords($class . ' ' . $type . ' ' . $hook);
		$description = array_key_exists('description', $options) ? $options['description'] : '';
		$method      = array_key_exists('method', $options)      ? $options['method'] : 'hook_' . ($type == 'post' ? 'post_' : '') . strtolower($hook);
		$global      = array_key_exists('global', $options)      ? $options['global'] : 0;
		$sequence    = array_key_exists('sequence', $options)    ? $options['sequence'] : 0;
		
		//Certain hooks should be global
		if (in_array($hook, array('init'))) {
			$global = 1;
		}
		
		$data = array(
			'class'       => $class,
			'hook'        => $hook,
			'type'        => $type,
			'mode'        => $mode,
			'name'        => $name,
			'description' => $description,
			'method'      => $method,
			'global'      => $global,
			'sequence'    => $sequence,
		);
		$hook = new HookObj();
		if ($hook->replace($data)) {
			Controller::addSuccess('Added hook ' . $name . '(' . $class . '::' . $method . ')');
			$toret = true;
		} else {
			Controller::addError('Could not add hook ' . $name . '(' . $class . '::' . $method . ')');
			$toret = false;
		}
		return $toret;
	}

	public static function get($hook, $type = 'pre') {
		$params = array(':type' => $type, ':hook' => $hook);
		$query = '
SELECT *
FROM `hooks`
LEFT JOIN `components` ON `hooks`.`class` = `components`.`name`
WHERE
	`hook` = :hook AND
	`type` = :type AND
	`hooks`.`active` = 1 AND
	`components`.`active` = 1';
		if (Controller::$area) {
			$query .= ' AND (`global` = 1 OR `class` = :area)';
			$params[':area'] = Controller::$area;
		}
		if (Controller::$view && Controller::$view->mode) {
			$query .= ' AND `mode` IN (:mode, \'*\')';
			$params[':mode'] = Controller::$view->mode;
		}
		$query .= ' ORDER BY `sequence`';
		$query = new CustomQuery($query);
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
				if (Component::isActive($hook['class']) && method_exists($hook['class'], $hook['method'])) {
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
	
	public static function install(array $options = array()) {
		$options['install_model'] = array_key_exists('install_model', $options) ? $optinos['install_model'] : false;
		$toret = parent::install($options);
		return $toret;
	}

	public static function pre_install() {
		$toret = self::installModel(__CLASS__ . 'Obj');
		return $toret;
	}
}
