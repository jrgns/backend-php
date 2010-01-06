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
		if ($action != 'list') {
			if ($this->checkPermissions(array('action' => 'list'))) {
				$links[] = array('link' => '?q=' . Controller::$area . '/list', 'text' => 'List');
			}
			if (Controller::$action == 'display' && $this->checkPermissions(array('action' => 'update')) && !empty(Controller::$parameters[0])) {
				$links[] = array('link' => '?q=' . Controller::$area . '/update/' . Controller::$parameters[0], 'text' => 'Update');
			}
			if (Controller::$action == 'update' && $this->checkPermissions(array('action' => 'update')) && !empty(Controller::$parameters[0])) {
				$links[] = array('link' => '?q=' . Controller::$area . '/display/' . Controller::$parameters[0], 'text' => 'Display');
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
		$object = self::getObject();
		if ($object && $id > 0) {
			$toret = self::action_read($id);
		}
		return $toret;
	}
	
	/**
	 * Action for listing an area's records
	 */
	public function action_list($start, $count) {
		$toret = false;
		$object = self::getObject();
		if ($object) {
			$toret = true;
			if ($start || $count) {
				$limit = "$start, $count";
			} else {
				$limit = false;
			}
			$object->load(array('limit' => $limit));
			$toret = $object;
		} else {
			Controller::whoops();
		}
		return $toret;
	}
	
	/**
	 * Action for creating a record in an area
	 *
	 * TODO: There's a duplication of code between rest_create and action_create... Any ideas on
	 * how to work around this?
	 */
	public function action_create() {
		$toret = false;
		$object = self::getObject();
		if ($object) {
			$toret = true;
			//We need to check if the post data is valid in some way?
			$data = $object->fromPost();
			if (is_post()) {
				$data = Hook::run('create', 'pre', array($data, $object), array('toret' => $data));
				if ($object->create($data)) {
					Hook::run('create', 'post', array($data, $object));
					Controller::addSuccess($object->getMeta('name') . ' Added');
					$toret = $object;
				} else {
					$toret = false;
					Controller::addError('Could not add ' . $object->getMeta('name'));
				}
			}
			Backend::add('obj_values', $data);
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
		$object = self::getObject($id);
		if ($object) {
			if ($object->array) {
				$toret = true;
				//We need to check if the post data is valid in some way?
				if (is_post()) {
					$data = $object->fromPost();
					$data = Hook::run('update', 'pre', array($data, $object), array('toret' => $data));
					if ($object->update($data)) {
						$toret = $object;
						Controller::addSuccess($object->getMeta('name') . ' Modified');
					} else {
						Controller::addError('Could not update ' . $object->getMeta('name'));
					}
				} else {
					$data = $object->array;
				}
				Backend::add('obj_values', $data);
			} else {
				Controller::addError('The ' . $object->getMeta('name') . ' does not exist');
			}
		} else {
			Controller::whoops();
		}
		return $toret;
	}
	
	public function action_delete($id) {
		$toret = false;
		$object = self::getObject($id);
		if ($object && is_post()) {
			if ($object->array) {
				if ($object->delete()) {
					Controller::addSuccess('Record has been removed');
					$toret = true;
				}
			} else {
				Controller::addError('The ' . $object->getMeta('name') . ' does not exist');
			}
		} else {
			Controller::whoops();
		}
		return $true;
	}
	
	public function action_toggle($id, $field, $should_redirect = true) {
		$toret = null;
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true)) {
			$toret = new $obj_name($id);
			$fields = $toret->getMeta('fields');
			if (array_key_exists($field, $fields) && $fields[$field] == 'boolean') {
				$data = array(
					$field => !$toret->array[$field],
				);
				if ($toret->update($data)) {
					if ($should_redirect) {
						Controller::redirect('?q=' . Controller::$area . '/' . $id);
					}
				} else {
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
				if (Render::checkTemplateFile($template_file)) {
					Controller::addContent(Render::renderFile($template_file));
				} else {
					//TODO It's a bit of a hack to redirect just because we can't generate the template
					Render::createTemplate($template_file, 'std_display.tpl.php');
					Controller::addSuccess('Created template for ' . $object->getMeta('name') . ' display');
					Controller::redirect();
				}
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
				if (Render::checkTemplateFile($template_file)) {
					Controller::addContent(Render::renderFile($template_file));
				} else {
					//TODO It's a bit of a hack to redirect just because we can't generate the template
					//Render::createTemplate($template_file, 'std_list.tpl.php');
					//Controller::addSuccess('Created template for ' . $object->getMeta('name') . ' list');
					//Controller::redirect();
					Controller::addContent(Render::renderFile('std_list.tpl.php'));
				}
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
	public function html_create($result) {
		switch (true) {
		case $result instanceof DBObject:
			Controller::redirect('?q=' . $result->getArea() . '/display/' . $result->getMeta('id'));
			break;
		case $result:
			$object = self::getObject();
			if ($object) {
				Backend::add('Object', $object);
				Backend::add('TabLinks', $this->getTabLinks('create'));
				Backend::add('Sub Title', 'Add ' . $object->getMeta('name'));
				$template_file = $object->getArea() . '.form.tpl.php';
				if (Render::checkTemplateFile($template_file)) {
					Controller::addContent(Render::renderFile($template_file));
				} else {
					//TODO It's a bit of a hack to redirect just because we can't generate the template
					Render::createTemplate($template_file, 'std_form.tpl.php');
					Controller::addSuccess('Created template for ' . $object->getMeta('name') . ' form');
					Controller::redirect();
				}
			}
			break;
		default:
			break;
		}
		return true;
	}
	
	/**
	 * Output a form to update a record in HTML
	 *
	 * Override this function if you want to customize the way the creation of a record is displayed.
	 * You can also just create a template named $areaname.form.tpl.php to customize the HTML.
	 */
	public function html_update($result) {
		switch (true) {
		case $result instanceof DBObject:
			Controller::redirect('?q=' . $result->getArea() . '/display/' . $result->getMeta('id'));
			break;
		case $result:
			$object = self::getObject();
			if ($object) {
				Backend::add('Object', $object);
				Backend::add('TabLinks', $this->getTabLinks('update'));
				Backend::add('Sub Title', 'Update ' . $object->getMeta('name'));
				$template_file = $object->getArea() . '.form.tpl.php';
				if (Render::checkTemplateFile($template_file)) {
					Controller::addContent(Render::renderFile($template_file));
				} else {
					//TODO It's a bit of a hack to redirect just because we can't generate the template
					Render::createTemplate($template_file, 'std_form.tpl.php');
					Controller::addSuccess('Created template for ' . $object->getMeta('name') . ' form');
					Controller::redirect();
				}
			}
			break;
		default:
			break;
		}
		return true;
	}

	public function html_import($result) {
		Backend::add('Sub Title', 'Import');
		if ($result instanceof DBObject) {
			Backend::add('Sub Title', 'Import ' . $object->getMeta('name'));
		}
	}
	
	/**
	 * RESTful action for creating a record in an area
	 *
	 * This function should be called when the create action is called in a RESTful app.
	 * TODO: There's a duplication of code between rest_create and action_create... Any ideas on
	 * how to work around this?
	 */
	public function rest_create() {
		$object = self::getObject();
		if ($object) {
			$toret = true;
			//We need to check if the post data is valid in some way?
			$data = $object->fromPost();
			$data = Hook::run('create', 'pre', array($data, $object), array('toret' => $data));
			if ($object->create($data)) {
				Hook::run('create', 'post', array($data, $object));
				Controller::addSuccess($object->getMeta('name') . ' Added');
				$toret = $object;
			} else {
				$toret = false;
				Controller::addError('Could not add ' . $object->getMeta('name'));
			}
		}
		return $toret;
	}
	
	public static function retrieve($parameter = false, $return = 'array') {
		if (!$parameter && $return == 'array') {
			$return = 'dbobject';
		}

		$toret = null;
		//We've defined get_called_class in functions.inc.php for servers with PHP < 5.3.0
		$obj_name = get_called_class() . 'Obj';
		if ($obj_name && class_exists($obj_name, true)) {
			$object = new $obj_name();
			if ($parameter) {
				$query = $object->getRetrieveSQL();
				if ($query) {
					$object->load(array('query' => $query, 'parameters' => array(':parameter' => $parameter), 'mode' => ($return == 'dbobject' ? 'object' : $return)));
				} else {
					$object = null;
				}
			} else {
				$object->load();
			}
			if (!empty($object->last_error)) {
				Controller::addError($object->last_error);
			} else if ($object) {
				switch ($return) {
				case 'list':
					$toret = $object->list;
					break;
				case 'array':
					$toret = $object->array;
					break;
				case 'object':
					$toret = $object->object;
					break;
				case 'dbobject':
				default:
					$toret = $object;
					break;
				}
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
				Controller::setAction('create');
				break;
			case 'POST':
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
		if (Controller::$action == 'list' && !isset(Controller::$parameters[0])) {
			$parameters[0] = 0;
		}
		if (Controller::$action == 'list' && !isset(Controller::$parameters[1])) {
			$parameters[1] = Value::get('list_length', 5);
		}
		if (Controller::$action == 'delete' && empty($parameters[0]) && !empty($_POST['delete_id'])) {
			$parameters[0] = $_POST['delete_id'];
		}
		return $parameters;
	}
	
	public static function getObject($id = false) {
		$toret = false;
		$component = class_name(Controller::$area);
		$obj_name  = class_name(Controller::$area) . 'Obj';
		if (Component::isActive($obj_name) && class_exists($obj_name, true)) {
			if ($id) {
				$toret = new $obj_name($id);
			} else {
				$toret = new $obj_name();
			}
		}
		return $toret;
	}
	
	public function action_install() {
		self::install();
		return true;
	}
	
	public static function install(array $options = array()) {
		$install_model = array_key_exists('install_model', $options) ? $options['install_model'] : true;

		$toret = parent::install($options);
		$class = get_called_class();
		if ($class && class_exists($class, true)) {
			if ($install_model) {
				$toret = self::installModel($class . 'Obj');
			}
		}
		return $toret;
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
