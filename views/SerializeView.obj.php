<?php
/**
 * The file that defines the SerializeView class.
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
 * Default class to handle SerializeView specific functions
 */
class SerializeView extends View {
	function __construct() {
		parent::__construct();
		$this->mode = 'serialize';
	}
	
	public static function hook_output($to_print) {
		if (!headers_sent()) {
			header('Content-Type: text/plain');
		}
		switch (Controller::$action) {
		case 'list':
			$to_print = $to_print instanceof DBObject ? $to_print->list : $to_print;
			break;
		case 'display':
			$to_print = $to_print instanceof DBObject && Controller::$id ? $to_print->array : $to_print;
			break;
		}
		//TODO check options to see if it should be encoded as well
		return serialize($to_print);
	}

	public static function install() {
		$toret = true;
		$hook = new HookObj();
		$toret = $hook->replace(array(
				'name'        => 'SerializeView Pre Output',
				'description' => '',
				'mode'        => 'serialize',
				'type'        => 'pre',
				'hook'        => 'output',
				'class'       => 'SerializeView',
				'method'      => 'hook_output',
				'sequence'    => '0',
			)
		) && $toret;
		return $toret;
	}
}

