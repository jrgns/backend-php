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
	function __construct() {
		parent::__construct();
		$this->mode = 'json';
	}
	
	public static function hook_init() {
		ob_start();
	}

	public static function hook_output($to_print) {
		switch (Controller::$action) {
		case 'list':
			$to_print = $to_print instanceof DBObject ? $to_print->list : $to_print;
			break;
		case 'display':
			if ($to_print instanceof DBObject && !empty(Controller::$parameters[0])) {
				$to_print = !empty($to_print->object) ? $to_print->object : $to_print->array;
			}
			break;
		default:
			break;
		}
		$object = new stdClass();
		$object->result  = $to_print;
		$object->error   = Backend::getError();
		$object->notice  = Backend::getNotice();
		$object->success = Backend::getSuccess();
		$last = '';
		while (ob_get_level() > 1) {
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
