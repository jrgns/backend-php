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
		if (file_exists($config)) {
			$this->config = parse_ini_file($config, true);
		} else {
			//Default values
			//Settings are used to manipulate how the application behaves
			$this->config['settings'] = array(
				'Class'            => 'Application',
				'DefaultView'      => 'HtmlView',
				'UseCache'         => true,
				'TemplateLocation' => 'templates'
			);
			//Application values are used in and around the application, and is often presented to the end user.
			$this->config['application'] = array(
				'Title'          => 'Backend',
				'Moto'           => 'Something pithy and funny...',
				'HelpBoxContent' => 'Backend aims to be an easy to use data manipulator and translator for the Web Developer.',
				'Description'    => 'A PHP Backend that reduces coding and makes life easier for a programmer',
			);
			//Authoer values are used in and around the application, nad is often presented to the end user.
			$this->config['author'] = array(
				'Name'    => 'J Jurgens du Toit',
				'Email'   => 'jrgns@jadeit.co.za',
				'Website' => 'http://jrgns.net',
			);
		}
	}

	public function getValue($names, $default = null) {
		$toret = true;
		if (is_string($names)) {
			$names = explode('.', $names);
		}
		if (count($names) > 2) {
			return null;
		}
		if (count($names) == 1) {
			$section = reset($names);
			//Check site specific first
			$result = array();
			if (array_key_exists($section, $this->config)) {
				$result = array_merge($result, $this->config[$section]);
			}
			if (array_key_exists(SITE_STATE . ':' . $section, $this->config)) {
				$result = array_merge($result, $this->config[SITE_STATE . ':' . $section]);
			}
			return $result;
		} else {
			list($section, $name) = $names;
			//Check site specific section and name
			if (array_key_exists(SITE_STATE . ':' . $section, $this->config)
				&& array_key_exists(SITE_STATE . ':' . $name, $this->config[SITE_STATE . ':' . $section])) {
					return $this->config[SITE_STATE . ':' . $section][SITE_STATE . ':' . $name];
			//Check site specific section
			} else if (array_key_exists(SITE_STATE . ':' . $section, $this->config)
				&& array_key_exists($name, $this->config[SITE_STATE . ':' . $section])) {
					return $this->config[SITE_STATE . ':' . $section][$name];
			//Check site specific name
			} else if (array_key_exists($section, $this->config)
				&& array_key_exists(SITE_STATE . ':' . $name, $this->config[$section])) {
					return $this->config[$section][SITE_STATE . ':' . $name];
			} else if (array_key_exists($section, $this->config)
				&& array_key_exists($name, $this->config[$section])) {
					return $this->config[$section][$name];
			}
		}
		return $default;
	}

	public function setValue($names, $value, $write_file = true) {
		if (is_string($names)) {
			$names = explode('.', $names);
		}
		if (is_string($names)) {
			$names = explode('.', $names);
		}
		if (count($names) > 2) {
			return null;
		}

		foreach(array_reverse($names) as $name) {
			$value = array($name => $value);
		}
		$this->config = array_replace_recursive($this->config, $value);
		if ($write_file) {
			return (bool)$this->writeFile();
		}
		return true;
	}

	public function writeFile() {
		$location = Backend::getConfigFileLocation();
		if ($result = write_ini_file($this->config, $location, true)) {
			$content = file_get_contents($location);
			//Some added security
			$content = ';<?php die(); ?>' . PHP_EOL . $content;
			$result = file_put_contents($location, $content);
		}
		return $result;
	}

	public static function asArray() {
		return self::$config;
	}
}
