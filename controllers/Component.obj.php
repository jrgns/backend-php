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
	public static function fromFolder() {
		$toret = array();
		$base_c = self::getBaseComponents(array('filenames' => true));
		$toret  = array_merge($toret, files_from_folder(BACKEND_FOLDER . '/controllers/', array('prepend_folder' => true)));
		$toret  = array_merge($toret, files_from_folder(APP_FOLDER . '/controllers/', array('prepend_folder' => true)));
		$toret  = array_merge($toret, files_from_folder(BACKEND_FOLDER . '/views/', array('prepend_folder' => true)));
		$toret  = array_merge($toret, files_from_folder(APP_FOLDER . '/views/', array('prepend_folder' => true)));
		$toret  = array_merge($toret, files_from_folder(APP_FOLDER . '/classes/', array('prepend_folder' => true)));
		$toret  = preg_replace('/(' . str_replace('/', '\/', BACKEND_FOLDER) . '\/|' . str_replace('/', '\/', APP_FOLDER) . '\/)/', '/', $toret);

		$toret  = array_unique(array_merge(array_values($base_c), $toret));
		
		return $toret;
	}

	private static function getBaseComponents() {
		$toret = array();
		$toret['Controller']  = '/classes/Controller.obj.php';
		$toret['Render']      = '/classes/Render.obj.php';
		$toret['Backend']     = '/classes/Backend.obj.php';
		$toret['GateKeeper']  = '/classes/GateKeeper.obj.php';
		$toret['View']        = '/classes/View.obj.php';
		$toret['HtmlView']    = '/views/HtmlView.obj.php';
		$toret['ImageView']   = '/views/ImageView.obj.php';
		$toret['JsonView']    = '/views/JsonView.obj.php';
		$toret['SerializeView'] = '/views/SerializeView.obj.php';
		$toret['PhpView']     = '/views/PhpView.obj.php';
		$toret['Component']   = '/controllers/Component.obj.php';
		$toret['Hook']        = '/controllers/Hook.obj.php';
		$toret['GateManager'] = '/controllers/GateManager.obj.php';
		$toret['Admin']       = '/controllers/Admin.obj.php';
		$toret['Content']     = '/controllers/Content.obj.php';
		$toret['Value']       = '/controllers/Value.obj.php';
		$toret['Account']     = '/controllers/Account.obj.php';
		$toret['Assignment']  = '/controllers/Assignment.obj.php';
		return $toret;
	}
	
	public static function getActive($refresh = false) {
		$toret = Backend::get('Component::active', false);
		if (!$toret || $refresh) {
			$component = new ComponentObj();
			list ($query, $params) = $component->getSelectSQL(array('conditions' => '`active` = 1'));
			$query = new CustomQuery($query);
			$toret = $query->fetchAll();
			Backend::add('Component::active', $toret);
		}
		return $toret;
	}
	
	public static function isActive($name) {
		$toret = false;
		if (Value::get('admin_installed', false)) {
			$name = preg_replace('/Obj$/', '', $name);
			$active = self::getActive();
			if ($active) {
				$active = array_flatten($active, 'id', 'name');
				$toret = in_array($name, $active);
			}
		} else if ($name == 'Admin') {
			$toret = true;
		}
		return $toret;
	}
	
	public static function hook_init() {
		self::getActive();
	}
	
	public static function pre_install() {
		$toret = self::installModel(__CLASS__ . 'Obj');

		$components = self::fromFolder();

		$component = new ComponentObj();
		$component->truncate();
		foreach($components as $component_file) {
			$name = preg_replace('/\.obj\.php$/', '', basename($component_file));
			$active = in_array($name, array_keys(self::getBaseComponents())) ||
					  $name == Backend::getConfig('backend.application.class');

			$data = array(
				'name'     => $name,
				'filename' => $component_file,
				'options'  => '',
				'active'   => $active,
			);
			if ($component->create($data, array('load' => false))) {
				//TODO Move this to a install log file
				//echo 'Installed ' . $name;
			} else {
				$toret = false;
			}
		}
		return $toret;
	}
		
	public static function install() {
		$toret = true;
		Hook::add('init', 'pre', __CLASS__) && $toret;
		return $toret;
	}
	
	public static function check() {
		$toret = false;
	}
}

