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
	 * Simple page to notify the user that the app has not been installed yet
	 */
	public function html_pre_install($result) {
		Backend::add('Sub Title', 'This application has not been installed yet.');
		Backend::addContent(Render::renderFile('admin.pre_install.tpl.php'));
		return $result;
	}

	/**
	 *	Do some checks before the install commences
	 *
	 * Catch both get and post
	 */
	public function action_check_install() {
		$components = Component::getActive();
		if (!$components) {
			Backend::addError('Could not get components to check');
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
	
	public function html_check_install($result) {
		Backend::add('Sub Title', 'Pre Installation Check');
		Backend::addNotice('This application has not been installed yet');
		Backend::addContent(Render::renderFile('admin.check_install.tpl.php', array('can_install' => $result)));
		return $result;
	}

	/**
	* Do the initial install for the different components.
	*/
	public function post_install() {
		$installed = ConfigValue::get('AdminInstalled', false);
		if ($installed) {
			return false;
		}

		$components = Component::getActive();
		if (!$components) {
			Backend::addError('Could not get components to pre install');
			return false;
		}

		//Save original LogToFile setting
		$original = ConfigValue::get('LogToFile', false);
		$install_log_file = 'install_log_' . date('Ymd_His') . '.txt';
		ConfigValue::set('LogToFile', $install_log_file);

		//Pre Install components
		Backend::addNotice(PHP_EOL . PHP_EOL . 'Installation started at ' . date('Y-m-d H:i:s'));
		$components = array_flatten($components, null, 'name');
		foreach($components as $component) {
			if (class_exists($component, true) && method_exists($component, 'pre_install')) {
				Backend::addNotice('Pre Installing ' . $component);
				if (!call_user_func_array(array($component, 'pre_install'), array())) {
					Backend::addError('Error on pre install for ' . $component);
					return false;
				}
			}
		}
		
		//Install Components
		foreach($components as $component) {
			if (class_exists($component, true) && method_exists($component, 'install')) {
				Backend::addNotice('Installing ' . $component);
				if (!call_user_func_array(array($component, 'install'), array())) {
					Backend::addError('Error on installing ' . $component);
					return false;
				}
			}
		}

		//Restore Original
		ConfigValue::set('LogToFile', $original);
		
		ConfigValue::set('AdminInstalled', true);
		return true;
	}
	
	public function html_install($result) {
		if (is_post() && $result) {
			Backend::addSuccess('Backend Install Successful');
			$_SESSION['just_installed'] = true;
			ConfigValue::set('AdminInstalled', date('Y-m-d H:i:s'));
			if (BACKEND_WITH_DATABASE) {
				Controller::redirect('?q=account/signup');
			} else {
				Controller::redirect('?q=home/index');
			}
		}
		Backend::add('Sub Title', 'Install Backend Application');
		Backend::addContent('<p class="large loud">Something went wrong with the installation</p>');
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
		$admin_links = array_filter($admin_links);
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
		$result = parent::install($options);
		if (!BACKEND_WITH_DATABASE) {
			return $result;
		}

		$result = Hook::add('display', 'post', __CLASS__, array('global' => true, 'mode' => 'html')) && $result;

		$result = Permission::add('nobody', 'daily', 'admin') && $result;
		$result = Permission::add('anonymous', 'daily', 'admin') && $result;
		$result = Permission::add('authenticated', 'daily', 'admin') && $result;
		$result = Permission::add('nobody', 'weekly', 'admin') && $result;
		$result = Permission::add('anonymous', 'weekly', 'admin') && $result;
		$result = Permission::add('authenticated', 'weekly', 'admin') && $result;

		return $result;
	}
}

