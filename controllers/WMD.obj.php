<?php
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
class WMD extends AreaCtl {
	/**
	 * Add the scripts and styles needed for the WMD editor
	 *
	 * This hook automatically enables the WMD editor for the Content module. To enable it for any other module,
	 * just call WMD::enable on the appropriate html_ function.
	 */
	public static function hook_post_display($data, $controller) {
		if (in_array(get_class($controller), array('Content')) && in_array(Controller::$action, array('create', 'update'))) {
			self::enable();
		}
		return $data;
	}
	
	public static function enable() {
		Backend::addScript(SITE_LINK . '/scripts/jquery.js');
		Backend::addScript(SITE_LINK . '/scripts/wmd.component.js');
		Backend::addScript(SITE_LINK . '/scripts/wmd/wmd.js');
		Backend::addStyle(SITE_LINK . '/styles/wmd.css');
	}

	public static function install(array $options = array()) {
		$toret = parent::install($options);
		$toret = Hook::add('display', 'post', get_called_class(), array('global' => 1)) && $toret;
		return $toret;
	}
}

