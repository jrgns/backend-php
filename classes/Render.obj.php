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
			self::$do_cache = Backend::getConfig('backend.application.renderer.use_cache', true);
			
			if (self::$do_cache) {
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
						self::$do_cache = false;
						self::$init = true;
						return;
					}
				}

				if (!is_writable(self::$cache_folder)) {
					self::$do_cache = false;
					if (SITE_STATE != 'production') {
						Backend::addError('Render::Cache folder unwritable (' . self::$cache_folder . ')');
					} else {
						Backend::addError('Render::Cache folder unwritable');
					}
				}
			}
			self::$init = true;
		}
	}

	public static function createTemplate($destination, $origin) {
		self::init();
		
		$template_content = self::buildTemplate($origin);
		$template_content = self::evalContent($origin, $template_content);
		if (!$template_content) {
			Backend::addError('Could not generate template');
			return false;
		}
		$template_loc     = Backend::getConfig('backend.templates.location', 'templates');
		$dest_file        = APP_FOLDER . '/' . $template_loc . '/' . $destination;
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

		$mobile = false;
		if (Component::isActive('Wurfl')) {
			$device = Wurfl::getDevice();
			if (($device && $device->getCapability('mobile_browser') != '') || array_key_exists('mobile', $_REQUEST)) {
				$mobile = true;
			}
		}

		$toret = false;
		$template_loc = Backend::getConfig('backend.templates.location', 'templates');
		if (Component::isActive('Theme')) {
			$theme = Theme::get();
		} else {
			$theme = false;
		}

		//Mobile and Theme
		if ($mobile && $theme && is_readable($theme['path'] . '/mobile_' . $filename)) {
			$toret = $theme['path'] . '/mobile_' . $filename;
		//Theme
		} else if ($theme && is_readable($theme['path'] . '/' . $filename)) {
			$toret = $theme['path'] . '/' . $filename;
		//Mobile and SITE FOLDER
		} else if ($mobile && defined('SITE_FOLDER') && is_readable(SITE_FOLDER . '/' . $template_loc . '/mobile_' . $filename)) {
			$toret = SITE_FOLDER . '/'. $template_loc . '/mobile_' . $filename;
		//SITE FOLDER
		} else if (defined('SITE_FOLDER') && is_readable(SITE_FOLDER . '/' . $template_loc . '/' . $filename)) {
			$toret = SITE_FOLDER . '/'. $template_loc . '/' . $filename;
		//Mobile and APP FOLDER
		} else if ($mobile && is_readable(APP_FOLDER . '/' . $template_loc . '/mobile_' . $filename)) {
			$toret = APP_FOLDER . '/'. $template_loc . '/mobile_' . $filename;
		//APP FOLDER
		} else if (is_readable(APP_FOLDER . '/' . $template_loc . '/' . $filename)) {
			$toret = APP_FOLDER . '/'. $template_loc . '/' . $filename;
		//Mobile and BACKEND_FOLDER
		} else if ($mobile && is_readable(BACKEND_FOLDER . '/' . $template_loc . '/mobile_' . $filename)) {
			$toret = BACKEND_FOLDER . '/'. $template_loc . '/mobile_' . $filename;
		//BACKEND_FOLDER
		} else if (is_readable(BACKEND_FOLDER . '/' . $template_loc . '/' . $filename)) {
			$toret = BACKEND_FOLDER . '/'. $template_loc . '/' . $filename;
		}
		return $toret;
	}

	/**
	 * Takes a template, and expands all other templates within it.
	 *
	 * @param string The name of a template.
	 * @return string The expanded template.
	 */
	private static function buildTemplate($template) {
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
			while (preg_match_all('/{tpl:(.*\.tpl.php)}/', $content, $templates, PREG_SET_ORDER) && is_array($templates) && count($templates) > 0) {
				foreach ($templates as $temp_arr) {
					$temp_file = $temp_arr[1];
					$inner_content = self::buildTemplate($temp_file);
					if ($inner_content) {
						if (Controller::$debug) {
							 if (!empty($_REQUEST['debug']) && $_REQUEST['debug'] == 'templates') {
								$inner_content = '<code class="template_name">{' . basename($temp_file) . '}</code>' . $inner_content;
							} else {
								$inner_content = '<!--' . basename($temp_file) . '-->' . $inner_content . '<!-- End of ' . basename($temp_file) . '-->';
							}
						}
					}
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
		$keys = array_map(create_function('$elm', "return str_replace(' ', '_', \$elm);"), $keys);

		extract(array_combine($keys, array_values($vars)));
		ob_start();
		include($template);
		return ob_get_clean();
	}

	/**
	 * @todo get a better way to report warnings and errors in eval code
	 */
	private static function evalContent($template_name, $content, array $vars = array()) {
		$vars = array_merge(Backend::getAll(), $vars);
		$keys = array_keys($vars);
		$keys = array_map(create_function('$elm', "return str_replace(' ', '_', \$elm);"), $keys);

		extract(array_combine($keys, array_values($vars)));
		ob_start();
		if (Controller::$debug) {
			$result = eval('?>' . $content);
		} else {
			$result = @eval('?>' . $content);
		}
		if ($result === false) {
			Backend::addError('Error evaluating template ' . $template_name);
		}
		return ob_get_clean();
	}

	public static function getTemplateVarName($name) {
		return '#' . $name . '#';
	}
}
