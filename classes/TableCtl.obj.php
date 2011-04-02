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
	//Array Constants
	public static $P_READONLY = array(
		'read', 'display', 'list', 'search'
	);
	public static $P_USAGE = array(
		'read', 'display', 'list', 'search', 'create', 'replace', 'import', 'update', 'toggle'
	);
	public static $P_FULL = array(
		'read', 'display', 'list', 'search', 'create', 'replace', 'import', 'update', 'toggle', 'delete'
	);
	
	/**
	 * Return Tab Links for this area
	 *
	 * Override this function if you want to customize the Tab Links for an area.
	 */
	protected function getTabLinks($action) {
		$links = array();
		if ($action != 'list') {
			if (Permission::check('list', Controller::$area)) {
				$links[] = array('link' => '?q=' . Controller::$area . '/list', 'text' => 'List');
			}
			if (Controller::$action == 'display' && !empty(Controller::$parameters[0]) && Permission::check('update', Controller::$area)) {
				$links[] = array('link' => '?q=' . Controller::$area . '/update/' . Controller::$parameters[0], 'text' => 'Update');
			}
			if (Controller::$action == 'update' && !empty(Controller::$parameters[0]) && Permission::check('display', Controller::$area)) {
				$links[] = array('link' => '?q=' . Controller::$area . '/display/' . Controller::$parameters[0], 'text' => 'Display');
			}
			if (Permission::check('create', Controller::$area)) {
				$links[] = array('link' => '?q=' . Controller::$area . '/create', 'text' => 'Create');
			}
		}
		return $links;
	}
	
	public function getHomeMethods() {
		$methods = parent::getHomeMethods();
		$methods = array_filter($methods, create_function('$var', "return !in_array(\$var, array('display', 'replace', 'read', 'update', 'delete', 'toggle'));"));
		return $methods;
	}
	
	public static function define_display() {
		return array(
			'description' => 'Display a specified record',
			'parameters'  => array(
				'id' => array(
					'description' => 'The id of the record. Can be anything that uniquely defines the record (as long as it\'s defined in the getRetrieveSQL statement).',
					'type'        => 'mixed',
				),
			),
			'return'      => array(
				'description' => 'The DB Object to display',
				'type'        => 'DBObject',
			),
		);
	}

	/**
	 * Display does nothing but display (hahaha) the content fetched by action_read
	 */
	public function action_display($id) {
		$id = Hook::run('table_display', 'pre', array($id), array('toret' => $id));
		$object = self::action_read($id, 'dbobject');
		$object = Hook::run('table_display', 'post', array($object), array('toret' => $object));
		return $object;
	}
	
	/**
	 * Output an object in HTML
	 *
	 * Override this function if you want to customize the way a record is displayed.
	 * You can also just create a template named $areaname.display.tpl.php to customize the HTML.
	 */
	public function html_display($object) {
		if (!($object instanceof DBObject)) {
			Controller::whoops('Invalid Object Returned');
			return $object;
		}
		Backend::add('Object', $object);
		Backend::add('TabLinks', $this->getTabLinks('display'));
		Backend::add('Sub Title', $object->getMeta('name'));
		$template_file = $object->getArea() . '.display.tpl.php';
		if (Render::checkTemplateFile($template_file)) {
			Backend::addContent(Render::renderFile($template_file));
		} else {
			//TODO It's a bit of a hack to redirect just because we can't generate the template
			if (Render::createTemplate($template_file, 'std_display.tpl.php')) {
				Backend::addSuccess('Created template for ' . $object->getMeta('name') . ' display');
				Controller::redirect();
			} else {
				Backend::addError('Could not create template file for ' . $object->getMeta('name') . '::display');
			}
		}
		return $object;
	}
	
	public function json_display($result) {
		if ($result instanceof DBObject) {
			switch (true) {
			case !empty($result->array):
				$result = $result->array;
				break;
			case !empty($result->object):
				$result = $result->object;
				break;
			default:
				$result = false;
				break;
			}
		}
		return $result;
	}
	
	public static function define_list() {
		return array(
			'description' => 'List a number of records',
			'parameters'  => array(
				'start' => array(
					'description' => 'The offset / start of the record list.',
					'type'        => 'numeric',
					'default'     => 0,
				),
				'count' => array(
					'description' => 'The number of records to return.',
					'type'        => 'numeric',
					'default'     => 5,
				),
				'options' => array(
					'description' => 'Options affecting the records returned.',
					'type'        => 'array',
				),
			),
			'optional' => array(
				'order' => array(
					'description' => 'Which field should be used to order the records',
					'type'        => 'string',
					'default'     => false,
				),
			),
			'return'      => array(
				'description' => 'The DB Object to list',
				'type'        => 'DBObject',
			),
		);
	}

	/**
	 * Action for listing an area's records
	 */
	public function action_list($start, $count, array $options = array()) {
		$p_options = API::extract(self::define_list());
		$options   = array_merge($options, $p_options);
		return self::do_list($start, $count, $options);
	}
	
	public static function do_list($start, $count, array $options = array()) {
		$object = self::getObject(get_called_class());
		if (!($object instanceof DBObject)) {
			Controller::whoops('Invalid Object Returned');
			return false;
		}
		$toret = true;
		if ($start === 'all') {
			$limit = 'all';
		} else if ($start || $count) {
			$limit = (int)$start . ', ' . (int)$count;
		} else {
			$limit = false;
		}
		if (!empty($options['order'])) {
			//TODO Check for commma delimited list of fields to order by
			if (!in_array($options['order'], array_keys($object->getMeta('fields')))) {
				//Backend::addNotice('Invalid Order Field');
				unset($options['order']);
			}
		}

		$object->read(array_merge(array('limit' => $limit), $options));
		return $object;
	}
	
	/**
	 * Output a list of records in HTML
	 *
	 * Override this function if you want to customize the way the list of records are displayed.
	 * You can also just create a template named $areaname.list.tpl.php to customize the HTML.
	 */
	public function html_list($object) {
		if (!($object instanceof DBObject)) {
			Controller::whoops('Invalid Object Returned');
			return $object;
		}
		
		Backend::add('Object', $object);
		Backend::add('TabLinks', $this->getTabLinks('list'));
		Backend::add('Sub Title', $object->getMeta('name'));

		Backend::addScript(SITE_LINK . 'scripts/jquery.js');
		Backend::addScript(SITE_LINK . 'scripts/table_list.js');
		$template_file = $object->getArea() . '.list.tpl.php';
		if (Render::checkTemplateFile($template_file)) {
			Backend::addContent(Render::renderFile($template_file));
		} else {
			//TODO It's a bit of a hack to redirect just because we can't generate the template
			//if (Render::createTemplate($template_file, 'std_list.tpl.php')) {
				//Backend::addSuccess('Created template for ' . $object->getMeta('name') . ' list');
				//Controller::redirect();
			//} else {
				//Backend::addError('Could not create template file for ' . $object->getMeta('name') . '::list');
			//}
			Backend::addContent(Render::renderFile('std_list.tpl.php'));
		}
		return $object;
	}
	
	public function json_list($result) {
		if ($result instanceof DBObject) {
			Backend::addContent(array('list_count' => $result->list_count), array('as_is' => 1));
			$result = $result->list;
		}
		return $result;
	}
	
	public static function define_search() {
		return array(
			'description' => 'Search and return records',
			'parameters'  => array(
				'term' => array(
					'description' => 'The term to search for.',
					'type'        => 'numeric',
					'default'     => 0,
				),
				'start' => array(
					'description' => 'The offset / start of the record list.',
					'type'        => 'numeric',
					'default'     => 0,
				),
				'count' => array(
					'description' => 'The number of records to return.',
					'type'        => 'numeric',
					'default'     => 5,
				),
				'options' => array(
					'description' => 'Options affecting the records returned.',
					'type'        => 'array',
				),
			),
			'return'      => array(
				'description' => 'The DB Object searched',
				'type'        => 'DBObject',
			),
		);
	}

	/**
	 * Action for searching an area's records
	 */
	public function action_search($term, $start, $count, array $options = array()) {
		$object = self::getObject(get_class($this));
		if (!$object instanceof DBObject) {
			Controller::whoops('Invalid Object Returned');
			return false;
		}
		$fields = $object->getSearchFields();
		if (!$fields || !is_array($fields)) {
			return false;
		}
		if (empty($term)) {
			return $object;
		}
		$filter = array();
		foreach($fields as $field) {
			$filter[] = $field . " LIKE CONCAT('%', :term, '%')";
		}
		$filter = implode(' OR ', $filter);
		if (!array_key_exists('filters', $options)) {
			$options['filters'] = array();
		}
		$options['filters'][] = $filter;

		if (!array_key_exists('parameters', $options)) {
			$options['parameters'] = array();
		}
		$options['parameters'][':term'] = $term;
		return $this->action_list($start, $count, $options);
	}
	
	public function html_search($object) {
		if (!($object instanceof DBObject)) {
			Controller::whoops('Invalid Object Returned');
			return $object;
		}
		
		Backend::add('Object', $object);
		Backend::add('TabLinks', $this->getTabLinks('list'));
		Backend::add('Sub Title', 'Searching ' . $object->getMeta('name'));
		Backend::add('term', Controller::$parameters[0]);

		Backend::addScript(SITE_LINK . 'scripts/jquery.js');
		Backend::addScript(SITE_LINK . 'scripts/table_list.js');

		$template_file = $object->getArea() . '.search_results.tpl.php';
		if (!Render::checkTemplateFile($template_file)) {
			$template_file = 'std_search_results.tpl.php';
		}
		Backend::addContent(Render::renderFile($template_file));
		return $object;
	}
	
	public static function define_create() {
		$result = array(
			'description' => 'Create a new record. Data for the new record should be passed as POST data.',
			'parameters'  => array(
			),
			'return'      => array(
				'description' => 'The DB Object searched',
				'type'        => 'DBObject',
			),
		);
		$class = get_called_class();
		$model = $class . 'Obj';
		if (!class_exists($model, true)) {
			return false;
		}
		$model = new $model();
		foreach($model->getMeta('fields') as $name => $options) {
			$options = is_array($options) ? $options : array('type' => $options);
			$type    = array_key_exists('type', $options) ? $options['type'] : 'string';
			if (in_array($type, array('primarykey', 'dateadded', 'lastmodified'))) {
				continue;
			}
			$param_type = empty($options['required']) ? 'optional' : 'required';
			$result[$param_type][$name] = array('type' => $type);
			if (!empty($options['description'])) {
				$result[$param_type][$name]['description'] = $options['description'];
			}
			if (!empty($options['default'])) {
				$result[$param_type][$name]['default'] = $options['default'];
			}
		}
		return $result;
	}

	/**
	 * Action for creating a record in an area
	 */
	public function action_create() {
		$object = self::getObject(get_class($this));
		if (!$object instanceof DBObject) {
			Controller::whoops('Invalid Object Returned');
			return $object;
		}
		$result = true;
		//We need to check if the post data is valid in some way?
		$data = $object->fromPost();
		if (is_post()) {
			$data = Hook::run('create', 'pre', array($data, $object), array('toret' => $data));
			if ($object->create($data)) {
				Hook::run('create', 'post', array($data, $object));
				Backend::addSuccess($object->getMeta('name') . ' Added');
				$result = $object;
			} else {
				Backend::addError('Could not add ' . $object->getMeta('name'));
				$result = false;
			}
			if (!empty($object->error_msg)) {
				Backend::addNotice($object->error_msg);
			}
		}
		Backend::add('obj_values', $data);
		return $result;
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
			Controller::redirect('?q=' . $result->getArea() . '/' . $result->getMeta('id'));
			break;
		case $result === false:
			//This prevents the form from being populated with the failed data
			//Controller::redirect('previous');
			//break;
		case $result:
		default:
			$object = self::getObject(get_class($this));
			if ($object) {
				Backend::add('Object', $object);
				Backend::add('TabLinks', $this->getTabLinks('create'));
				Backend::add('Sub Title', 'Add ' . $object->getMeta('name'));
				$template_file = $object->getArea() . '.form.tpl.php';
				if (Render::checkTemplateFile($template_file)) {
					Backend::addContent(Render::renderFile($template_file));
				} else {
					//TODO It's a bit of a hack to redirect just because we can't generate the template
					if (Render::createTemplate($template_file, 'std_form.tpl.php')) {
						Backend::addSuccess('Created template for ' . $object->getMeta('name') . ' form');
						Controller::redirect();
					} else {
						Backend::addError('Could not create template file for ' . $object->getMeta('name') . '::create');
					}
				}
			}
			break;		
		}
		return $result;
	}
	
	public static function define_replace() {
		$result = array(
			'description' => 'Create a new record or replace it if it already exists. Data for the new record should be passed as POST data.',
			'parameters'  => array(
			),
			'return'      => array(
				'description' => 'The DB Object searched',
				'type'        => 'DBObject',
			),
		);
		$class = get_called_class();
		$model = $class . 'Obj';
		if (!class_exists($model, true)) {
			return false;
		}
		$model = new $model();
		foreach($model->getMeta('fields') as $name => $options) {
			$options = is_array($options) ? $options : array('type' => $options);
			$type    = array_key_exists('type', $options) ? $options['type'] : 'string';
			if (in_array($type, array('primarykey', 'dateadded', 'lastmodified'))) {
				continue;
			}
			$param_type = empty($options['required']) ? 'optional' : 'required';
			$result[$param_type][$name] = array('type' => $type);
			if (!empty($options['description'])) {
				$result[$param_type][$name]['description'] = $options['description'];
			}
			if (!empty($options['default'])) {
				$result[$param_type][$name]['default'] = $options['default'];
			}
		}
		return $result;
	}

	public function action_replace() {
		$toret = false;
		$object = self::getObject(get_class($this));
		if (!($object instanceof DBObject)) {
			Controller::whoops('Invalid Object Returned');
			return $object;
		}
		$toret = true;
		//We need to check if the post data is valid in some way?
		$data = $object->fromPost();
		if (is_post()) {
			$data = Hook::run('replace', 'pre', array($data, $object), array('toret' => $data));
			if ($object->replace($data)) {
				Hook::run('replace', 'post', array($data, $object));
				Backend::addSuccess($object->getMeta('name') . ' Added');
				$toret = $object;
			} else {
				$toret = false;
				Backend::addError('Could not replace ' . $object->getMeta('name'));
			}
		}
		Backend::add('obj_values', $data);
	}
	
	public function html_replace($result) {
		return $this->html_create($result);
	}
	
	public static function define_read() {
		return array(
			'description' => 'Read the specified record.',
			'parameters'  => array(
				'id' => array(
					'description' => 'The id of the record to read.',
					'type'        => 'numeric',
					'default'     => 0,
				),
				'mode' => array(
					'description' => 'The format in which the record should be returned.',
					'type'        => 'string',
					'default'     => 'array',
					'range'       => array('array', 'object', 'dbobject'),
				),
			),
			'return' => array(
				'description' => 'The record in the specified format',
				'type'        => 'mixed',
			),
		);
	}

	/**
	 * Action for reading a record in an area
	 */
	public function action_read($id, $mode = 'array') {
		return call_user_func_array(array(class_name(Controller::$area), 'retrieve'), array($id, $mode));
	}
	
	public static function define_update() {
		$result = array(
			'description' => 'Update a record. The update data for the record should be passed as POST data.',
			'parameters'  => array(
				'id' => array(
					'description' => 'The id of the record to update.',
					'type'        => 'numeric',
					'default'     => 0,
				),
			),
			'return'      => array(
				'description' => 'The DB Object updated',
				'type'        => 'DBObject',
			),
		);
		$class = get_called_class();
		$model = $class . 'Obj';
		if (!class_exists($model, true)) {
			return false;
		}
		$model = new $model();
		foreach($model->getMeta('fields') as $name => $options) {
			$options = is_array($options) ? $options : array('type' => $options);
			$type    = array_key_exists('type', $options) ? $options['type'] : 'string';
			if (in_array($type, array('primarykey', 'dateadded', 'lastmodified'))) {
				continue;
			}
			$param_type = empty($options['required']) ? 'optional' : 'required';
			$result[$param_type][$name] = array('type' => $type);
			if (!empty($options['description'])) {
				$result[$param_type][$name]['description'] = $options['description'];
			}
			if (!empty($options['default'])) {
				$result[$param_type][$name]['default'] = $options['default'];
			}
		}
		return $result;
	}

	/**
	 * Action for updating a record in an area
	 */
	public function action_update($id) {
		$toret = false;
		$object = self::getObject(get_class($this), $id);
		if (!($object instanceof DBObject)) {
			Controller::whoops('Invalid Object Returned');
			return $object;
		}
		if (!$object->array) {
			Backend::addError('The ' . $object->getMeta('name') . ' does not exist');
			return false;
		}

		$result = true;
		//We need to check if the post data is valid in some way?
		if (is_post()) {
			$data = $object->fromPost();
			$data = Hook::run('update', 'pre', array($data, $object), array('toret' => $data));
			if ($object->update($data) !== false) {
				$result = $object;
				Backend::addSuccess($object->getMeta('name') . ' Modified');
			} else {
				Backend::addError('Could not update ' . $object->getMeta('name'));
				$result = false;
			}
		} else {
			$data = $object->array;
		}
		Backend::add('obj_values', $data);
		return $result;
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
			$object = self::getObject(get_class($this));
			if ($object) {
				Backend::add('Object', $object);
				Backend::add('TabLinks', $this->getTabLinks('update'));
				Backend::add('Sub Title', 'Update ' . $object->getMeta('name'));
				$template_file = $object->getArea() . '.form.tpl.php';
				if (Render::checkTemplateFile($template_file)) {
					Backend::addContent(Render::renderFile($template_file));
				} else {
					//TODO It's a bit of a hack to redirect just because we can't generate the template
					Render::createTemplate($template_file, 'std_form.tpl.php');
					Backend::addSuccess('Created template for ' . $object->getMeta('name') . ' form');
					Controller::redirect();
				}
			}
			break;
		default:
			break;
		}
		return $result;
	}
	
	public static function define_delete() {
		return array(
			'description' => 'Delete the specified record.',
			'parameters'  => array(
				'id' => array(
					'description' => 'The id of the record to delete.',
					'type'        => 'numeric',
					'default'     => 0,
				),
			),
			'return' => array(
				'description' => 'If the record was succesfully deleted.',
				'type'        => 'boolean',
			),
		);
	}

	public function post_delete($id) {
		$object = self::getObject(get_class($this), $id);
		if (!($object instanceof DBObject)) {
			Controller::whoops('Invalid Object Returned');
			return $object;
		}
		if ($object->array) {
			if ($object->delete()) {
				return true;
			}
		} else {
			Backend::addError('The ' . $object->getMeta('name') . ' does not exist');
		}
		return false;
	}

	public function html_delete($result) {
		if ($result === true) {
			Backend::addSuccess('Record has been removed');
		}
		Controller::redirect('?q=' . Controller::$area . '/list');
	}
	
	public static function define_toggle() {
		return array(
			'description' => 'Toggle a boolean field on the specified record.',
			'parameters'  => array(
				'id' => array(
					'description' => 'The id of the record to toggle.',
					'type'        => 'numeric',
					'default'     => 0,
				),
				'field' => array(
					'description' => 'The name of the field to toggle. It must be a boolean field.',
					'type'        => 'string',
				),
			),
			'return' => array(
				'description' => 'The DBObject toggled.',
				'type'        => 'DBObject',
			),
		);
	}

	/**
	 * @todo Make this a POST only
	 */
	public function action_toggle($id, $field) {
		$object = self::getObject(get_class($this), $id);
		if (!($object instanceof DBObject)) {
			Controller::whoops('Invalid Object Returned');
			return $object;
		}
		$fields = $object->getMeta('fields');
		if (!array_key_exists($field, $fields) && $fields[$field] == 'boolean') {
			Controller::whoops('Invalid Toggle Field', 400);
			return false;
		}
		$data = array(
			$field => !$object->array[$field],
		);
		if ($object->update($data)) {
			return $object;
		}
		return false;
	}
	
	public function html_toggle($result) {
		if ($result instanceof DBObject) {
			Controller::redirect('?q=' . Controller::$area . '/' . $result->getMeta('id'));
		}
		return $result;
	}
	
	/**
	 * Action for importing records in an area
	 */
	public function action_import($data = false) {
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true)) {
			$object = new $obj_name();
			if (is_post()) {
				if (empty($_FILES) || !array_key_exists('import_file', $_FILES)) {
					Backend::addError('There is a problem with the HTML Form');
					return false;
				}
				$file = $_FILES['import_file'];
				if ($file['error']) {
					switch($file['error']) {
					case UPLOAD_ERR_INI_SIZE:
						$msg = 'File exceeds PHP size limit';
						break;
					case UPLOAD_ERR_FORM_SIZE:
						$msg = 'File exceeds form size limit';
						break;
					case UPLOAD_ERR_PARTIAL:
						$msg = 'File unload partially uploaded';
						break;
					case UPLOAD_ERR_NO_FILE:
						$msg = 'No file uploaded';
						break;
					case UPLOAD_ERR_NO_TMP_DIR:
						$msg = 'Temporary folder doesn\'t exist';
						break;
					case UPLOAD_ERR_CANT_WRITE:
						$msg = 'Can\'t create temporary file';
						break;
					case UPLOAD_ERR_EXTENSION:
						$msg = 'File upload prohibited by PHP extension';
						break;
					}
					Backend::addError('Could not upload file: ' . $msg);
					return false;
				}
				if (!in_array($file['type'], array('text/csv', 'application/octet-stream'))) {
					Backend::addError('This import can only handle CSV files. The uploaded file is ' . $file['type']);
					return false;
				}
				$importer_name = get_class($this) . 'Importer';
				if (!class_exists($importer_name, true)) {
					$importer_name = 'GenericImporter';
				}
				$count = call_user_func_array(array($importer_name, 'import'), array($this, $file['tmp_name'], $data));
				$error = call_user_func(array($importer_name, 'getLastError'));
				if (!empty($error)) {
					if (!$count) {
						Backend::addError($error);
					} else {
						Backend::addNotice($error);
					}
				}
				return $count;
			}
			return $object;
		} else {
			Controller::whoops();
		}
		return false;
	}
	
	public function html_import($result) {
		switch (true) {
		case $result instanceof DBObject:
			Backend::add('Sub Title', 'Import');
			Backend::add('Object', $result);
			Backend::add('Sub Title', 'Import ' . $result->getMeta('name'));
			$template_file = singularize(computerize(class_name(Controller::$area))) . '.import.tpl.php';
			if (!Render::checkTemplateFile($template_file)) {
				$template_file = 'std_import.tpl.php';
			}
			Backend::addContent(Render::renderFile($template_file));
			break;
		case is_numeric($result) && $result >= 0:
			Backend::addSuccess($result . ' records imported');
			Controller::redirect('?q=' . Controller::$area . '/list');
			break;
		default:
			Controller::redirect();
			break;
		}
		return $result;
	}
	
	public static function retrieve($parameter = false, $return = 'array', array $options = array()) {
		if (is_null($parameter)) {
			return null;
		}
		//jrgns 2011-02-17 Why is this here? It's Dodgy...
		if ($parameter === false && $return == 'array') {
			$return = 'dbobject';
		}

		$toret = null;
		//We've defined get_called_class in functions.inc.php for servers with PHP < 5.3.0
		$obj_name = get_called_class() . 'Obj';
		if ($obj_name && class_exists($obj_name, true)) {
			$object = new $obj_name();
			if ($parameter !== false) {
				$query = $object->getRetrieveSQL();
				if ($query) {
					if ($parameter == 'random') {
						if ($query instanceof Query) {
							$query
								->setFilter(array())
								->setOrder(array('RAND()'))
								->limit(1);
						} else {
							$query .= ' ORDER BY RAND() LIMIT 1';
						}
					}
					$object->read(
						array(
							'query'      => $query,
							'parameters' => array(':parameter' => $parameter),
							'mode'       => ($return == 'dbobject' ? 'object' : $return)
						)
					);
				} else {
					$object = null;
				}
			} else {
				$object->read();
			}
			if (!empty($object->error_msg)) {
				Backend::addError($object->error_msg);
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
	 * Use this function to set default parameters for specific actions
	 *
	 * It's also a good way to transform request variables to proper parameters
	 */
	public static function checkParameters($parameters) {
		//If there's no action, only a ID, use the request verb to determine the action
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
		//List instead of index
		if (Controller::$action == 'index') {
			Controller::setAction('list');
		}
		//Defaults for List
		if (Controller::$action == 'list') {
			if (!isset(Controller::$parameters[0])) {
				$parameters[0] = 0;
			}
			if (!isset(Controller::$parameters[1])) {
				$parameters[1] = Value::get('list_length', 5);
			}
		}
		//Defaults for Search
		if (Controller::$action == 'search') {
			//Get the search term from the request variable. It's always the first parameter
			if ($term = Controller::getVar('term')) {
				array_unshift($parameters, $term);
			} else if (!count($parameters)) {
				$parameters[0] = '';
			}
			if (!isset(Controller::$parameters[1])) {
				$start = Controller::getVar('start', FILTER_VALIDATE_INT);
				$parameters[1] = is_null($start) ? 0 : $start;
			}
			if (!isset(Controller::$parameters[2])) {
				$count = Controller::getVar('count', FILTER_VALIDATE_INT);
				$parameters[2] = is_null($count) ? Value::get('list_length', 5) : $count;
			}
		}
		//Get the delete_id from the request variable
		if (Controller::$action == 'delete' && empty($parameters[0]) && ($delete_id = Controller::getVar('delete_id', FILTER_VALIDATE_INT))) {
			$parameters[0] = $delete_id;
		}
		return $parameters;
	}
	
	public static function getObject($obj_name = false, $id = false) {
		$toret = false;
		$obj_name = $obj_name ? class_name($obj_name) : class_name(get_called_class());
		$obj_name .= 'Obj';
		if (Component::isActive($obj_name)) {
			if ($id) {
				$toret = new $obj_name($id);
			} else {
				$toret = new $obj_name();
			}
		}
		return $toret;
	}
	
	public static function define_install() {
		return array(
			'description' => 'Install the component',
			'return'      => array(
				'type'        => 'boolean',
				'description' => 'Whether or not the installation was successful',
			),
			'optional'  => array(
				'install_model' => array(
					'type'        => 'boolean',
					'description' => 'If the model should be installed. This will create the DB Table or Data Source if it doesn\'t exist.',
				),
				'drop_table' => array(
					'type'        => 'boolean',
					'description' => 'If the Data Source should be destroyed if it exists.',
				),
			),
		);
	}
	
	public static function install(array $options = array()) {
		$install_model = array_key_exists('install_model', $options) ? $options['install_model'] : true;

		$toret = parent::install($options);
		$class = get_called_class();
		if ($class && class_exists($class, true)) {
			if ($install_model) {
				$toret = self::installModel($class . 'Obj', $options);
			}
		}
		return $toret;
	}

	public static function installModel($model, array $options = array()) {
		$toret = false;
		if (class_exists($model, true)) {
			$model = new $model();
			$toret = $model->install($options);
			if (!$toret) {
				Backend::addError('Could not install ' . get_class($model) . ' Model: ' . $model->error_msg);
			}
		} else {
			Backend::addError($model . ' does not exist');
		}
		return $toret;
	}
}
