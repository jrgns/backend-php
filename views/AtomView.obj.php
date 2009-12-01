<?php
/**
 * The file that defines the AtomView class.
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
 * Default class to handle AtomView specific functions
 */
class AtomView extends View {
	function __construct() {
		parent::__construct();
		$this->mode = 'atom';
		$this->mime_type = 'application/atom+xml';
	}
	
	public static function hook_output($to_print) {
		if (!headers_sent()) {
			header('Content-Type: application/atom+xml');
		}
		$to_print = Render::renderFile('atom.tpl.php');
		return $to_print;
	}

	public static function install() {
		$toret = true;
		$toret = Hook::add('output', 'pre', __CLASS__, array('mode' => 'atom', 'global' => 1)) && $toret;
		return $toret;
	}
}

