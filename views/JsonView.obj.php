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
		$this->mode     = 'json';
		self::$ob_level = ob_get_level();
		ob_start();
	}

	public static function hook_output($to_print) {
		//Construct the object to output
		$object = new stdClass();
		$object->result  = $to_print;
		$object->error   = Backend::getError();
		$object->notice  = Backend::getNotice();
		$object->success = Backend::getSuccess();
		$object->info    = Backend::getInfo();
		$object->content = Backend::getContent();
		$last = '';
		while (ob_get_level() > self::$ob_level) {
			//Ending the ob_start from __construct
			$last .= ob_get_clean();
		}
		$object->output  = $last;

		//Clean up
		Backend::setError();
		Backend::setNotice();
		Backend::setSuccess();
		Backend::setInfo();
		//$result_only = Controller::getVar('result_only');
		return json_encode($object);
	}

	public static function install() {
		$toret = true;
		return $toret;
	}
}
