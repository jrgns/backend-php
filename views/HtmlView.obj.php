<?php
/**
 * The file that defines the HtmlView class.
 *
 * @author J Jurgens du Toit (JadeIT cc) <jurgens.dutoit@gmail.com> - initial API and implementation
 * @copyright Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available {@link http://www.eclipse.org/legal/epl-v10.html here}
 * @license http://www.eclipse.org/legal/epl-v10.html Eclipse Public License v1.0
 * @package View
 */
 
/**
 * Default class to handle HtmlView specific functions
 */
class HtmlView extends View {
	function __construct() {
		parent::__construct();
		$this->mode      = 'html';
		$this->mime_type = 'text/html';
		$this->charset   = 'utf-8';
	}
	
	public static function hook_init() {
		ob_start();
	}
	
	public static function hook_display($results, $controller) {
		$display_method = Controller::$view->mode . '_' . Controller::$action;
		if (Permission::check(Controller::$action, Controller::$area) && !method_exists($controller, $display_method)) {
			$template_file = Controller::$area . '.' . Controller::$action . '.tpl.php';
			if (Render::checkTemplateFile($template_file)) {
				Controller::addContent(Render::renderFile($template_file, $results));
			}
		}
		$comp_script = '/scripts/' . Controller::$area . '.component.js';
		$comp_style  = '/styles/' . Controller::$area . '.component.css';
		if (file_exists(SITE_FOLDER . $comp_script)) {
			Controller::addScript(SITE_LINK . $comp_script);
		}
		if (file_exists(SITE_FOLDER . $comp_style)) {
			Controller::addStyle(SITE_LINK . $comp_style);
		}
		return $results;
	}
	
	public static function hook_output($to_print) {
		Backend::add('BackendErrors', array_unique(array_filter(Controller::getError())));
		Backend::add('BackendSuccess', array_unique(array_filter(Controller::getSuccess())));
		Backend::add('BackendNotices', array_unique(array_filter(Controller::getNotice())));
		Controller::setError();
		Controller::setSuccess();
		Controller::setNotice();

		$to_print = Render::renderFile('index.tpl.php');

		$to_print = self::addLastContent($to_print);
		$to_print = self::replace($to_print);
		$to_print = self::rewriteLinks($to_print);
		$to_print = self::addLinks($to_print);

		if (Value::get('admin_installed', false)) {
			$BEFilter = new BEFilterObj();
			$BEFilter->load();
			$filters = $BEFilter->list ? $BEFilter->list : array();
		
			foreach($filters as $row) {
				if (class_exists($row['class'], true) && is_callable(array($row['class'], $row['function']))) {
					$to_print = call_user_func(array($row['class'], $row['function']), $to_print);
				}
			}
		}

		return $to_print;
	}
	
	/**
	 * This function adds all styles and scripts to the HTML. It also retrieves primary and secondary links from the App
	 *
	 */
	public static function hook_post_display($data, $controller) {
		Controller::addScript(SITE_LINK . 'scripts/backend.js');
		Backend::add('Styles', array_unique(array_filter(Controller::getStyles())));
		Backend::add('Scripts', array_unique(array_filter(Controller::getScripts())));
		$app_class = Backend::getConfig('backend.application.class', 'Application');
		$primary = Links::get('primary');
		$secondary = Links::get('secondary');
		if (class_exists($app_class, true) && method_exists($app_class, 'getLinks')) {
			$app_pri = call_user_func(array($app_class, 'getLinks'), 'primary');
			$app_sec = call_user_func(array($app_class, 'getLinks'), 'secondary');
		} else {
			$app_pri = false;
			$app_sec = false;
		}
		$primary   += is_array($app_pri) ? $app_pri : array();
		$secondary += is_array($app_sec) ? $app_sec : array();
		Backend::add('primary_links', $primary);
		Backend::add('secondary_links', $secondary);
		return $data;
	}

	public static function replace($content) {
		$toret = $content;
	
		$vars = Backend::getAll();
		$search = array();
		$replace = array();
		
		if ($vars) {
			foreach($vars as $name => $value) {
				if (!(is_object($name) || is_array($name) || is_null($name)) && !(is_object($value)  || is_array($value))) {
					$var_name = Render::getTemplateVarName($name);
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
	
	private static function addLastContent($to_print) {
		//Checking for ob_level > 1, as we're using ob_gzhandler
		if (ob_get_level() > 1) {
			//Ending the ob_start from HtmlView::hook_init
			$last = ob_get_clean();
		} else {
			$last = '';
		}
		$start = Backend::get('start');
		$time = microtime(true) - $start;
		$last = 'Generated on ' . date('Y-m-d H:i:s') . ' in ' . number_format($time, 4) . ' seconds' . $last;
		$to_print = str_replace('#Last Content#', $last, $to_print);
		return $to_print;
	}
	
	public static function addLinks($to_print) {
		parse_str($_SERVER['QUERY_STRING'], $vars);
		$new_vars = array();
		if (array_key_exists('debug', $vars)) {
			$new_vars['debug'] = $vars['debug'];
		}
		if (array_key_exists('nocache', $vars)) {
			$new_vars['nocache'] = $vars['nocache'];
		}
		if (array_key_exists('recache', $vars)) {
			$new_vars['recache'] = $vars['recache'];
		}
		/*if (array_key_exists('mode', $vars)) {
			$new_vars['mode'] = $vars['mode'];
		}*/
		$to_print = update_links($to_print, $new_vars);
		return $to_print;
	}
	
	public static function rewriteLinks($to_print) {
		if (Value::get('clean_urls', false)) {
			preg_match_all('/(<a\s+.*?href=[\'\"]|<form\s+.*?action=[\'"]|<link\s+.*?href=[\'"])(|.*?[\?&]q=.*?&?.*?)[\'"]/', $to_print, $matches);
			if (count($matches) == 3) {
				$matched = $matches[0];
				$links = $matches[1];
				$urls = $matches[2];
				$replacements = array();
				foreach ($urls as $key => $url) {
					if (empty($url)) {
						$url = get_current_url();
					}
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
				$to_print = str_replace($matched, $replacements, $to_print);
			}
		}
		return $to_print;
	}

	public static function install() {
		$toret = true;
		Hook::add('init', 'pre', __CLASS__, array('global' => 1, 'mode' => 'html')) && $toret;
		return $toret;
	}
}

