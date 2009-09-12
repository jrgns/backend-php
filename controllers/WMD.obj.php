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
	 * @todo Check for which controllers WMD should be enabled.
	 */
	public static function hook_post_display($data, $controller) {
		if (in_array(get_class($controller), array('Content'))) { 
			Controller::addScript(SITE_LINK . 'scripts/jquery.js');
			Controller::addScript(SITE_LINK . 'scripts/wmd.module.js');
			Controller::addScript(SITE_LINK . 'scripts/wmd/wmd.js');
			Controller::addStyle(SITE_LINK . 'styles/wmd.css');
		}
		return $data;
	}

	public static function install() {
		$toret = true;
		$hook = new HookObj();
		$toret = $hook->replace(array(
				'name'        => 'WMD Post Display',
				'description' => '',
				'mode'        => '*',
				'type'        => 'post',
				'hook'        => 'display',
				'class'       => 'WMD',
				'method'      => 'hook_post_display',
				'sequence'    => '0',
			)
		) && $toret;
		return $toret;
	}
}

