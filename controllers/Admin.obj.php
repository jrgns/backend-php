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
	/**
	* Do the initial install for the different components.
	*
	* @todo Check that this function is only called once, or by the super user (Use values to record when it was ran
	*/
	function action_install() {
		$toret = false;
		$installed = Value::get('admin_installed', false);
		if (!$installed) {
			$components = array(
				'Admin',
				'View',
				'HtmlView',
				'ImageView',
				'JsonView',
				'PhpView',
				'SerializeView',
				'Account',
				'Render',
				'ContentRevision',
				'Content',
				'WMD',
				'GateManager',
			);
			$toret = true;
			foreach($components as $component) {
				if (class_exists($component, true) && method_exists($component, 'install')) {
					if (!call_user_func_array(array($component, 'install'), array())) {
						Controller::addError('Error on installing ' . $component);
						$toret = false;
					}
				}
			}
			if ($toret) {
				Value::set('admin_installed', date('Y-m-d H:i:s'));
				Controller::addSuccess('Backend Install Successful');
			}
			//TODO Returning false at the moment, to make backend output the default HTML...
		} else {
			Controller::addError('Admin installation script already ran at ' . $installed);
		}
		return $toret;
	}
	
	function action_update() {
	}
	
	function action_components() {
		$toret = array();
		$toret = array_merge($toret, self::getComponents());
		$toret = array_merge($toret, self::getViews());
		
		$component = new ComponentObj();
		$component->load();
		if (Controller::$debug) {
			$list = $component->list;
			array_flatten($list);
			var_dump('Component List:', $list);
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
	
	function html_interface($result) {
		Backend::add('Sub Title', 'Manage Application');
		Backend::add('result', $result);
		Controller::addContent(Render::renderFile('admin_interface.tpl.php'));
	}
	
	function html_components($result) {
		Backend::add('Sub Title', 'Manage Components');
		Backend::add('result', $result);
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
				array('href' => '?q=admin/interface', 'text' => 'Manage Application'),
			);
		}
		Backend::add('secondary_links', $sec_links);
		
		return $data;
	}

	public static function install() {
		$toret = true;
		$hook = new HookObj();
		$toret = $hook->replace(array(
				'name'        => 'Admin Post Display',
				'description' => '',
				'mode'        => 'html',
				'type'        => 'post',
				'hook'        => 'display',
				'class'       => 'Admin',
				'method'      => 'hook_post_display',
				'sequence'    => 0,
			)
		) && $toret;
		return $toret;
	}
}

