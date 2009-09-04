<?php
/**
 * The file that defines the AreaCtl class.
 *
 * @author J Jurgens du Toit (JadeIT cc) <jurgens.dutoit@gmail.com> - initial API and implementation
 * @copyright Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available {@link http://www.eclipse.org/legal/epl-v10.html here}
 * @license http://www.eclipse.org/legal/epl-v10.html Eclipse Public License v1.0
 * @package Core
 */
 
/**
 * Default class to handle Area specific functions
 */
class AreaCtl {
	static private $error_msgs = array();

	/**
	 * The standard action for an Area
	 */
	public function action() {
		$toret = null;
		if (array_key_exists('msg', $_REQUEST)) {
			Controller::addError(self::getError($_REQUEST['msg']));
		}
		
		$method = 'action_' . Controller::$action;
		if (method_exists($this, $method)) {
			if ($this->checkPermissions()) {
				$toret = $this->$method();
			} else {
				Controller::whoops(array('title' => 'Permission Denied', 'message' => 'You do not have permission to ' . Controller::$action . ' ' . get_class($this)));
				$toret = false;
			}
		} else {
			Controller::whoops();
		}
		return $toret;
	}
	
	/**
	 * Display does nothing but display (hahaha) the content fetched by DBObject::read
	 */
	public function action_display() {
		$toret = false;
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true) && Controller::$id !== 'home' && Controller::$id > 0) {
			$object = new $obj_name(Controller::$id);
			$object->load(array('mode' => 'array'));
			$toret = $object;
		} else {
			Controller::$action = 'list';
			$toret = $this->action_list();
		}
		return $toret;
	}
	
	/**
	 * Action for listing an area's records
	 */
	public function action_list() {
		$toret = false;
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true)) {
			$object = new $obj_name();
			$object->load(array('limit' => Controller::$count));
			$toret = $object;
		} else {
			Controller::whoops();
		}
		return $toret;
	}
	
	/**
	 * Action for creating a record in an area
	 */
	public function action_create() {
		$toret = false;
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true)) {
			$object = new $obj_name();
			$data = $object->fromPost();
			if (is_post()) {
				if ($object->create($data)) {
					Controller::addSuccess($object->getMeta('name') . ' Added');
					Controller::redirect('?q=' . $object->getArea() . '/display/' . $object->getMeta('id'));
				} else {
					Controller::addError('Could not add ' . $object->getMeta('name'));
				}
			}
			Backend::add('obj_values', $data);
			$toret = $object;
		} else {
			Controller::whoops();
		}
		return $toret;
	}
	
	/**
	 * Action for reading a record in an area
	 */
	public function action_read() {
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true)) {
			$object = new $obj_name(Controller::$id);
			$toret = $object->read();
		} else {
			Controller::whoops();
		}
		return $toret;
	}
	
	/**
	 * Action for updating a record in an area
	 */
	public function action_update() {
		$toret = false;
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true) && Controller::$id !== 'home' && Controller::$id > 0) {
			$object = new $obj_name(Controller::$id);
			if (is_post()) {
				$data = $object->fromPost();
				if ($object->update($data)) {
					Controller::addSuccess($object->getMeta('name') . ' Modified');
					Controller::redirect('?q=' . $object->getArea() . '/display/' . $object->getMeta('id'));
				} else {
					Controller::addError('Could not update ' . $object->getMeta('name'));
				}
			} else {
				$data = $object->array;
			}
			Backend::add('obj_values', $data);
			$toret = $object;
		} else {
			Controller::whoops();
		}
		return $toret;
	}
	
	/**
	 * Action for importing records in an area
	 */
	public function action_import() {
		$toret = false;
		Backend::add('Sub Title', 'Import');
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true)) {
			Backend::add('Sub Title', 'Import ' . $object->getMeta('name'));
			$object = new $obj_name();
			$object->load();
			if (is_post() && !empty($_FILES) && array_key_exists('import_file', $_FILES)) {
				$file = $_FILES['import_file'];
				if ($file['type'] == 'text/csv') {
					$importer_name = get_class($this) . 'Importer';
					if (class_exists($importer_name, true)) {
						$Importer = new $importer_name();
						$toret = $Importer->import($file['tmp_name']);
					} else {
						$fp = fopen($file['tmp_name'], 'r');
						if ($fp)  {
							$obj_name = get_class($this) . 'Obj';
							if (class_exists($obj_name, true)) {
								$Object = new $obj_name();
								$names = array_keys($Object->getMeta('fields'));
								$name_count = count($names);
								$count = 0;
								while(($line = fgetcsv($fp)) !== false) {
									if ($name_count == count($line)) {
										$line = array_combine($names, $line);
										$toret = $Object->create($line);
										if (!$toret) {
											break;
										}
										$count++;
									} else {
										Controller::addError('Number of imported fields does not match defined fields');
									}
								}
								if ($count) {
									Controller::addSuccess($count . ' records Imported');
								}
							} else {
								Controller::addNotice('The Object definition is missing');
							}
						} else {
							Controller::addError('Could not read uploaded file');
						}
					}
				} else {
					Controller::addError('This import can only handle CSV files. The uploaded file is ' . $file['type']);
				}
			} else if (is_post() && empty($_FILES)) {
				Controller::addError('There is a problem with the HTML Form');
			}
			Backend::add('Object', $object);
			$template_file = singularize(computerize(class_name(Controller::$area))) . '.import.tpl.php';
			if (!Render::checkTemplateFile($template_file)) {
				$template_file = 'std_import.tpl.php';
			}
			Controller::addContent(Render::renderFile($template_file));
		} else {
			Controller::whoops();
		}
		return $toret;
	}
	
	/**
	 * Output an object in HTML
	 *
	 * Override this function if you want to customize the way a record is displayed.
	 * You can also just create a template named $areaname.display.tpl.php to customize the HTML.
	 */
	public function html_display($object) {
		if ($object) {
			if ($object instanceof DBObject) {
				Backend::add('Object', $object);
				Backend::add('TabLinks', $this->getTabLinks('display'));
				Backend::add('Sub Title', $object->getMeta('name'));
				$template_file = $object->getArea() . '.display.tpl.php';
				if (!Render::checkTemplateFile($template_file)) {
					$template_file = 'std_display.tpl.php';
				}
				Controller::addContent(Render::renderFile($template_file));
			} else {
				Controller::whoops(array('title' => 'Invalid Object returned'));
			}
		}
		return true;
	}
	
	/**
	 * Output a list of records in HTML
	 *
	 * Override this function if you want to customize the way the list of records are displayed.
	 * You can also just create a template named $areaname.list.tpl.php to customize the HTML.
	 */
	public function html_list($object) {
		if ($object) {
			if ($object instanceof DBObject) {
				Backend::add('Object', $object);
				Backend::add('TabLinks', $this->getTabLinks('list'));
				Backend::add('Sub Title', $object->getMeta('name'));
				$template_file = $object->getArea() . '.list.tpl.php';
				if (!Render::checkTemplateFile($template_file)) {
					$template_file = 'std_list.tpl.php';
				}
				Controller::addContent(Render::renderFile($template_file));
			} else {
				Controller::whoops(array('title' => 'Invalid Object returned'));
			}
		}
		return true;
	}
	
	/**
	 * Output a form to create a record in HTML
	 *
	 * Override this function if you want to customize the way the creation of a record is displayed.
	 * You can also just create a template named $areaname.form.tpl.php to customize the HTML.
	 */
	public function html_create($object) {
		if ($object) {
			if ($object instanceof DBObject) {
				Backend::add('Object', $object);
				Backend::add('TabLinks', $this->getTabLinks('create'));
				Backend::add('Sub Title', 'Add ' . $object->getMeta('name'));
				$template_file = $object->getArea() . '.form.tpl.php';
				if (!Render::checkTemplateFile($template_file)) {
					$template_file = 'std_form.tpl.php';
				}
				Controller::addContent(Render::renderFile($template_file));
			} else {
				Controller::whoops(array('title' => 'Invalid Object returned'));
			}
		}
		return true;
	}
	
	/**
	 * Output a form to update a record in HTML
	 *
	 * Override this function if you want to customize the way the creation of a record is displayed.
	 * You can also just create a template named $areaname.form.tpl.php to customize the HTML.
	 */
	public function html_update($object) {
		if ($object) {
			if ($object instanceof DBObject) {
				Backend::add('Object', $object);
				Backend::add('TabLinks', $this->getTabLinks('update'));
				Backend::add('Sub Title', 'Update ' . $object->getMeta('name'));
				$template_file = $object->getArea() . '.form.tpl.php';
				if (!Render::checkTemplateFile($template_file)) {
					$template_file = 'std_form.tpl.php';
				}
				Controller::addContent(Render::renderFile($template_file));
			} else {
				Controller::whoops(array('title' => 'Invalid Object returned'));
			}
		}
		return true;
	}
	
	/**
	 * Return a area specific error
	 *
	 * Override this function if you want to customize the errors returned for an area.
	 */
	public static function getError($num, $class_name = false) {
		$msg = false;
		$class_name = $class_name ? $class_name : class_name(Controller::$area);
		if (class_exists($class_name, true)) {
			$vars = get_class_vars($class_name);
			var_dump($vars);
			$msg = empty($vars['error_msgs'][$num]) ? false : $vars['error_msgs'][$num];
		}
		return $msg;
	}
	
	/**
	 * Return Tab Links for this area
	 *
	 * Override this function if you want to customize the Tab Links for an area.
	 */
	protected function getTabLinks($action) {
		$links = array();
		//TODO Check permissions!
		if ($this->checkPermissions(array('action' => 'list'))) {
			$links[] = array('link' => '?q=' . Controller::$area . '/list', 'text' => 'List');
		}
		if ($this->checkPermissions(array('action' => 'create'))) {
			$links[] = array('link' => '?q=' . Controller::$area . '/create', 'text' => 'Create');
		}
		return $links;
	}
	
	/**
	 * Check permissions for this area
	 *
	 * Override this function if you want to customize the permissions for an area.
	 */
	public function checkPermissions(array $options = array()) {
		$toret = true;
		$action = !empty($options['action']) ? $options['action'] : (!empty(Controller::$action) ? Controller::check_reverse_map('action', Controller::$action) : '*');
		$subject = !empty($options['subject']) ? $options['subject'] : (!empty(Controller::$area) ? Controller::check_reverse_map('area', Controller::$area) : '*');
		$subject_id = !empty($options['subject_id']) ? $options['subject_id'] : (!empty(Controller::$id) ? Controller::check_reverse_map('id', Controller::$id) : 0);

		$roles = GateKeeper::permittedRoles($action, $subject, $subject_id);
		if (!empty($_SESSION['user'])) {
			if (Controller::$debug) {
				if (is_object($_SESSION['user']) && property_exists($_SESSION['user'], 'roles')) {
					Controller::addNotice('Current user roles: ' . serialize($_SESSION['user']->roles));
				} else {
					Controller::addError('No user roles');
					$_SESSION['user']->roles = array();
				}
			}
			if ($roles) {
				$intersect = array_intersect($_SESSION['user']->roles, $roles);
				$toret = count($intersect) ? true : false;
			} else {
				$toret = $_SESSION['user']->roles;
			}
		}
		return $toret;
	}
}
