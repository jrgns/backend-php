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
	private static $ob_level = 0;

	function __construct() {
		$this->mode      = 'html';
		$this->mime_type = 'text/html';
		$this->charset   = 'utf-8';
		self::$ob_level  = ob_get_level();
		ob_start();
	}

	/**
	 * See if there's a template file that can be automatically added.
	 *
	 * This only happens when:
	 * 1. The user has permission to access this query
	 * 2. There is not display method present in the controller
	 * 3. The correct template file is present
	 */
	public static function hook_display($results, $controller) {
		$display_method = Controller::$view->mode . '_' . Controller::$action;
		if ($controller->checkPermissions() && !method_exists($controller, $display_method)) {
			$template_file = Controller::$area . '.' . Controller::$action . '.tpl.php';
			if (Render::checkTemplateFile($template_file)) {
				$results = is_array($results) ? $results : array('results' => $results);
				Backend::addContent(Render::file($template_file, $results));
			}
		}
		$sub_title = Backend::get('Sub Title');
		if (empty($sub_title)) {
			Backend::add('Sub Title', $controller->getHumanName());
		}
		return $results;
	}

	public static function hook_output($to_print) {
		Backend::add('BackendErrors', Backend::getError());
		Backend::add('BackendSuccess', Backend::getSuccess());
		Backend::add('BackendNotices', Backend::getNotice());
		Backend::add('BackendInfo', Backend::getInfo());
		Backend::setError();
		Backend::setSuccess();
		Backend::setNotice();
		Backend::setInfo();

		$content = Backend::getContent();
		if (empty($content)) {
			ob_start();
			var_dump($to_print);
			$content = ob_get_clean();
			if (substr($content, 0, 4) != '<pre') {
				$content = '<pre>' . $content . '</pre>';
			}
			Backend::addContent($content);
		}

		$layout   = Backend::get('HTMLLayout', 'index');
		if (!Render::checkTemplateFile($layout . '.tpl.php')) {
		    if (SITE_STATE != 'production') {
		        Backend::addError('Missing Layout ' . $layout);
		    }
		    $layout = 'index';
		}
		$to_print = Render::file($layout . '.tpl.php');

		$to_print = self::addLastContent($to_print);
		$to_print = self::replace($to_print);
		$to_print = self::rewriteLinks($to_print);
		$to_print = self::addLinks($to_print);
		$to_print = self::formsAcceptCharset($to_print);

		//TODO fix this
		if (Component::isActive('BackendFilter')) {
			$BEFilter = new BEFilterObj();
			$BEFilter->read();
			$filters = $BEFilter->list ? $BEFilter->list : array();

			foreach($filters as $row) {
				if (class_exists($row['class'], true) && is_callable(array($row['class'], $row['function']))) {
					$to_print = call_user_func(array($row['class'], $row['function']), $to_print);
				}
			}
		}

		//TODO Make this configurable
		if (ConfigValue::get('html_view.TidyHTML') && function_exists('tidy_repair_string')) {
		    $to_print = tidy_repair_string($to_print);
	    }

		return $to_print;
	}

	/**
	 * This function adds all styles and scripts to the HTML. It also retrieves primary and secondary links from the App
	 *
	 */
	public static function hook_post_display($data, $controller) {
		Backend::addScript(SITE_LINK . '/js/backend.js');
		//TODO Add site_link, and other vars, as JS vars
		Backend::addScriptContent('var site_link = \'' . SITE_LINK . '\';');
		//TODO if someone can land a script file in the correct place, he can insert JS at will...
		$comp_script = '/js/' . Controller::$area . '.component.js';
		$comp_style  = '/css/' . Controller::$area . '.component.css';
		if (file_exists(WEB_FOLDER . $comp_script)) {
			Backend::addScript(SITE_LINK . $comp_script);
		}
		if (file_exists(WEB_FOLDER . $comp_style)) {
			Backend::addStyle(SITE_LINK . $comp_style);
		}

		//Make sure that jquery and backend is right at the top
		$scripts = array_unique(array_filter(Backend::getScripts()));
		$against = array();
		if (in_array(SITE_LINK . '/js/jquery.js', $scripts)) {
			$against[] = SITE_LINK . '/js/jquery.js';
		}
		if (in_array(SITE_LINK . '/js/backend.js', $scripts)) {
			$against[] = SITE_LINK . '/js/backend.js';
		}
		$scripts = array_unique(array_merge($against, $scripts));

		Backend::add('Styles', array_unique(array_filter(Backend::getStyles())));
		Backend::add('Scripts', $scripts);
		Backend::add('ScriptContent', array_unique(array_filter(Backend::getScriptContent())));
		$primary = Links::get('primary');
		$secondary = Links::get('secondary');
		$tertiary = Links::get('tertiary');

		$app_class = ConfigValue::get('settings.Class', 'Application');
		if (class_exists($app_class, true) && method_exists($app_class, 'getLinks')) {
			$app_pri = call_user_func(array($app_class, 'getLinks'), 'primary');
			$app_sec = call_user_func(array($app_class, 'getLinks'), 'secondary');
			$app_tri = call_user_func(array($app_class, 'getLinks'), 'tertiary');
		} else {
			$app_pri = false;
			$app_sec = false;
			$app_tri = false;
		}
		$primary   = array_merge($primary, is_array($app_pri)   ? $app_pri : array());
		$secondary = array_merge($secondary, is_array($app_sec) ? $app_sec : array());
		$tertiary  = array_merge($tertiary, is_array($app_tri)  ? $app_tri : array());
		Backend::add('primary_links', $primary);
		Backend::add('secondary_links', $secondary);
		Backend::add('tertiary_links', $tertiary);
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

			//Allways have a Sub Title?
			if (empty($vars['Sub Title'])) {
				array_unshift($search, ' - ' . Render::getTemplateVarName('Sub Title'));
				array_unshift($replace, '');
			}
			$toret = str_replace($search, $replace, $content);
		}
		return $toret;
	}

	public static function addLastContent($to_print) {
		//Checking for ob_level > $this->ob_level, so we'll exit on the same number we started on
		$last = '';
		while (ob_get_level() > self::$ob_level) {
			//Ending the ob_start from __construct
			$last .= ob_get_clean();
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
		if (ConfigValue::get('CleanURLs', false)) {
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
					if (empty($query['path'])) {
						$query['path'] = SITE_LINK;
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
						if (substr($query['path'], -1) == '/') {
						    $query['path'] = substr($query['path'], 0, strlen($query['path']) - 1);
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

	public static function formsAcceptCharset($content, $charset = 'utf-8') {
		return str_replace('<form ', '<form accept-charset="' . $charset . '" ', $content);
	}

	public function whoops($title, $message, $code_hint = false) {
		Backend::add('Sub Title', $title);
		Backend::addContent('<ht>' . $message . '<hr>');
		parent::whoops($title, $message, $code_hint);
	}

	public static function install() {
		if (!Backend::getDB('default')) {
			return true;
		}
		$result = true;
		return $result;
	}
}
