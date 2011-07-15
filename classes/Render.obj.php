<?php
/**
 * This file defines the Render Class
 * Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * @license http://www.eclipse.org/legal/epl-v10.html
 * @package Core
 *
 * Contributors:
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 */

 /**
  * The Render class renders HTML template files.
  *
  */
class Render {
	public static $do_cache = true;
	private static $init = false;
	private static $cache_folder = false;

	private static function init() {
		if (!self::$init) {
			self::$do_cache = ConfigValue::get('settings.UseCache', true);

			if (self::$do_cache) {
				self::$do_cache = self::checkCacheFolder();
			}
			self::$init = true;
		}
	}

	private static function checkCacheFolder() {
		if (defined('SITE_FOLDER')) {
			self::$cache_folder = SITE_FOLDER . '/cache/';
		} else {
			self::$cache_folder = APP_FOLDER . '/cache/';
		}

		//Check the cache folder
		if (!file_exists(self::$cache_folder)) {
			if (@!mkdir(self::$cache_folder, 0755)) {
				if (SITE_STATE != 'production') {
					Backend::addError('Cannot create cache folder ' . self::$cache_folder);
				} else {
					Backend::addError('Cannot create cache folder');
				}
				return false;
			}
		}
		if (!is_writable(self::$cache_folder)) {
			if (SITE_STATE != 'production') {
				Backend::addError('Render::Cache folder unwritable (' . self::$cache_folder . ')');
			} else {
				Backend::addError('Render::Cache folder unwritable');
			}
			return false;
		}
		return true;
	}

	public static function createTemplate($destination, $origin, array $variables = array()) {
		self::init();

		$template_content = self::buildTemplate($origin);
		$template_content = self::evalContent($origin, $template_content, $variables);
		if (!$template_content) {
			Backend::addError('Could not generate template');
			return false;
		}
		$template_loc     = ConfigValue::get('settings.TemplateLocation', 'templates');
		if (defined('SITE_FOLDER')) {
			$dest_file = SITE_FOLDER . '/' . $template_loc . '/' . $destination;
		} else {
			$dest_file = APP_FOLDER . '/' . $template_loc . '/' . $destination;
		}
		if (@file_put_contents($dest_file, $template_content)) {
			if (SITE_STATE != 'production') {
				chmod($dest_file, 0664);
			}
			return true;
		} else {
			$error = error_get_last();
			if (strpos($error['message'], 'Permission denied') !== false) {
				if (Controller::$debug) {
					Backend::addError('Permission denied. Check writeability of templates folder ' . dirname($dest_file) . '.');
				} else {
					Backend::addError('Permission denied. Check writeability of templates folder.');
				}
			}
		}
		return false;
	}

	public static function renderFile($template_name, array $values = array()) {
		trigger_error('Render::renderFile deprecated, use Render::file instead', E_USER_NOTICE);
		return self::file($template_name, $values);
	}

	public static function file($template_name, array $values = array()) {
		self::init();

		//Build the template
		$content = self::buildTemplate($template_name);
		if ($content) {
			//We need to run the content and parse it's variables
			$content = self::evalContent($template_name, $content, $values);
			//Parse the #Variables# in the content
			$content = self::parseVariables($content, $values);
		}
		return $content;
	}

	/**
	 * This function retrieves the absolute location of a template file
	 *
	 * A template can be defined on three levels (in order of specificity):
	 * + Framework level
	 * + Application level
	 * + Site level
	 * + Theme level
	 * Should a template not exist on Theme level, the Application level template will be used.
	 * If it does not exist on Application level, the Framework level template will be used.
	 * @todo TODO Consider caching the template locations for faster lookups?
	 */
	public static function checkTemplateFile($filename) {
		self::init();
		return Controller::$view->getTemplateLocation($filename);
	}

	/**
	 * Takes a template, and expands all other templates within it.
	 *
	 * @param string The name of a template.
	 * @return string The expanded template.
	 */
	private static function buildTemplate($template) {
		//Get the template file location
		$template_file = self::checkTemplateFile($template);
		if (empty($template_file)) {
			return false;
		}

		$content = false;
		if (self::$do_cache) {
			//Check Cache
			$content = self::getCacheFile($template_file);
		}
		//Build the Tempate
		if (!$content) {
			$content = file_get_contents($template_file);
			//Check for other templates within the template
			while (preg_match_all('/{tpl:(.*\.tpl.php)(|.*)?}/', $content, $templates, PREG_SET_ORDER) && is_array($templates) && count($templates) > 0) {
				foreach ($templates as $temp_arr) {
					$temp_file = $temp_arr[1];
					$inner_content = self::buildTemplate($temp_file);
					if ($inner_content) {
						//Prepend Variables (if any)
						if (!empty($temp_arr[2])) {
							$variable_str  = substr($temp_arr[2], 1);
							$inner_content = '{var:|' . $variable_str . '|}' . $inner_content;
						}
						//Add debugging info and template names
						if (Controller::$debug) {
							 if (!empty($_REQUEST['debug']) && $_REQUEST['debug'] == 'templates') {
								$inner_content = '<code class="template_name">{' . basename($temp_file) . '}</code>' . $inner_content;
							} else {
								$inner_content = '<!--' . basename($temp_file) . '-->' . $inner_content . '<!-- End of ' . basename($temp_file) . '-->';
							}
						}
					}
					//Place the inner content in the original template file
					$content = str_replace($temp_arr[0], $inner_content, $content);
				}
			}
		}
		if ($content && self::$do_cache) {
			$cache_file = self::getCacheFileName($template_file);
			if (!file_exists($cache_file) || array_key_exists('recache', $_REQUEST)) {
				file_put_contents($cache_file, $content);
			}
		}
		return $content;
	}

