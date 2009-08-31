<?php
/**
 * The file that defines the ImageView class.
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
 * Default class to handle ImageView specific functions
 */
class ImageView extends View {
	function __construct() {
		parent::__construct();
		$this->mode = 'image';
	}
	
	function output() {
		if (!headers_sent()) {
			$mime_ranges = Parser::accept_header();
			$mime_type = current($mime_ranges);
			header('Content-Type: image/' . $mime_type['sub_type']);
		}
	}
}

