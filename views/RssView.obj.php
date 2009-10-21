<?php
/**
 * The file that defines the RssView class.
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
 * Default class to handle RssView specific functions
 */
class RssView extends View {
	function __construct() {
		parent::__construct();
		$this->mode = 'rss';
		$this->mime_type = 'application/xml';
	}
	
	public static function hook_output($to_print) {
		if (!headers_sent()) {
			header('Content-Type: application/xml');
		}
		$to_print = Render::renderFile('rss2.tpl.php');
		return $to_print;
	}

	public static function install() {
		$toret = true;
		$hook = new HookObj();
		$toret = $hook->replace(array(
				'name'        => 'RssView Pre Output',
				'description' => '',
				'mode'        => 'rss',
				'type'        => 'pre',
				'hook'        => 'output',
				'class'       => 'RssView',
				'method'      => 'hook_output',
				'sequence'    => 0,
			)
		) && $toret;
		return $toret;
	}
}

