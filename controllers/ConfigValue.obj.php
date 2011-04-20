<?php
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
		//Check the config file
		return Backend::getConfig('application.' . $name, $default);
	}
}

