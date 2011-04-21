<?php
/**
 * The class file for Admin
 *
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 * @copyright Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * @license http://www.eclipse.org/legal/epl-v10.html
 * @package ControllerFiles
 */
 
/**
 * This is the controller for the Admin area
 * @package Controllers
 */
class Admin extends AreaCtl {
	/**
	 *	Do some checks before the install commences
	 */
	public function get_pre_install() {
		$components = Component::getActive();
		if (!$components) {
			return false;
		}

		$can_install = true;
		$components = array_flatten($components, null, 'name');
		foreach($components as $component) {
			if (class_exists($component, true) && method_exists($component, 'install_check')) {
				Backend::addNotice('Checking ' . $component);
				if (!call_user_func_array(array($component, 'install_check'), array())) {
					Backend::addError('Error on checking install for ' . $component);
					$can_install = false;
				}
			}
		}
		
		return $can_install;
	}
	
	public function html_pre_install($can_install) {
		Backend::addNotice('This application has not been installed yet');
		Backend::addContent(Render::renderFile('uninstalled_msg.tpl.php', array('can_install' => $can_install)));
		return $can_install;
	}

	/**
	* Do the initial install for the different components.
	*/
	public function post_install() {
		$installed = ConfigValue::get('AdminInstalled', false);
		if ($installed) {
			return false;
		}
		$install_log_file = 'install_log_' . date('Ymd_His') . '.txt';
		Backend::add('log_to_file', $install_log_file);
		if (!Component::pre_install()) {
			Backend::addError('Could not pre install Component');
			return false; 
		}
		if (!Permission::pre_install()) {
			Backend::addError('Could not pre install Permission');
			return false;
		}
		if (!Hook::pre_install()) {
			Backend::addError('Could not pre install Hook');
			return false;
		}
		if (!Value::pre_install()) {
			Backend::addError('Could not pre install Value');
			return false;
		}
	
		$original = ConfigValue::get('LogToFile', false);
		ConfigValue::set('LogToFile', $install_log_file);

		Backend::addNotice(PHP_EOL . PHP_EOL . 'Installation started at ' . date('Y-m-d H:i:s'));
		
		if (!self::installConfig()) {
			return false;
		}
		$result = self::installComponents();

		ConfigValue::set('LogToFile', $original);
		return $result;
	}
	
	private static function installConfig() {
	}
	
	private static function installComponents() {
		$components = Component::getActive();
		if (!$components) {
			return false;
		}

		$result = true;
		$components = array_flatten($components, null, 'name');
		foreach($components as $component) {
			if (class_exists($component, true) && method_exists($component, 'install')) {
				Backend::addNotice('Installing ' . $component);
				if (!call_user_func_array(array($component, 'install'), array())) {
					Backend::addError('Error on installing ' . $component);
					$result = false;
				}
			}
		}
		return $result;
	}
	
	function get_daily(array $options = array()) {
		$components = Component::getActive();
		$result = true;
		foreach($components as $component) {
			if (is_callable(array($component['name'], 'daily'))) {
				$object = new $component['name']();
				call_user_func_array(array($object, 'daily'), $options) && $result;
			}
		}
		return $result;
	}
	
	public function json_daily($result) {
		if ($result) {
			//Exit without outputting anything. To be used in crons
			die;
		}
		return $result;
	}

	function get_weekly(array $options = array()) {
		$components = Component::getActive();
		$result = true;
		foreach($components as $component) {
			if (is_callable(array($component['name'], 'weekly'))) {
				$object = new $component['name']();
				$result = call_user_func_array(array($object, 'weekly'), $options) && $result;
			}
		}
		return $result;
	}
	
	public function json_weekly($result) {
		if ($result) {
			//Exit without outputting anything. To be used in crons
			die;
		}
		return $result;
	}

	public function html_install($result) {
		$installed = ConfigValue::get('AdminInstalled', false);
		if ($installed) {
			Backend::addNotice('Installation script already ran at ' . $installed);
			Controller::redirect('?q=admin');
		
		}
		if ($result && is_post()) {
			Backend::addSuccess('Backend Install Successful');
			$_SESSION['just_installed'] = true;
			ConfigValue::set('AdminInstalled', date('Y-m-d H:i:s'));
			Controller::redirect('?q=account/signup');
		} else {
			Backend::add('Sub Title', 'Install Backend Application');
		}
	}
	
	function html_post_install($result) {
		if (ConfigValue::get('AdminInstalled', false)) {
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
		Backend::addContent(Render::renderFile('admin.index.tpl.php'));
	}
	
	public static function hook_post_display($data, $controller) {
		$user = BackendAccount::checkUser();
		$installed = ConfigValue::get('AdminInstalled', false);
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

		$toret = Permission::add('nobody', 'post_install', 'admin') && $toret;
		$toret = Permission::add('anonymous', 'post_install', 'admin') && $toret;
		$toret = Permission::add('nobody', 'pre_install', 'admin') && $toret;
		$toret = Permission::add('anonymous', 'pre_install', 'admin') && $toret;
		$toret = Permission::add('nobody', 'daily', 'admin') && $toret;
		$toret = Permission::add('anonymous', 'daily', 'admin') && $toret;
		$toret = Permission::add('authenticated', 'daily', 'admin') && $toret;
		$toret = Permission::add('nobody', 'weekly', 'admin') && $toret;
		$toret = Permission::add('anonymous', 'weekly', 'admin') && $toret;
		$toret = Permission::add('authenticated', 'weekly', 'admin') && $toret;

		return $toret;
	}
}

