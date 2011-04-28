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
	/**
	 * Get a configuration value.
	 *
	 * Config Value names should be CamelCase
	 */
	public static function get($name, $default = null) {
		if (Component::isActive('Value')) {
			$value = Value::get($name, null);
			//Retrieved from the DB
			if (!is_null($value)) {
				return $value;
			}
		}
		$name = explode('.', $name);
		if (count($name) == 1) {
			array_unshift($name, 'application');
		}
		//Check the config file
		return Backend::getConfig($name, $default);
	}
	
	public static function set($name, $value) {
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

	public static function install_check() {
		//Check the cache folder
		if (!Backend::checkConfigFile()) {
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
				}
			}
			if ($result) {
				self::set('settings.ConfigValueSet', true);
			}
			return $result;
		}
		Backend::addContent(Render::renderFile('config_value.values.tpl.php', $values));
		return false;
	}
}

