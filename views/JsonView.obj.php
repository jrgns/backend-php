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
	
	public static function hook_output($to_print) {
		$to_print = parent::hook_output($to_print);
		switch (Controller::parameter('action')) {
		case 'list':
			$to_print = $to_print instanceof DBObject ? $to_print->list : $to_print;
			break;
		case 'display':
			if ($to_print instanceof DBObject && Controller::$id) {
				$to_print = !empty($to_print->object) ? $to_print->object : $to_print->array;
			}
			break;
		default:
			break;
		}
		return json_encode($to_print);
	}

	public static function install() {
		$toret = true;
		$toret = Hook::add('output', 'pre', __CLASS__, array('mode' => 'json', 'global' => 1)) && $toret;
		return $toret;
	}
}

