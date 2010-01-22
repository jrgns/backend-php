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
	
	public static function hook_display($results, $controller) {
		$display_method = Controller::$view->mode . '_' . Controller::$action;
		if (Permission::check(Controller::$action, Controller::$area) && !method_exists($controller, $display_method)) {
			$template_file = Controller::$area . '.' . Controller::$action . '.tpl.php';
			if (Render::checkTemplateFile($template_file)) {
				Controller::addContent(Render::renderFile($template_file, $results));
			}
		}
		return $results;
	}
	
	public static function hook_output($to_print) {
		$to_print = Render::renderFile('index.tpl.php');
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
		}
		$primary   += is_array($app_pri) ? $app_pri : array();
		$secondary += is_array($app_sec) ? $app_sec : array();
		Backend::add('primary_links', $primary);
		Backend::add('secondary_links', $secondary);
		return $data;
	}
	
	public static function install() {
		$toret = true;
		$hook = new HookObj();
		Hook::add('output',  'pre',  __CLASS__, array('mode' => 'html', 'global' => 1)) && $toret;
		Hook::add('display', 'pre',  __CLASS__, array('mode' => 'html', 'global' => 1)) && $toret;
		Hook::add('display', 'post', __CLASS__, array('mode' => 'html', 'global' => 1, 'sequence' => 100)) && $toret;
		return $toret;
	}
}

