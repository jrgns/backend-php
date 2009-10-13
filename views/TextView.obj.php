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
class TextView extends View {
	function __construct() {
		parent::__construct();
		$this->mode = 'text';
	}
	
	public static function hook_output($to_print) {
		if (!headers_sent()) {
			header('Content-Type: text/plain');
		}
		return $to_print;
	}

	public static function install() {
		$toret = true;
		$hook = new HookObj();
		$toret = $hook->replace(array(
				'name'        => 'TextView Pre Output',
				'description' => '',
				'mode'        => 'text',
				'type'        => 'pre',
				'hook'        => 'output',
				'class'       => 'TextView',
				'method'      => 'hook_output',
				'sequence'    => 0,
			)
		) && $toret;
		return $toret;
	}
}