	/**
	 * Get the cache name of a template file.
	 *
	 * @param string The absolute filename of the template.
	 * @return string Name of the template file, or false if it does not exist.
	 */
	private static function getCacheFilename($template_file) {
		if (is_readable($template_file)) {
			parse_str($_SERVER['QUERY_STRING'], $variables);
			if (array_key_exists('recache', $variables)) {
				unset($variables['recache']);
			}
			if (array_key_exists('debug', $variables)) {
				unset($variables['debug']);
			}
			return self::$cache_folder . md5($_SERVER['SCRIPT_NAME'] . $variables . $template_file) . '.' . filemtime($template_file) . '.php';
		} else {
			Backend::addError('Render::Template does not exist: ' . $template_file);
			return false;
		}
	}

	/**
	 * Get the contents of a cached template file, if it's a valid cache file.
	 *
	 * A cache file is invalid if it's older than specified (currently a day), or we have a recache request
	 *
	 * @param string The absolute name of the template.
	 * @return string The contents of the cached template file.
	 */
	private static function getCacheFile($template_file) {
		$cache_file = self::getCacheFilename($template_file);
		if (!file_exists($cache_file)) {
			return false;
		}
		if (array_key_exists('recache', $_REQUEST)) {
			unlink($cache_file);
			return false;
		}

		//A day previous (Looks weird, but it works.)
		$expire_time = mktime(-1);
		if (filemtime($cache_file) >= $expire_time) {
			return file_get_contents($cache_file);
		} else {
			unlink($cache_file);
			return false;
		}
	}

	/**
	 * This function translates all #Variables# into their Backend values
	 */
	private static function parseVariables($content, array $vars = array()) {
		$vars = array_merge(Backend::getAll(), $vars);
		foreach($vars as $name => $value) {
			if (is_string($name) && is_string($value)) {
				$search[] = self::getTemplateVarName($name);
				$replace[] = $value;
			}
		}
		$toret = str_replace($search, $replace, $content);
		return $toret;
	}

	/**
	 * This function runs a template, making all the Backend variables available as PHP vars
	 *
	 * This isn't used anymore, but should we decide that `eval` is to slow, we can use this again
	 */
	private static function evalTemplate($template, array $vars = array()) {
		if (!file_exists($template)) {
			return false;
		}

		$vars = array_merge(Backend::getAll(), $vars);
		$keys = array_keys($vars);
		//Convert spaces to underscores in Backend Variables
		$keys = array_map(create_function('$elm', "return str_replace(' ', '_', \$elm);"), $keys);

		extract(array_combine($keys, array_values($vars)));
		ob_start();
		include($template);
		return ob_get_clean();
	}

	/**
	 * @todo get a better way to report warnings and errors in eval code
	 */
	private static function evalContent($be_template_name, $be_content, array $be_vars = array()) {
		//Prepare Variables
		$be_vars = array_merge(Backend::getAll(), $be_vars);
		$be_keys = array_keys($be_vars);
		//Convert spaces to underscores in Backend Variables
		$be_keys = array_map(create_function('$elm', "return str_replace(' ', '_', \$elm);"), $be_keys);
		extract(array_combine($be_keys, array_values($be_vars)));

		//Evaluate Extra Variables in Templates
		if (preg_match_all('/{var:\|(.*)\|}/', $be_content, $variable_strings, PREG_SET_ORDER)) {
			foreach($variable_strings as $var_string) {
				//Eval any PHP in the variables
				ob_start();
				eval('?>' . $var_string[1]);
				$variable_string = ob_get_clean();
				$variables = @json_decode($variable_string, true);
				if (is_null($variables)) {
					if (SITE_STATE != 'production') {
						Backend::addError('Invalid Variables passed in ' . $be_template_name);
						if (Controller::$debug) {
							echo 'Invalid Variable String: ' . PHP_EOL . $var_string[1];
						}
					}
				} else {
					$var_content = array();
					foreach($variables as $name => $value) {
						$var_content[] = "\$$name = " . var_export($value, true) . ';';
					}
					$var_content = '<?php' . PHP_EOL . implode($var_content, PHP_EOL) . PHP_EOL . '?>' . PHP_EOL;
					$be_content = str_replace('{var:|' . $var_string[1] . '|}', $var_content, $be_content);
					$did_something = true;
				}
			}
		}

		//Evaluate PHP in Templates
		ob_start();
		if (Controller::$debug) {
			$be_result = eval('?>' . $be_content);
		} else {
			$be_result = @eval('?>' . $be_content);
		}
		if ($be_result === false) {
			Backend::addError('Error evaluating template ' . $be_template_name);
		}
		$result = ob_get_clean();

		return $result;
	}

	public static function getTemplateVarName($name) {
		return '#' . $name . '#';
	}

	public static function install_check() {
		if (!self::checkCacheFolder()) {
			if (function_exists('posix_getgrgid') && function_exists('posix_getegid')) {
				if ($group = posix_getgrgid(posix_getegid())) {
					$group = $group['name'];
				}
			}
			$values = array(
				'folder' => self::$cache_folder,
				'group'  => isset($group) ? $group : false,
			);
			Backend::addContent(Render::renderFile('render.fix_cache_folder.tpl.php', $values));
			return false;
		}
		return true;
	}
}
