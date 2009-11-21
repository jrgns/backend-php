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
	public function action_pre_install() {
		Controller::addNotice('This application has not been installed yet');
		Controller::addContent(Render::renderFile('uninstalled_msg.tpl.php'));
		return true;
	}
	
	/**
	* Do the initial install for the different components.
	*
	* @todo Check that this function is only called once, or by the super user (Use values to record when it was ran
	*/
	public function action_install() {
		$toret = false;
		$installed = Value::get('admin_installed', false);
		if (!$installed) {
			Component::pre_install();
			Hook::pre_install();
			
			$components = Component::getActive();
			if ($components) {
				$toret = true;

				$components = array_flatten($components, null, 'name');

				//Why is this here?
				$hook = new HookObj();
				$hook->truncate();
				foreach($components as $component) {
					if (class_exists($component, true) && method_exists($component, 'install')) {
						if (!call_user_func_array(array($component, 'install'), array())) {
							Controller::addError('Error on installing ' . $component);
							$toret = false;
						}
					}
				}
			}
			if ($toret) {
				$_SESSION['just_installed'] = true;
				Value::set('admin_installed', date('Y-m-d H:i:s'));
				Controller::addSuccess('Backend Install Successful');
				Controller::redirect('?q=account/signup');
			}
		} else {
			Controller::addError('Admin installation script already ran at ' . $installed);
			Controller::redirect('?q=admin/post_install');
		}
		return $toret;
	}
	
	function action_post_install() {
		if (Value::get('admin_installed', false)) {
			Backend::add('Sub Title', 'Installation Successfull');
		} else {
			Backend::add('Sub Title', 'Installation Failed');
		}
		return true;
	}
	
	function action_update() {
	}
	
	function action_check() {
	}
	
	function action_components() {
		$toret = array();
		
		$toret = Component::retrieve(array('return' => 'list'));
		if (Controller::$debug) {
			var_dump('Component List:', $toret);
		}
		return $toret;
	}
	
	function html_install($result) {
		Backend::add('Sub Title', 'Install Backend Components');
		Backend::add('result', $result);
	}
	
	function html_update($result) {
		Backend::add('Sub Title', 'Update Backend Components');
		Backend::add('result', $result);
	}
	
	function html_index($result) {
		Backend::add('Sub Title', 'Manage Application');
		Backend::add('result', $result);
		Controller::addContent(Render::renderFile('admin_interface.tpl.php'));
	}
	
	function html_components($result) {
		Backend::add('Sub Title', 'Manage Components');
		Backend::add('result', $result);
		Controller::addScript(SITE_LINK . 'scripts/jquery.js');
		Controller::addScript(SITE_LINK . 'scripts/admin_components.js');
		Controller::addContent(Render::renderFile('admin_components.tpl.php'));
	}
	
	public static function hook_post_display($data, $controller) {
		
		$sec_links = Backend::get('secondary_links', array());
		$user = Account::checkUser();
		$installed = Value::get('admin_installed', false);
		if (!$installed) {
			$sec_links += array(
				array('href' => '?q=admin/install', 'text' => 'Install Application'),
			);
		}
		if ($user && count(array_intersect(array('superadmin', 'admin'), $user->roles))) {
			$sec_links += array(
				array('href' => '?q=admin', 'text' => 'Manage Application'),
			);
		}
		Backend::add('secondary_links', $sec_links);
		
		return $data;
	}

	public static function install() {
		$toret = true;

		$toret = Hook::add('display', 'post', __CLASS__, array('mode' => 'html')) && $toret;

		$toret = Permission::add('anonymous', 'post_install', 'admin') && $toret;
		$toret = Permission::add('anonymous', 'pre_install', 'admin') && $toret;

		return $toret;
	}
}

