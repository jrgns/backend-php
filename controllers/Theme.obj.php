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
	public static function get($name = false) {
		$theme = false;
		if (Value::get('admin_installed', false)) {
			$name = $name ? $name : Value::get('default_theme', 'backend');
			$theme = Theme::retrieve($name);
			if ($theme) {
				$theme['path'] = str_replace(array('#BACKEND_FOLDER#', '#APP_FOLDER#', '#SITE_FOLDER#', '#WEB_FOLDER#'), array(BACKEND_FOLDER, APP_FOLDER, SITE_FOLDER, WEB_FOLDER), $theme['path']);
			}
		}
		return $theme;
	}
}

