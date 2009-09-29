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
 * Default class to handle the specific functions for Areas that are linked to Tables
 */
class TableCtl extends AreaCtl {
	/**
	 * Display does nothing but display (hahaha) the content fetched by DBObject::read
	 */
	public function action_display() {
		$toret = false;
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true) && Controller::$id !== 'home' && Controller::$id > 0) {
			$toret = self::action_read();
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
				$data = Hook::run('create', 'pre', array($data, $object), array('toret' => $data));
				if ($object->create($data)) {
					Controller::addSuccess($object->getMeta('name') . ' Added');
					if (Hook::run('create', 'post', array($data, $object), array('toret' => true))) {
						Controller::redirect('?q=' . $object->getArea() . '/display/' . $object->getMeta('id'));
					}
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
		$toret = null;
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true)) {
			$toret = new $obj_name(Controller::$id);
			$toret->read();
			Hook::run('read', 'post', array($toret), array('toret' => $toret));
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
				$data = Hook::run('update', 'pre', array($data, $object), array('toret' => $data));
				if ($object->update($data)) {
					Controller::addSuccess($object->getMeta('name') . ' Modified');
					if (Hook::run('update', 'post', array($data, $object), array('toret' => true))) {
						Controller::redirect('?q=' . $object->getArea() . '/display/' . $object->getMeta('id'));
					}
				} else {
					Controller::addError('Could not update ' . $object->getMeta('name'));
				}
			} else {
				$data = $object->array;
			}
			Backend::add('obj_values', $data);
			if ($data) {
				$toret = $object;
			} else {
				Controller::whoops('No ' . $object->getMeta('name') . ' to update');
			}
		} else {
			Controller::whoops();
		}
		return $toret;
	}
	
	public function action_delete() {
		$toret = false;
		if (is_post()) {
			if (empty(Controller::$id) && !empty($_POST['delete_id'])) {
				Controller::$id = $_POST['delete_id'];
			}
			$obj_name = (class_name(Controller::$area) . 'Obj');
			$object = new $obj_name(Controller::$id);
			if ($object->array) {
				if ($object->delete()) {
					Controller::addSuccess('Record has been removed');
					Controller::redirect();
				}
			} else {
				Controller::addError('The record does not exist');
			}
		}
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

				Controller::addScript(SITE_LINK . 'scripts/jquery.js');
				Controller::addScript(SITE_LINK . 'scripts/table_list.js');
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
	 * Don't know why, but the child classes actually inherits this!?
	 *
	 * @todo This isn't entirely accurate. If you want to create a random action_something, it need's to be
	 * added to the array below... This isn't optimal. Either get the array dynamically (get_class_methods) or refactor.
	 */
	public static function checkTuple($tuple) {
		if (!in_array($tuple['action'], array('create', 'read', 'update', 'delete', 'list', 'display')) && !$tuple['id']) {
			$tuple['id']     = $tuple['action'];
			$tuple['action'] = 'display';
		}
		return $tuple;
	}
}
