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
		$base_c = self::getBaseComponents(array('filenames' => true));
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

	private static function getBaseComponents() {
		$toret = array();
		$toret['Backend']     = '/classes/Backend.obj.php';

		$toret['Controller']  = '/classes/Controller.obj.php';
		$toret['Render']      = '/classes/Render.obj.php';
		$toret['View']        = '/classes/View.obj.php';
		$toret['GateKeeper']  = '/classes/GateKeeper.obj.php';

		$toret['Component']   = '/controllers/Component.obj.php';
		$toret['Hook']        = '/controllers/Hook.obj.php';

		$toret['Role']        = '/controllers/Role.obj.php';
		$toret['Assignment']  = '/controllers/Assignment.obj.php';
		$toret['Permission']  = '/controllers/Permission.obj.php';
		$toret['GateManager'] = '/controllers/GateManager.obj.php';
		$toret['Admin']       = '/controllers/Admin.obj.php';
		$toret['Content']     = '/controllers/Content.obj.php';
		$toret['Value']       = '/controllers/Value.obj.php';
		$toret['BackendAccount'] = '/controllers/BackendAccount.obj.php';
		$toret['Account']     = '/controllers/Account.obj.php';
		$toret['Home']        = '/controllers/Home.obj.php';
		//Views
		$toret['HtmlView']    = '/views/HtmlView.obj.php';
		$toret['ImageView']   = '/views/ImageView.obj.php';
		$toret['JsonView']    = '/views/JsonView.obj.php';
		$toret['CssView']     = '/views/CssView.obj.php';
		$toret['SerializeView'] = '/views/SerializeView.obj.php';
		$toret['PhpView']     = '/views/PhpView.obj.php';
		$toret['AtomView']    = '/views/AtomView.obj.php';
		$toret['RssView']     = '/views/RssView.obj.php';
		$toret['ChunkView']   = '/views/ChunkView.obj.php';
		return $toret;
	}
	
	public static function getActive($refresh = false) {
		$toret = Backend::get('Component::active', false);
		if (!$toret || $refresh) {
			$component = new ComponentObj();
			list ($query, $params) = $component->getSelectSQL(array('conditions' => '`active` = 1'));
			$query = new CustomQuery($query);
			$toret = $query->fetchAll($params);
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
		$toret = self::installModel(get_called_class() . 'Obj', array('drop_table' => true));

		$components = self::fromFolder();

		$component = new ComponentObj();
		$component->truncate();
		foreach($components as $component_file) {
			if (self::add($component_file)) {
				//TODO Move this to a install log file
				//echo 'Installed ' . $name;
			} else {
				$toret = false;
			}
		}
		return $toret;
	}
	
	private static function add($filename) {
		$name = preg_replace('/\.obj\.php$/', '', basename($filename));
		$active = in_array($name, array_keys(self::getBaseComponents())) ||
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
		$toret = parent::install($options);
		Hook::add('init', 'pre', get_called_class()) && $toret;
		return $toret;
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
		return array(
			array('text' => 'Manage Components', 'href' => '?q=component/manage'),
			array('text' => 'Check Components' , 'href' => '?q=component/check'),
		);
	}
}

