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
class Admin extends AreaCtl {
	function action_install() {
		Backend::add('Sub Title', 'Install Backend Components');

		$modules = array(
			'HtmlView',
			'JsonView',
			'PhpView',
			'SerializeView',
			'Account',
			'Render',
		);
		$toret = true;
		foreach($modules as $module) {
			if (class_exists($module, true) && method_exists($module, 'install')) {
				if (!call_user_func_array(array($module, 'install'), array())) {
					Controller::addError('Error on installing ' . $module);
					$toret = false;
				}
			}
		}
		if ($toret) {
			Controller::addSuccess('Backend Install Successful');
		}
		//TODO Returning false at the moment, to make backend output the default HTML...
		return false;
	}
}