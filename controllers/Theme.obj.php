<?php
/**
 * The class file for Theme
 */
/**
 * Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * @license http://www.eclipse.org/legal/epl-v10.html
 * @package Controllers
 *
 * Contributors:
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 */

/**
 * This is the controller for the table themes.
 */
class Theme extends TableCtl {
	public static function hook_view_name($view_name) {
		//TODO Check for a Theme here
		
		//Check for a Mobile version of the View
		$mobile = false;
		if (Component::isActive('Wurfl')) {
			$device = Wurfl::getDevice();
			if (($device && $device->getCapability('mobile_browser') != '') || array_key_exists('mobile', $_REQUEST)) {
				$mobile = true;
			}
		}
		if ($mobile && Component::isActive('Mobile' . $view_name)) {
			$view_name = 'Mobile' . $view_name;
		}
		return $view_name;
	}

	public static function install(array $options = array()) {
		$toret = parent::install($options);
		$toret = Hook::add('update', 'post', get_called_class(),
							array('description' => 'Check for enabled / requested themes, and change the view name accordingly.')
						) && $toret;
		return $toret;
	}
}

