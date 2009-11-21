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
class CssView extends TextView {
	function __construct() {
		parent::__construct();
		$this->mode = 'css';
		$this->mime_type = 'text/css';
	}
	
	public static function install() {
		$toret = true;
		$toret = Hook::add('output', 'pre', __CLASS__, array('mode' => 'css', 'global' => 1)) && $toret;
		return $toret;
	}
}
