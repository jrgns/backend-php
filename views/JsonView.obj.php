<?php
/**
 * The file that defines the JsonView class.
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
 * Default class to handle JsonView specific functions
 */
class JsonView extends TextView {
	private static $ob_level = 0;

	function __construct() {
		parent::__construct();
		$this->mode = 'json';
		self::$ob_level = ob_get_level();
	}
	
	public static function hook_init() {
		ob_start();
	}

	public static function hook_output($to_print) {
		$object = new stdClass();
		$object->result  = $to_print;
		$object->error   = Backend::getError();
		$object->notice  = Backend::getNotice();
		$object->success = Backend::getSuccess();
		$object->content = Backend::getContent();
		$last = '';
		while (ob_get_level() > self::$ob_level) {
			//Ending the ob_start from HtmlView::hook_init
			$last .= ob_get_clean();
		}
		$object->output  = $last;
		Backend::setError();
		Backend::setNotice();
		Backend::setSuccess();
		return json_encode($object);
	}

	public static function install() {
		$toret = true;
		Hook::add('init', 'pre', __CLASS__, array('global' => 1, 'mode' => 'json')) && $toret;
		return $toret;
	}
}
