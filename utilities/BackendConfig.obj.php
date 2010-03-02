<?php
/**
 * The class file for BackendConfig
 *
 * @author J Jurgens du Toit (JadeIT cc) <jurgens.dutoit@gmail.com> - initial API and implementation
 * @copyright Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available {@link http://www.eclipse.org/legal/epl-v10.html here}
 * @license http://www.eclipse.org/legal/epl-v10.html Eclipse Public License v1.0
 * @package Utilities
 */
/**
 * Base class to handle configurations
 */
class BackendConfig {
	protected $config = array();

	public function __construct($config, $site_state = 'production') {
		$this->config = array();
		if (is_array($config)) {
			foreach($config as $section => $values) {
				$sections = explode(':', $section);
				if (in_array($site_state, $sections) || $section == 'application') {
					foreach($config[$section] as $name => $value) {
						$this->config = array_merge_recursive($this->config, self::buildValue($name, $value));
					}
				}
			}
		} else if (is_string($config) && file_exists($config)) {
			$config = parse_ini_file($config, true);
			self::__construct($config, $site_state);
		}
	}
	
	private static function buildValue($names, $value, array $config = array()) {
		if (is_string($names)) {
			$names = explode('.', $names);
		}
		if (count($names) > 0) {
			$config[array_shift($names)] = self::buildValue($names, $value, $config);
		} else {
			$config = $value;
		}
		return $config;
	}
	
	public function getValue($names, $default = null) {
		$toret = true;
		if (is_string($names)) {
			$names = explode('.', $names);
		}
		$value = $this->config;
		foreach($names as $name) {
			if (array_key_exists($name, $value)) {
				$value = $value[$name];
			} else {
				$toret = false;
				break;
			}
		}
		return $toret ? $value : $default;
	}
	
	public static function asArray() {
		return self::$config;
	}
}
