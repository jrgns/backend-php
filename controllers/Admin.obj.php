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
			//if (is_post()) {
				Component::pre_install();
				Permission::pre_install();
				Hook::pre_install();
				Value::pre_install();
			
				$original = Value::get('log_to_file', false);
				//Value::set('log_to_file', 'install_log.txt');
				Value::set('log_to_file', 'install_log_' . date('Ymd_His') . '.txt');

				Controller::addNotice(PHP_EOL . PHP_EOL . 'Installation started at ' . date('Y-m-d H:i:s'));

				$components = Component::getActive();
				if ($components) {
					$toret = true;

					$components = array_flatten($components, null, 'name');

					foreach($components as $component) {
						Controller::addNotice('Installing ' . $component);
						if (class_exists($component, true) && method_exists($component, 'install')) {
							if (!call_user_func_array(array($component, 'install'), array())) {
								Controller::addError('Error on installing ' . $component);
								$toret = false;
							}
						}
					}
				}
				Value::set('log_to_file', $original);
			//}
		}
		return $toret;
	}
	
	function action_update() {
	}
	
	function action_check() {
	}
	
	function action_components() {
		$toret = array();
		
		$toret = Component::retrieve(false, 'list');
		if (Controller::$debug) {
			var_dump('Component List:', $toret);
		}
		return $toret;
	}
	
	public function html_install($result) {
		$installed = Value::get('admin_installed', false);
		if ($result) {
			Controller::addSuccess('Backend Install Successful');
			$_SESSION['just_installed'] = true;
			Value::set('admin_installed', date('Y-m-d H:i:s'));
			Controller::redirect('?q=account/signup');
		} else if (!$installed) {
			Backend::add('Sub Title', 'Install Backend Application');
		} else {
			Controller::addNotice('Installation script already ran at ' . $installed);
			Controller::redirect('?q=admin');
		}
	}
	
	function html_post_install($result) {
		if (Value::get('admin_installed', false)) {
			Backend::add('Sub Title', 'Installation Successfull');
		} else {
			Backend::add('Sub Title', 'Installation Failed');
		}
		return true;
	}
	
	function html_update($result) {
		Backend::add('Sub Title', 'Update Backend Components');
		Backend::add('result', $result);
	}
	
	function html_index($result) {
		Backend::add('Sub Title', 'Manage Application');
		Backend::add('result', $result);

		$admin_links = array();
		$components = Component::getActive();
		foreach($components as $component) {
			if (method_exists($component['name'], 'admin_links')) {
				$admin_links[$component['name']] = call_user_func(array($component['name'], 'admin_links'));
			}
		}
		Backend::add('admin_links', $admin_links);
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
		$user = Account::checkUser();
		$installed = Value::get('admin_installed', false);
		if (!$installed) {
			Links::add('Install Application', '?q=admin/install', 'secondary');
		}
		if ($user && count(array_intersect(array('superadmin', 'admin'), $user->roles))) {
			Links::add('Manage Application', '?q=admin', 'secondary');
		}
		
		return $data;
	}

	public static function install(array $options = array()) {
		$toret = parent::install($options);

		$toret = Hook::add('display', 'post', __CLASS__, array('global' => true, 'mode' => 'html')) && $toret;

		$toret = Permission::add('anonymous', 'post_install', 'admin') && $toret;
		$toret = Permission::add('anonymous', 'pre_install', 'admin') && $toret;

		return $toret;
	}
}

