<?php
/**
 * The class file for Component
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
 * This is the controller for the table components.
 */
class Component extends TableCtl {

	/**
	 * @todo Make this a POST only
	 */
	public function action_toggle($id, $field) {
		$result = parent::action_toggle($id, $field, false);
		if ($result && $result->array['active']) {
			if (call_user_func(array($result->array['name'], 'install'))) {
			} else {
				Backend::addError('Could not install component');
			}
		}
		return $result;
	}
	
	public function json_toggle($result) {
		if ($result instanceof ComponentObj) {
			Controller::redirect('?q=' . Controller::$area . '/read/' . $result->array['id']);
		}
		return $result;
	}
	
	public static function fromFolder() {
		$toret = array();
		$base_c = self::getCoreComponents(true);
		$base_c = array_flatten($base_c, 'name', 'filename');
		$toret  = array_merge($toret, files_from_folder(BACKEND_FOLDER . '/controllers/', array('prepend_folder' => true)));
		$toret  = array_merge($toret, files_from_folder(APP_FOLDER . '/controllers/', array('prepend_folder' => true)));
		$toret  = array_merge($toret, files_from_folder(BACKEND_FOLDER . '/views/', array('prepend_folder' => true)));
		$toret  = array_merge($toret, files_from_folder(APP_FOLDER . '/views/', array('prepend_folder' => true)));
		$toret  = array_merge($toret, files_from_folder(APP_FOLDER . '/classes/', array('prepend_folder' => true)));
		
		$backend = str_replace(array('\\', '/'), array('\\\\', '\/'), BACKEND_FOLDER);
		$app     = str_replace(array('\\', '/'), array('\\\\', '\/'), APP_FOLDER);
		$search = '/(' . $backend . '\/|' . $backend . '\\\\|' . $app . '\/|' . $app . '\\\\)/';
		$toret  = preg_replace($search, '/', $toret);

		$toret  = array_unique(array_merge(array_values($base_c), $toret));
		$toret  = array_filter($toret, array('Component', 'checkFile'));
		
		return $toret;
	}
	
	private static function checkFile($file) {
		if (substr($file, -4) != '.php') {
			return false;
		}
		return true;
	}

	public static function getCoreComponents($with_db = false) {
		$result = include(BACKEND_FOLDER . '/stuph/core_classes.inc.php');
		if (!$with_db) {
			return $result;
		}
		$result = array_merge($result, include(BACKEND_FOLDER . '/stuph/core_db_classes.inc.php'));
		return $result;
	}
	
	public static function getActive($refresh = false) {
		if (!BACKEND_WITH_DATABASE) {
			//Return the core components
			return self::getCoreComponents();
		}
		$result = $refresh ? false : Backend::get('Component::active', false);
		if ($result) {
			return $result;
		}
		$component = new ComponentObj();
		list ($query, $params) = $component->getSelectSQL(array('conditions' => '`active` = 1'));
		if ($query) {
			$query = new CustomQuery($query);
			$result = $query->fetchAll($params);
			Backend::add('Component::active', $result);
		}
		return $result;
	}
	
	public static function isActive($name) {
		//No DB, so we use files to determine if it's active or not
		if (!Backend::getDB('default')) {
			//Return true if the controller is in the APP / SITE Folder
			if (file_exists(APP_FOLDER . '/controllers/' . $name . '.obj.php')) {
				return true;
			} else if (defined('SITE_FOLDER') && file_exists(SITE_FOLDER . '/controllers/' . $name . '.obj.php')) {
				return true;
			}
		}
		$name = preg_replace('/Obj$/', '', $name);
		$active = self::getActive();
		if ($active) {
			$active = array_flatten($active, 'id', 'name');
			return in_array($name, $active);
		}
		return false;
	}
	
	public static function hook_init() {
		self::getActive();
	}
	
	/**
	 * This installs a default list of components.
	 * 
	 * It needs to be pre_installed so that the rest of the components can be installed from there
	 */
	public static function pre_install() {
		if (!Backend::getDB('default')) {
			return true;
		}
		$result = self::installModel(get_called_class() . 'Obj', array('drop_table' => true));

		$components = self::fromFolder();

		$component = new ComponentObj();
		$component->truncate();
		foreach($components as $component_file) {
			if (self::add($component_file)) {
				//TODO Move this to a install log file
				//echo 'Installed ' . $name;
			} else {
				$result = false;
			}
		}
		return $result;
	}
	
	private static function add($filename) {
		$name = preg_replace('/\.obj\.php$/', '', basename($filename));
		$active = in_array($name, array_flatten(self::getCoreComponents(true), null, 'name')) ||
				  $name == Backend::getConfig('backend.application.class');

		$data = array(
			'name'     => $name,
			'filename' => $filename,
			'options'  => '',
			'active'   => $active,
		);
		$component = new ComponentObj();
		return $component->create($data, array('load' => false));
	}
		
	public static function install(array $options = array()) {
		$options['install_model'] = array_key_exists('install_model', $options) ? $options['install_model'] : false;
		$result = parent::install($options);
		if (!Backend::getDB('default')) {
			return $result;
		}

		Hook::add('init', 'pre', get_called_class()) && $result;
		return $result;
	}
	
	public function action_check() {
		$toret = 0;
		$files = self::fromFolder();
		$table = Component::retrieve(false, 'list');
		$table = array_flatten($table, null, 'filename');
		foreach ($files as $component) {
			if (!in_array($component, $table)) {
				if (self::add($component)) {
					Backend::addSuccess('Added ' . basename($component));
					$toret++;
				}
			}
		}
		return $toret;
	}
	
	public function html_check() {
		Controller::redirect('?q=component/manage');
	}

	function action_manage() {
		$toret = array();
		
		$component = new ComponentObj();
		$component->read(array('order' => '`filename`'));
		$toret = $component->list;
		if (Controller::$debug) {
			var_dump('Component List:', $toret);
		}
		return $toret;
	}
	
	function html_manage($result) {
		Backend::add('Sub Title', 'Manage Components');
		Backend::add('result', $result);
		Links::add('Admin', '?q=admin/index', 'secondary');
		Backend::addScript(SITE_LINK . 'scripts/jquery.js');
		Backend::addScript(SITE_LINK . 'scripts/component.manage.js');
		Backend::addContent(Render::renderFile('component.manage.tpl.php'));
	}
	
	public static function admin_links() {
		if (!Backend::getDB('default')) {
			return false;
		}
		return array(
			array('text' => 'Manage Components', 'href' => '?q=component/manage'),
			array('text' => 'Check Components' , 'href' => '?q=component/check'),
		);
	}
}

