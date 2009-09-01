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
		$this->mode = 'html';
	}
	
	public static function hook_output($to_print) {
		if (!headers_sent()) {
			header('Content-Type: text/html');
		}
		$to_print = Render::renderFile('templates/index.tpl.php');
		return $to_print;
	}
	
	public static function hook_post_display($data, $controller) {
		Controller::addScript(SITE_LINK . '/scripts/backend.js');
		Backend::add('Styles', array_unique(array_filter(Controller::getStyles())));
		Backend::add('Scripts', array_unique(array_filter(Controller::getScripts())));
		$app_class = Backend::getConfig('backend.application.class', 'Application');
		if (class_exists($app_class, true) && method_exists($app_class, 'getLinks')) {
			Backend::add('links', call_user_func(array($app_class, 'getLinks')));
		}
		return $data;
	}
	
	public static function install() {
		$toret = true;
		$hook = new HookObj();
		$toret = $hook->replace(array(
				'name'        => 'HtmlView Pre Display',
				'description' => '',
				'mode'        => 'html',
				'type'        => 'pre',
				'hook'        => 'display',
				'class'       => 'HtmlView',
				'method'      => 'hook_display',
				'sequence'    => '100',
			)
		) && $toret;
		$toret = $hook->replace(array(
				'name'        => 'HtmlView Post Display',
				'description' => '',
				'mode'        => 'html',
				'type'        => 'post',
				'hook'        => 'display',
				'class'       => 'HtmlView',
				'method'      => 'hook_post_display',
				'sequence'    => '100',
			)
		) && $toret;
		return $toret;
	}
}

