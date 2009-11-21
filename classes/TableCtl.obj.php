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
	 * Return Tab Links for this area
	 *
	 * Override this function if you want to customize the Tab Links for an area.
	 */
	protected function getTabLinks($action) {
		$links = array();
		//TODO Check permissions!
		if ($action != 'list') {
			if ($this->checkPermissions(array('action' => 'list'))) {
				$links[] = array('link' => '?q=' . Controller::$area . '/list', 'text' => 'List');
			}
			if (Controller::$action == 'display' && $this->checkPermissions(array('action' => 'update'))) {
				$links[] = array('link' => '?q=' . Controller::$area . '/update/' . $id, 'text' => 'Update');
			}
			if ($this->checkPermissions(array('action' => 'create'))) {
				$links[] = array('link' => '?q=' . Controller::$area . '/create', 'text' => 'Create');
			}
		}
		return $links;
	}
	
	/**
	 * Display does nothing but display (hahaha) the content fetched by DBObject::read
	 */
	public function action_display($id) {
		$toret = false;
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true) && $id !== 'home' && $id > 0) {
			$toret = self::action_read($id);
		} else {
			Controller::$parameters['action'] = 'list';
			$toret = $this->action_list();
		}
		return $toret;
	}
	
	/**
	 * Action for listing an area's records
	 */
	public function action_list($count) {
		$toret = false;
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true)) {
			$object = new $obj_name();
			$object->load(array('limit' => $count));
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
			//We need to check if the post data is valid in some way?
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
	public function action_read($id) {
		$toret = null;
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true)) {
			$toret = new $obj_name($id);
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
	public function action_update($id) {
		$toret = false;
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true) && $id !== 'home' && $id > 0) {
			$object = new $obj_name($id);
			//We need to check if the post data is valid in some way?
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
			$id = $id;
			if (empty($id) && !empty($_POST['delete_id'])) {
				Controller::$parameters['id'] = $_POST['delete_id'];
			}
			$obj_name = (class_name(Controller::$area) . 'Obj');
			$object = new $obj_name($id);
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
	
	public function action_toggle($id, $field) {
		$toret = null;
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true)) {
			$toret = new $obj_name($id);
			$fields = $toret->getMeta('fields');
			if (array_key_exists($field, $fields) && $fields[$field] == 'boolean') {
				$data = array(
					$field => !$toret->array[$field],
				);
				if (!$toret->update($data)) {
					$toret = false;
				}
			} else {
				Controller::whoops('Invalid Toggle field');
			}
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
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true)) {
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

	public function html_import($result) {
		Backend::add('Sub Title', 'Import');
		if ($result instanceof DBObject) {
			Backend::add('Sub Title', 'Import ' . $object->getMeta('name'));
		}
	}
	
	public static function retrieve($options = array()) {
		$toret = false;
		if (!(is_array($options) || is_object($options))) {
			$options = array('id' => $options);
		}
		$id     = array_key_exists('id', $options) ? $options['id'] : false;
		$return = array_key_exists('return', $options) ? $options['return'] : false;

		//We've defined get_called_class in functions.inc.php for servers with PHP < 5.3.0
		$obj_name = get_called_class() . 'Obj';
		if (class_exists($obj_name, true)) {
			if ($id) {
				$toret = new $obj_name($id);
			} else {
				$toret = new $obj_name();
			}
			$toret->load();
			switch ($return) {
			case 'list':
				$toret = $toret->list;
				break;
			case 'array':
				$toret = $toret->array;
				break;
			case 'object':
				$toret = $toret->object;
				break;
			default:
				break;
			}
		}
		return $toret;
	}
	
	/**
	 */
	public static function checkParameters($parameters) {
		 if (is_numeric(Controller::$action)) {
			$parameters[0] = Controller::$action;
			switch (strtoupper($_SERVER['REQUEST_METHOD'])) {
			case 'DELETE':
				Controller::setAction('delete');
				break;
			case 'PUT':
				Controller::setAction('update');
				break;
			case 'GET':
			default:
				Controller::setAction('display');
				break;
			}
		}
		if (Controller::$action == 'index') {
			Controller::setAction('list');
		}
		if (Controller::$action == 'list' && empty(Controller::$parameters[0])) {
			$parameters['0'] = Value::get('list_length', 5);
		}
		return $parameters;
	}
	
	public function action_install() {
		self::install();
		return true;
	}
	
	public static function installModel($model) {
		$toret = false;
		if (class_exists($model, true)) {
			$model = new $model();
			$toret = $model->install();
		} else {
			Controller::addError($model . ' does not exist');
		}
		return $toret;
	}
}
