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

	public static function createTemplate($destination, $origin) {
		$template_file    = self::buildTemplate($origin);
		$template_content = self::evalTemplate($template_file);
		$template_loc     = Backend::getConfig('backend.templates.location', 'templates');
		$dest_file        = APP_FOLDER . '/' . $template_loc . '/' . $destination;
		if (file_put_contents($dest_file, $template_content)) {
			if (SITE_STATE != 'production') {
				chmod($dest_file, 0664);
			}
			return true;
		}
		return false;
	}

	public static function renderFile($filename, array $values = array()) {
		//Build the template
		$cache_file = self::buildTemplate($filename);
		//Run the PHP in the template
		$toret = self::evalTemplate($cache_file, $values);
		//Parse the #Variables# in the template
		$toret = self::parseVariables($toret, $values);
		return $toret;
	}
	
	/**
	 * This function retrieves the absolute location of a template file
	 *
	 * A template can be defined on three levels (in order of specificity):
	 * + Framework level
	 * + Application level
	 * + Theme level
	 * Should a template not exist on Theme level, the Application level template will be used.
	 * If it does not exist on Application level, the Framework level template will be used.
	 * @todo TODO Consider caching the template locations for faster lookups?
	 */
	public static function checkTemplateFile($filename) {
		$toret = false;
		$template_loc = Backend::getConfig('backend.templates.location', 'templates');
		if (Component::isActive('Theme')) {
			$theme = Theme::get();
		} else {
			$theme = false;
		}

		//Check the theme first.
		if ($theme && is_readable($theme['path'] . '/' . $filename)) {
			$toret = $theme['path'] . '/' . $filename;
		} else if (is_readable(APP_FOLDER . '/' . $template_loc . '/' . $filename)) {
			$toret = APP_FOLDER . '/'. $template_loc . '/' . $filename;
		} else if (is_readable(BACKEND_FOLDER . '/' . $template_loc . '/' . $filename)) {
			$toret = BACKEND_FOLDER . '/'. $template_loc . '/' . $filename;
		}
		return $toret;
	}

	private static function buildTemplate($filename) {
		$filename = self::checkTemplateFile($filename);
		$toret = false;
		if ($filename) {
			$cached_file = self::getCacheFile($filename);
			$cache_filename = self::getCacheFilename($filename);
			if ($cached_file) {
				$toret = true;
			} else {
				$content = file_get_contents($filename);
				//Check for other templates within the template
				while (preg_match_all('/{tpl:(.*\.tpl.php)}/', $content, $templates, PREG_SET_ORDER) && is_array($templates) && count($templates) > 0) {
					foreach ($templates as $temp_arr) {
						$temp_file = $temp_arr[1];
						$inner_filename = self::buildTemplate($temp_file);
						if ($inner_filename) {
							$inner_content = file_get_contents($inner_filename);
						} else {
							$inner_content = '<!--Missing Template-->';
							//Controller::addError('Unknown Inner Template: ' . $temp_file);
						}
						if (Controller::$debug) {
							 if (!empty($_REQUEST['debug']) && $_REQUEST['debug'] == 'templates') {
								$inner_content = '<code class="template_name">{' . basename($temp_file) . '}</code>' . $inner_content;
							} else {
								$inner_content = '<!--' . basename($temp_file) . '-->' . $inner_content . '<!-- End of ' . basename($temp_file) . '-->';
							}
						}
						$content = str_replace($temp_arr[0], $inner_content, $content);
					}
				}
				if (is_writable(SITE_FOLDER . '/cache/')) {
					file_put_contents($cache_filename, $content);
					if (Controller::$debug) {
						var_dump('Render::Written Cache file for ' . $cache_filename);
					}
					$toret = true;
				} else {
					if (Controller::$debug) {
						var_dump($cache_filename);
					}
					die('Render::Cache folder unwritable (' . SITE_FOLDER . '/cache/' . ')');
				}
			}
		}
		return $toret ? $cache_filename : false;
	}

	private static function getCacheFilename($filename) {
		$toret = false;
		if (is_readable($filename)) {
			parse_str($_SERVER['QUERY_STRING'], $variables);
			if (array_key_exists('recache', $variables)) {
				unset($variables['recache']);
			}
			if (array_key_exists('nocache', $variables)) {
				unset($variables['nocache']);
			}
			$toret = SITE_FOLDER . '/cache/' . md5($_SERVER['SCRIPT_NAME'] . $variables . $filename) . '.' . filemtime($filename) . '.php';
		} else {
			var_dump('Render::Template does not exist');
		}
		return $toret;
	}

	private static function getCacheFile($filename) {
		$toret = false;

		switch (true) {
			case array_key_exists('nocache', $_REQUEST):
			case array_key_exists('HTTP_PRAGMA', $_SERVER) && $_SERVER['HTTP_PRAGMA'] == 'no-cache':
			case array_key_exists('HTTP_CACHE_CONTROL', $_SERVER) && in_array($_SERVER['HTTP_CACHE_CONTROL'], array('no-cache', 'max-age=0')):
				self::$do_cache = false;
				break;
		}

		if (Backend::getConfig('backend.application.renderer.use_cache', true) && self::$do_cache) {
			//Check the cache folder
			if (!file_exists(BACKEND_FOLDER . '/cache/')) {
				mkdir(BACKEND_FOLDER . '/cache/', 0755);
			}
				$cache_file = self::getCacheFilename($filename);
			if (file_exists($cache_file)) {
				if (array_key_exists('recache', $_REQUEST)) {
					var_dump('Render::Recaching File');
					unlink($cache_file);
				} else {
					//A day previous (Looks weird, but it works.)
					$expire_time = mktime(-1);
					if (filemtime($cache_file) >= $expire_time) {
						$toret = file_get_contents($cache_file);
					} else {
						unlink($cache_file);
					}
				}
			}
		}
		return $toret;
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
	 */
	private static function evalTemplate($template, array $vars = array()) {
		$toret = false;
		if (file_exists($template)) {
			$vars = array_merge(Backend::getAll(), $vars);

			$keys = array_keys($vars);
			$keys = array_map(create_function('$elm', "return str_replace(' ', '_', \$elm);"), $keys);
			extract(array_combine($keys, array_values($vars)));
			ob_start();
			include($template);
			$toret = ob_get_clean();
		}
		return $toret;
	}

	public static function getTemplateVarName($name) {
		return '#' . $name . '#';
	}
}
