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

	public static function renderFile($filename) {
		//Build the template
		$cache_file = self::buildTemplate($filename);
		//Run the PHP in the template
		$toret = self::evalTemplate($cache_file);
		//Parse the #Variables# in the template
		$toret = self::parseVariables($toret);
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
		$theme = Theme::get();

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
					die('Render::Cache folder unwritable');
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
	private static function parseVariables($content, $vars = false) {
			$vars = $vars ? $vars : Backend::getAll();
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
	private static function evalTemplate($template, $vars = false) {
		$toret = false;
		if (file_exists($template)) {
			$vars = $vars ? $vars : Backend::getAll();

			$keys = array_keys($vars);
			$keys = array_map(create_function('$elm', "return str_replace(' ', '_', \$elm);"), $keys);
			extract(array_combine($keys, array_values($vars)));
			ob_start();
			include($template);
			$toret = ob_get_clean();
		}
		return $toret;
	}

	private static function getTemplateVarName($name) {
		return '#' . $name . '#';
	}
	
	public static function hook_output($content) {
		$toret = $content;
		$BEFilter = new BEFilterObj();
		$BEFilter->load();
		$filters = $BEFilter->list ? $BEFilter->list : array();
		
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
	
	protected static function rewriteLinks($content) {
		$toret = $content;
		if (Value::get('clean_urls', false)) {
			preg_match_all('/(<a\s+.*?href=[\'\"]|<form\s+.*?action=[\'"]|<link\s+.*?href=[\'"])(.*?[\?&]q=.*?&?.*?)[\'"]/', $toret, $matches);
			if (count($matches) == 3) {
				$matched = $matches[0];
				$links = $matches[1];
				$urls = $matches[2];
				$replacements = array();
				foreach ($urls as $key => $url) {
					//Build query array
					//workaround for parse_url acting funky with a url = ?q=something/another/
					if (substr($url, 0, 3) == '?q=') {
						$query = array('query' => substr($url, 1));
					} else {
						$query = parse_url($url);
					}
					if (!array_key_exists('path', $query)) {
						$query['path'] = dirname($_SERVER['SCRIPT_NAME']);
					}
					if (substr($query['path'], -1) != '/') {
						$query['path'] .= '/';
					}
					if (array_key_exists('scheme', $query)) {
						$query['scheme'] = $query['scheme'] . '://';
					}
					
					//Get the old vars
					if (array_key_exists('query', $query)) {
						parse_str($query['query'], $vars);
					} else {
						$vars = array();
					}
					
					//append q to the URL
					if (array_key_exists('q', $vars)) {
						$query['path'] .= $vars['q'];
						unset($vars['q']);
						if (substr($query['path'], -1) != '/') {
							$query['path'] .= '/';
						}
					}
					
					//Create query string
					if (count($vars)) {
						$query['query'] = '?' . http_build_query($vars);
					} else {
						$query['query'] = '';
					}
					$to_rep = $links[$key] . $query['path'] . $query['query'] . '"';
					$replacements[] = $to_rep;
				}
				$toret = str_replace($matched, $replacements, $toret);
			}
		}
		return $toret;
	}

	public static function install() {
		$toret = true;
		Hook::add('output', 'pre', __CLASS__, array('global' => 1, 'sequence' => 1000)) && $toret;
		
		$filter = new BEFilterObj();
		$toret = $filter->replace(array(
				'name' => 'System Replace',
				'description' => 'Replace system variables with their values',
				'class' => 'Render',
				'function' => 'replace',
				'options' => '',
			)
		) && $toret;
		$toret = $filter->replace(array(
				'name' => 'System Links',
				'description' => 'Update Links...',
				'class' => 'Render',
				'function' => 'addLinks',
				'options' => '',
			)
		) && $toret;
		$toret = $filter->replace(array(
				'name' => 'System Rewrite Links',
				'description' => 'Rewrite Links...',
				'class' => 'Render',
				'function' => 'rewriteLinks',
				'options' => '',
			)
		) && $toret;
		return $toret;
	}
}
