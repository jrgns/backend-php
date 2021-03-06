<?php
/**
 * The file that defines the Application class.
 *
 * @author J Jurgens du Toit (JadeIT cc) <jurgens.dutoit@gmail.com> - initial API and implementation
 * @copyright Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available {@link http://www.eclipse.org/legal/epl-v10.html here}
 * @license http://www.eclipse.org/legal/epl-v10.html Eclipse Public License v1.0
 * @package Core
 */

/**
 * The Application class
 */
class Application {
	public static function getLinks($type = 'primary') {
	}

	/**
	 * Display hook
	 *
	 * Add Global stylesheets and scripts here
	 */
	public static function display_html() {
	}

	/**
	 * Add the names of Components to install in the array
	 */
	public static function getComponents() {
	    return array(
	    );
	}

	public static function install(array $options = array()) {
		return true;
	}
}
