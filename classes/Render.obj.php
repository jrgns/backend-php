<?php
/**
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
class Render {
	public static $do_cache = true;

	public static function renderFile($filename) {
		$cache_file = self::buildTemplate($filename);
		$toret = self::evalTemplate($cache_file);
		return $toret;
	}
	
	public static function checkTemplateFile($filename) {
		$toret = false;
		if (is_readable($filename)) {
			$toret = $filename;
		} else if (is_readable(BACKEND_FOLDER . '/' . $filename)) {
			$toret = BACKEND_FOLDER . '/'. $filename;
		} else if (is_readable(APP_FOLDER . '/' . $filename)) {
			$toret = APP_FOLDER . '/'. $filename;
		}
		return $toret;
	}

	private static function buildTemplate($filename) {
		$filename = self::checkTemplateFile($filename);
		$toret = false;
		if ($filename) {
			$cache_filename = self::getCacheFilename($filename);
			$toret = self::getCacheFile($filename);
			if (!$toret) {
				$toret = file_get_contents($filename);
				while (preg_match_all('/{tpl:(.*\.tpl.php)}/', $toret, $templates) && is_array($templates) && count($templates) == 2) {
					foreach ($templates[1] as $key => $temp_file) {
						$content = self::decodeTemplate($temp_file);
						if (Controller::$debug) {
							 if (!empty($_REQUEST['debug']) && $_REQUEST['debug'] == 'templates') {
								$content = '<code class="template_name">{' . basename($temp_file) . '}</code>' . $content;
							} else {
								$content = '<!--' . basename($temp_file) . '-->' . $content . '<!-- End of ' . basename($temp_file) . '-->';
							}
						}
						$toret = str_replace($templates[0][$key], $content, $toret);
					}
				}
				if (is_writable(SITE_FOLDER . '/cache/')) {
					file_put_contents($cache_filename, $toret);
				} else {
					if (Controller::$debug) {
						var_dump($cache_filename);
					}
					die('Cache folder unwritable');
				}
			}
		}
		return $toret ? $cache_filename : false;
	}

	private static function getCacheFilename($filename) {
		$toret = false;
		if (is_readable($filename)) {
			$toret = SITE_FOLDER . '/cache/' . md5($_SERVER['SCRIPT_NAME'] . $_SERVER['QUERY_STRING'] . $filename) . '.' . filemtime($filename) . '.php';
		} else {
			Controller::addError('Template does not exist');
		}
		return $toret;
	}

	private static function getCacheFile($filename) {
		$toret = false;

		switch (true) {
			case array_key_exists('HTTP_PRAGMA', $_SERVER) && $_SERVER['HTTP_PRAGMA'] == 'no-cache':
			case array_key_exists('HTTP_CACHE_CONTROL', $_SERVER) && $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache':
				self::$do_cache = false;
				break;
		}

		if (Backend::getConfig('backend.application.renderer.use_cache', true)) {
			//Check the cache folder
			if (!file_exists(BACKEND_FOLDER . '/cache/')) {
				mkdir(BACKEND_FOLDER . '/cache/', 0755);
			}
			$cache_file = self::getCacheFilename($filename);
			if (file_exists($cache_file)) {
				if (array_key_exists('recache', $_REQUEST)) {
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

	private static function decodeTemplate($filename) {
		$toret = '';
		$filename = self::checkTemplateFile($filename);
		if ($filename) {
			$content = file_get_contents($filename);
			if ($content) {
				$vars = Backend::getAll();
				foreach($vars as $name => $value) {
					if (is_string($name) && is_string($value)) {
						$search[] = self::getTemplateVarName($name);
						$replace[] = $value;
					}
				}
				$toret = str_replace($search, $replace, $content);
			}
		}
		return $toret;
	}

	private static function evalTemplate($template, $vars = false) {
		$toret = false;
		if (file_exists($template)) {
			$vars = $vars ? $vars : Backend::getAll();
			extract($vars);
			ob_start();
			include($template);
			$toret = ob_get_clean();
		}
		return $toret;
	}

	private static function getTemplateVarName($name) {
		return '#' . $name . '#';
	}
	
	public static function shouldUseCache() {
		$toret = true;
		switch (true) {
			case array_key_exists('HTTP_PRAGMA', $_SERVER) && $_SERVER['HTTP_PRAGMA'] == 'no-cache':
			case array_key_exists('HTTP_CACHE_CONTROL', $_SERVER) && $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache':
			case array_key_exists('nocache', $_REQUEST):
				$toret = false;
				break;
		}
		return $toret;
	}

	public static function runFilters($content) {
		$toret = $content;
		$BEFilter = new BEFilterObj();
		$BEFilter->load();
		$filters = $BEFilter->list ? $BEFilter->list : array();
		//Standard Filters
		$filters += array(
			array('class' => 'Render', 'function' => 'replace'),
			array('class' => 'Render', 'function' => 'addLinks'),
		);

		//Added Filters
		if (count($filters)) {
			foreach($filters as $row) {
				if (class_exists($row['class'], true) && method_exists($row['class'], $row['function'])) {
					$toret = call_user_func(array($row['class'], $row['function']), $toret);
				}
			}
		}
		return $toret;
	}
	
	protected static function replace($content) {
		$toret = $content;
	
		$vars = Backend::getAll();
		$search = array();
		$replace = array();
		
		if ($vars) {
			foreach($vars as $name => $value) {
				if (!(is_object($name) || is_array($name) || is_null($name)) && !(is_object($value)  || is_array($value))) {
					$var_name = self::getTemplateVarName($name);
					$search[] = $var_name;
					if (Controller::$debug && !in_array($var_name, array('#SITE_LINK#')) && !empty($_REQUEST['debug']) && $_REQUEST['debug'] == 'var_names') {
						$replace[] = '<code class="var_name">{' . $var_name . '}</code>' . $value;
					} else {
						$replace[] = $value;
					}
				}
			}

			if (empty($vars['Sub Title'])) {
				array_unshift($search, ' - ' . self::getTemplateVarName('Sub Title'));
				array_unshift($replace, '');
			}
			$toret = str_replace($search, $replace, $content);
		}
		return $toret;
	}
	
	protected static function addLinks($content) {
		parse_str($_SERVER['QUERY_STRING'], $vars);
		$new_vars = array();
		if (array_key_exists('debug', $vars)) {
			$new_vars['debug'] = $vars['debug'];
		}
		if (array_key_exists('nocache', $vars)) {
			$new_vars['nocache'] = '';
		}
		if (array_key_exists('recache', $vars)) {
			$new_vars['recache'] = '';
		}
		$toret = update_links($content, $new_vars);
		//$toret = $content;
		return $toret;
	}
}
