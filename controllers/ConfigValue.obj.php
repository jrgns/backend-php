<?php
//die("We need to decide how we're storing and retrieving values. application.\$name? or do we allow other sections (such as author) as well?");
/**
 * This class should be used to store and retrieve configuration values
 * for the application.
 *
 * It uses values from the database and falls back on values in the
 * configuration file if no value exists.
 */
class ConfigValue extends Value {
    private static $cache = array();
	/**
	 * Get a configuration value.
	 *
	 * Config Value names should be CamelCase
	 */
	public static function get($name, $default = null) {
	    if (array_key_exists($name, self::$cache)) {
	        return self::$cache[$name];
	    }
	    if (array_key_exists('application.' . $name, self::$cache)) {
	        return self::$cache['application.' . $name];
	    }
		if (defined('BACKEND_WITH_DATABASE') && BACKEND_WITH_DATABASE) {
			$value = Value::get($name, null);
			//Retrieved from the DB
			if (!is_null($value)) {
        		self::$cache[$name] = $value;
				return $value;
			}
		}
		$name = explode('.', $name);
		if (count($name) == 1) {
			array_unshift($name, 'application');
		}
		//Check the config file
		$result = Backend::getConfig($name, $default);
		self::$cache[implode('.', $name)] = $result;
		return $result;
	}

	public static function set($name, $value) {
	    self::$cache[$name] = $value;
		if (Component::isActive('Value')) {
			return Value::set($name, $value);
		}
		$name = explode('.', $name);
		if (count($name) == 1) {
			array_unshift($name, 'application');
		}
		//Update the config file
		return Backend::setConfig($name, $value);
	}

	public static function adminLinks() {
		if (!Backend::getDB('default')) {
			return false;
		}
		return array(
			array('href' => '?q=value/admin', 'text' => 'Values')
		);
	}

	public static function install_check() {
		//Check the cache folder
		if (!Backend::checkConfigFile()) {
			if (function_exists('posix_getgrgid') && function_exists('posix_getegid')) {
				if ($group = posix_getgrgid(posix_getegid())) {
					$group = $group['name'];
				}
			}
			$values = array(
				'file' => Backend::getConfigFileLocation(),
				'group'  => isset($group) ? $group : false,
			);
			Backend::addContent(Render::file('config_value.fix_config.tpl.php', $values));
			return false;
		}

		if (self::get('settings.ConfigValueSet')) {
			return true;
		}
		if (is_post()) {
			$result = true;
			foreach($_POST as $name => $value) {
				$name = str_replace('_', '.', $name);
				if (in_array($name, array('application.Title', 'application.Moto', 'application.HelpBoxContent', 'application.Description', 'author.Name', 'author.Email' ,'author.Website'))) {
					if (!self::set($name, $value)) {
						Backend::addError('Could not set ' . $name);
						$result = false;
					}
				} else {
					var_dump('Rejected:', $name);
				}
			}
			self::set('settings.ConfigValueSet', $result);
			Controller::redirect();
		}
		Backend::addContent(Render::file('config_value.values.tpl.php'));
		return false;
	}

	public static function installModel($model, array $options = array()) {
		return parent::installModel('Value', $options);
	}
}
