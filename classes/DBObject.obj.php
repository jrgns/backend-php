<?php
/**
 * The file that defines the DBObject class.
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
 * The base model class.
 */
class DBObject {
	private $db;
	protected $meta;
	protected $load_mode = 'array';
	
	public $list = null;
	public $array = null;
	public $object = null;
	public $inserted_id;
	
	//If you set $last_error in a function, reset it in the beginning of the function as well.
	public $last_error = false;

	/**
	 * Construct a DB Object
	 *
	 * children have the following options:
	 * - conditions = array(ClassName => array(field_in_child => value | field_in_this_model))
	 * - relation = single | multiple, defaults to single
	 */
	function __construct($meta = array(), array $options = null) {
		if (!is_array($meta)) {
			if (is_numeric($meta)) {
				$meta = array('id' => $meta);
			} else {
				$meta = array();
			}
		}
		$meta['id']        = array_key_exists('id', $meta)        ? $meta['id']        : false;
		$meta['id_field']  = array_key_exists('id_field', $meta)  ? $meta['id_field']  : 'id';
		$meta['table']     = array_key_exists('table', $meta)     ? $meta['table']     : table_name(get_class($this));
		$meta['database']  = array_key_exists('database', $meta)  ? $meta['database']  : Backend::getConfig('backend.dbs.default.alias', 'default');
		$meta['provider']  = array_key_exists('provider', $meta)  ? $meta['provider']  : Backend::getConfig('backend.provider', 'MySQL');
		$meta['fields']    = array_key_exists('fields', $meta)    ? $meta['fields']    : array();
		$meta['keys']      = array_key_exists('keys', $meta)      ? $meta['keys']      : array();
		$meta['name']      = array_key_exists('name', $meta)      ? $meta['name']      : class_name(get_class($this));
		$meta['objname']   = array_key_exists('objname', $meta)   ? $meta['objname']   : get_class($this);
		$meta['relations'] = array_key_exists('relations', $meta) ? $meta['relations'] : array();
		$this->meta = $meta;

		$options = $options ? $options : array();
		$load_type        = array_key_exists('load_mode', $options) ? $options['load_mode'] : $this->load_mode;
		if ($this->checkConnection()) {
			if ($meta['id']) {
				$this->load(array('mode' => $load_type));
			}
		}
	}
	
	private function checkConnection() {
		$this->last_error = false;
		if (!$this->db instanceof PDO) {
			$this->db = Backend::getDB($this->meta['database']);
			if (!$this->db instanceof PDO) {
				$this->last_error = 'No Database setup';
				return false;
			}
		}
		return ($this->db instanceof PDO);
	}
	
	private function loadRelation($class, $options, $load_mode) {
		$class_name = array_key_exists('model', $options) ? $options['model'] . 'Obj' : $class . 'Obj';
		if (Component::isActive($class_name)) {
			$conds = array();
			$params = array();
			$parent = new $class_name();
			$conditions = array_key_exists('conditions', $options) ? $options['conditions'] : false;
			$type       = array_key_exists('type', $options)       ? $options['type']       : 'single';
			if ($conditions) {
				foreach($conditions as $field => $name) {
					if (is_array($name)) {
						$operator = key($name);
						$name     = current($name);
					} else {
						$operator = '=';
					}
					
					if ($load_mode == 'array') {
						$value = array_key_exists($name, $this->array) ? $this->array[$name] : $name;
					} else if ($type == 'object') {
						$value = array_key_exists($name, $this->object) ? $this->object->$name : $name;
					}
					switch ($operator) {
					case '=':
						$conds[] = '`' . $field . '` = :' . $name;
						break;
					case 'FIND_IN_SET':
					case 'in_set':
						$conds[] = 'FIND_IN_SET(:' . $name . ', `' . $field . '`)';
						break;
					case 'IN':
						$conds[] = '`' . $field . '` IN (' . $value . ')';
						break;
					}
					$params[':' . $name] = $value;
				}
			}
			if ($type == 'multiple') {
				$mode = 'list';
			} else {
				$mode = $load_mode;
			}
			$parent->load(array('conditions' => $conds, 'parameters' => $params, 'mode' => $mode));
			$parent->loadDeep($mode);
			return $parent;
		}
		return null;
	}
	
	private function loadDeep($mode = 'array') {
		if (in_array($mode, array('array', 'object')) && $this->$mode) {
			foreach ($this->meta['relations'] as $class => $options) {
				$type     = array_key_exists('type', $options) ? $options['type'] : 'single';
				$relation = $this->loadRelation($class, $options, $mode);
				switch ($type) {
				case 'multiple':
					if ($mode == 'array') {
						$this->array[$class]  = $relation->list ? $relation->list : array();
					} else if (!$mode == 'object') {
						$this->object->$class = $relation->list ? $relation->list : array();
					}
					break;
				default:
				case 'single':
					if ($mode == 'array') {
						$this->array[$class]  = $relation->array  ? $relation->array  : false;
					} else if (!$mode == 'object') {
						$this->object->$class = $relation->object ? $relation->object : false;
					}
					break;
				}
			}
		}
	}
	
	public function loadArray(array $options = array()) {
		$this->load(array_merge($options, array('mode' => 'array')));
	}
	
	public function loadObject(array $options = array()) {
		$this->load(array_merge($options, array('mode' => 'object')));
	}
	
	public function loadList(array $options = array()) {
		$this->load(array_merge($options, array('mode' => 'list')));
	}
	
	public function load($options = array()) {
		$result = false;
		$this->last_error = false;
		if ($this->checkConnection()) {
			if (!array_key_exists('mode', $options)) {
				if (empty($this->meta['id'])) {
					$options['mode'] = 'list';
				} else {
					$options['mode'] = $this->load_mode;
				}
			}

			if (array_key_exists('query', $options)) {
				$query = $options['query'];
				$params = array_key_exists('parameters', $options) ? $options['parameters'] : array();
			} else {
				list ($query, $params) = $this->getSelectSQL($options);
			}
			if (Controller::$debug >= 2) {
				var_dump('Options:', $options);
				echo 'Query:<br/><pre>';
				echo $query . '</pre>';
				var_dump('Params:', $params);
			}
			if (!empty($query)) {
				if (!($query instanceof Query)) {
					$query = new CustomQuery($query, array('connection' => $this->db));
				}
				$result = $query->execute($params);
				if ($result) {
					switch ($options['mode']) {
					case 'object':
					case 'full_object':
						$this->object = $result->fetch(PDO::FETCH_OBJ);
						$this->array = (array)$this->object;
						$this->loadDeep('object');
						if (empty($this->meta['id'])) {
							$this->meta['id'] = $this->array[$this->meta['id_field']];
						}
						break;
					case 'array':
						$this->array = $result->fetch(PDO::FETCH_ASSOC);
						$this->loadDeep('array');
						if (empty($this->meta['id'])) {
							$this->meta['id'] = $this->array[$this->meta['id_field']];
						}
						break;
					case 'list':
					default:
						$this->list = $result->fetchAll(PDO::FETCH_ASSOC);
						break;
					}
					if ($this->object) {
						$this->object = $this->process($this->object, 'out');
					}
					if ($this->array) {
						$this->array = $this->process($this->array, 'out');
					}
				} else if (!empty($query->last_error)) {
					$this->last_error = $query->last_error;
				}
			} else {
				$this->last_error = 'No Query to load';
			}
		} else {
			$this->last_error = 'DB Connection error';
		}
		return $result;
	}
	
	function process($data, $direction) {
		foreach($data as $name => $value) {
			if (array_key_exists($name, $this->meta['fields'])) {
				$options = $this->meta['fields'][$name];
				if (!is_array($options)) {
					$options = array('type' => $options);
				}
				$type = array_key_exists('type', $options) ? $options['type'] : 'string';
				switch ($type) {
				case 'serialized':
					switch ($direction) {
					case 'in':
						$data[$name] = base64_encode(serialize($value));
						break;
					case 'out':
						$data[$name] = @unserialize(base64_decode($value));
						break;
					}
					break;
				case 'text':
					if (!empty($options['markdown']) && $direction == 'in' && function_exists('markdown')) {
						$data[$name] = markdown($value);
					}
					break;
				default:
					if (Controller::$debug >= 3) {
						var_dump('DBObject::process field', $this->meta['fields'][$name]);
					}
					break;
				}
			}
		}
		return $data;
	}
	
	public function create($data, array $options = array()) {
		$toret = false;
		$this->last_error = false;
		if ($this->checkConnection()) {
			$data = $this->validate($data, 'create', $options);
			if ($data) {
				$data = $this->process($data, 'in');
				list ($query, $params) = $this->getCreateSQL($data, $options);
				$query = new CustomQuery($query, array('connection' => $this->db));
				$toret = $query->execute($params);
				if ($toret) {
					//TODO This will potentially break if there are triggers in use
					$this->inserted_id = $this->db->lastInsertId();
					$this->array       = $data;
					$this->array['id'] = $this->inserted_id;
					$this->meta['id']  = $this->inserted_id;
					$toret             = $this->inserted_id;
					if (array_key_exists('load', $options) ? $options['load'] : true) {
						$this->load();
					}
				} else if (!empty($query->last_error)) {
					$this->last_error = $query->last_error;
				}
			}
		} else {
			$this->last_error = 'DB Connection error';
		}
		return $toret;
	}
	
	public function replace($data, $options = array()) {
		$toret = false;
		$this->last_error = false;
		if ($this->checkConnection()) {
			$data = $this->validate($data, 'create', $options);
			if ($data) {
				$data = $this->process($data, 'in');
				list ($query, $params) = $this->getCreateSQL($data, $options);
				$query = preg_replace('/^INSERT/', 'REPLACE', $query);
				$query = new CustomQuery($query, array('connection' => $this->db));
				$toret = $query->execute($params);
				if ($toret) {
					//TODO This will potentially break if there are triggers in use
					$this->inserted_id = $this->db->lastInsertId();
					$this->meta['id']  = $this->inserted_id;
					$toret             = $this->inserted_id;
					if (array_key_exists('load', $options) ? $options['load'] : true) {
						$this->load();
					}
				} else if (!empty($query->last_error)) {
					$this->last_error = $query->last_error;
				}
			}
		} else {
			$this->last_error = 'DB Connection error';
		}
		return $toret;
	}
	
	public function retrieve($parameter) {
		$toret = null;
		$this->last_error = false;
		if ($this->checkConnection()) {
			$query = $this->getRetrieveSQL();
			if ($query) {
				$stmt = $this->db->prepare($query);
				if ($stmt->execute(array(':parameter' => $parameter))) {
					$toret = $stmt->fetch(PDO::FETCH_ASSOC);
					$toret = $toret ? $toret : null;
				}
			} else {
				$this->last_error = 'No retrieve SQL for ' . class_name($this);
			}
		} else {
			$this->last_error = 'DB Connection error';
		}
		return $toret;
	}
	
	public function read($mode = false) {
		$toret = null;
		$this->last_error = false;
		if ($this->checkConnection()) {
			$id = $this->meta['id'];
			$mode = $mode ? $mode : ($id ? $this->load_mode : 'list');
			switch ($mode) {
			case 'array':
			case 'object':
			case 'full_object':
				$this->load(array('mode' => $mode));
				if (in_array($mode, array('object', 'full_object'))) {
					$toret = $this->object;
				} else {
					$toret = $this->array;
				}
				break;
			default:
			case 'list':
				$this->load(array('mode' => 'list'));
				$toret = $this->list;
				break;
			}
		} else {
			$this->last_error = 'DB Connection error';
		}
		return $toret;
	}

	function update($data, $options = array()) {
		$toret = false;
		$this->last_error = false;
		if ($this->checkConnection()) {
			$data = $this->validate($data, 'update', $options);
			if ($data) {
				$data = $this->process($data, 'in');
				list ($query, $params) = $this->getUpdateSQL($data, $options);
				$query = new CustomQuery($query, array('connection' => $this->db));
				$toret = $query->execute($params);
				if ($toret) {
					if (array_key_exists('load', $options) ? $options['load'] : true) {
						$this->load();
					}
				} else if (!empty($query->last_error)) {
					$this->last_error = $query->last_error;
				}
			}
		} else {
			$this->last_error = 'DB Connection error';
		}
		return $toret;
	}
	
	function delete() {
		$toret = false;
		$this->last_error = false;
		if ($this->checkConnection()) {
			extract($this->meta);
			$query = new CustomQuery("DELETE FROM `$table` WHERE `$id_field` = :id LIMIT 1", array('connection' => $this->db));
			$toret = $query->execute(array(':id' => $this->meta['id']));
			if (!empty($query->last_error)) {
				$this->last_error = $query->last_error;
			}
		} else {
			$this->last_error = 'DB Connection error';
		}
		return $toret;
	}
	
	function truncate() {
		$toret = false;
		$this->last_error = false;
		if ($this->checkConnection()) {
			extract($this->meta);
			$query = new CustomQuery("TRUNCATE `$table`", array('connection' => $this->db));
			$toret = $query->execute();
			if (!empty($query->last_error)) {
				$this->last_error = $query->last_error;
			}
		} else {
			$this->last_error = 'DB Connection error';
		}
		return $toret;
	}
	
	public function install(array $options = array()) {
		$toret = false;
		$this->last_error = false;
		if ($this->checkConnection()) {
			$drop_table = array_key_exists('drop_table', $options) ? $options['drop_table'] : false;
			$query = $this->getInstallSQL();
			if ($query) {
				if ($drop_table) {
					$table = $this->meta['table'];
					$drop_query = new CustomQuery('DROP TABLE IF EXISTS `' . $table . '`', array('connection' => $this->db));
					$drop_query->execute();
					Backend::addNotice('Dropping table ' . $table);
					if (!empty($drop_query->last_error)) {
						$this->last_error = $query->last_error;
					}
				}
				$query = new CustomQuery($query, array('connection' => $this->db));
				$toret = $query->execute();
				if (!empty($query->last_error)) {
					$this->last_error = $query->last_error;
				}
			} else {
				$this->last_error = 'No Install SQL for ' . class_name($this);
			}
		} else {
			$this->last_error = 'DB Connection error';
		}
		return $toret;
	}
	
	function validate($data, $action, $options = array()) {
		//TODO Try to use $this->last_error here
		$ret_data = array();
		$toret = true;
		if (is_array($data)) {
			foreach($this->meta['fields'] as $name => $options) {
				$options = is_array($options) ? $options : array('type' => $options);
				$type  = array_key_exists('type', $options) ? $options['type'] : 'string';
				
				$value = array_key_exists($name, $data) ? $data[$name] : null;
				switch($type) {
				case 'primarykey':
				case 'lastmodified':
					$value = null;
					break;
				case 'boolean':
					if ($value !== null) {
						$value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
					}
					break;
				case 'alphanumeric':
					if ($value !== null) {
						if (!ctype_alnum(preg_replace('/[[:space:]]/', '', $value))) {
							Backend::addError('Please supply a valid string for ' . humanize($name));
							$toret = false;
						}
					}
					break;
				case 'string':
					if ($value !== null) {
						$value = plain($value);
					}
					break;
				case 'text':
					if ($value !== null) {
						$value = simple($value);
					}
					break;
				case 'dateadded':
					if ($action == 'create') {
						$value = 'NOW()';
					} else {
						$value = null;
					}
					break;
				case 'email':
					if ($value !== null) {
						if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
							$value = filter_var($value, FILTER_VALIDATE_EMAIL);
						} else {
							Backend::addError('Please supply a valid email address');
							$toret = false;
						}
					}
					break;
				case 'website':
					//No break;
				case 'url':
					if ($value !== null && $value != '') {
						if (filter_var($value, FILTER_VALIDATE_URL)) {
							$value = filter_var($value, FILTER_VALIDATE_URL);
						} else {
							Backend::addError('Please supply a valid URL');
							$toret = false;
						}
					}
					break;
				case 'url_with_scheme':
					if ($value !== null) {
						if (filter_var($value, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
							$value = filter_var($value, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
						} else {
							Backend::addError('Please supply a valid URL with a scheme');
							$toret = false;
						}
					}
					break;
				case 'ip_address':
					if ($value !== null) {
						if (!filter_var($value, FILTER_VALIDATE_IP)) {
							Backend::addError('Please supply a valid URL with a scheme');
							$toret = false;
						}
					} else if (!empty($_SERVER['REMOTE_ADDR'])) {
						$value = $_SERVER['REMOTE_ADDR'];
					}
					break;
				case 'current_user':
					if ($value !== null) {
						$value = (int)$value;
					} else {
						$user = BackendAccount::checkUser();
						if ($user && $user->id > 0) {
							$value = $user->id;
						} else {
							$value = session_id();
						}
					}
					break;
				case 'current_query':
					$value = Controller::$area . '/' . Controller::$action . '/' . implode('/', Controller::$parameters);
					break;
				case 'current_request':
					if (!empty($_SERVER['REQUEST_URI'])) {
						$value = $_SERVER['REQUEST_URI'];
					} else {
						$value = get_current_url();
					}
					break;
				case 'previous_query':
					$value = get_previous_query();
					break;
				case 'previous_request':
					if (!empty($_SERVER['HTTP_REFERER'])) {
						$value = $_SERVER['HTTP_REFERER'];
					} else {
						$value = get_previous_url();
					}
					break;
				case 'user_agent':
					if (!empty($_SERVER['HTTP_USER_AGENT'])) {
						$value = $_SERVER['HTTP_USER_AGENT'];
					} else {
						$value = 'Unknown';
					}
					break;
				default:
					if ($value !== null) {
					}
					break;
				}
				if (!is_null($value)) {
					$ret_data[$name] = $value;
				} else if (!empty($options['required'])) {
					Backend::addError('Missing ' . $name);
					$toret = false;
					break;
				}
			}
		}
		return ($toret && count($ret_data)) ? $ret_data : false;
	}
	
	function fromPost(array $data = array()) {
		$data = count($data) ? $data : $_POST;
		if (array_key_exists('obj', $data)) {
			$data = $data['obj'];
		}
		$toret = array();
		foreach($this->meta['fields'] as $name => $options) {
			$options = is_array($options) ? $options : array('type' => $options);

			$type = array_key_exists('type', $options) ? $options['type'] : 'string';
			if (in_array($type, array('tiny_blob', 'blob', 'medium_blob', 'long_blob'))) {
				if (!empty($_FILES['obj'])) {
					if ($_FILES['obj']['error'][$name]) {
						switch ($_FILES['obj']['error'][$name]) {
						case 1:
						case 2:
							$message = 'File too large to be uploaded';
							break;
						case 3:
							$message = 'File only partially uploaded';
							break;
						case 4:
							$message = 'No file was uploaded';
							break;
						case 6:
							$message = 'Could not upload file. No tmp folder';
							break;
						case 7:
							$message = 'Could not upload file. Can\'t write to tmp folder';
							break;
						case 8:
							$message = 'Could not upload file. Invalid extension';
							break;
						default:
							$message = 'Unknown file upload error (' . $_FILES['obj']['error'][$name] . ')';
							break;
						}
						Backend::addError($message);
					} else {
						$file = array();
						$file['name']     = $_FILES['obj']['name'][$name];
						$file['type']     = $_FILES['obj']['type'][$name];
						$file['tmp_name'] = $_FILES['obj']['tmp_name'][$name];
						$file['error']    = $_FILES['obj']['error'][$name];
						$file['size']     = $_FILES['obj']['size'][$name];
						$toret[$name] = $file;
					}
				} else {
					$toret[$name] = null;
				}
			} else {
				$toret[$name] = array_key_exists($name, $data) ? $data[$name] : null;
			}
		}
		return $toret;
	}
	
	public function getSource() {
		return '`' . $this->meta['database'] . '`.`' . $this->meta['table'] . '`';
	}
	
	public function getSelectSQL($options = array()) {
		extract($this->meta);

		$mode = array_key_exists('mode', $options) ? $options['mode'] : 'list';

		$query = new SelectQuery($this, array('connection' => $this->db));
		//Fields
		$fields = array_key_exists('fields', $options) ? $options['fields'] : array();
		if (empty($fields)) {
			$query->field("`$table`.*");
		} else {
			$query->field($fields);
		}
		//Joins
		$joins = array_key_exists('joins', $options) ? $options['joins'] : array();
		if (count($joins)) {
			foreach($joins as $join) {
				if (is_array($join)) {
					$query->joinArray($join);
				}
			}
		}

		$parameters = array();

		if (!empty($options['conditions'])) {
			$query->filter($options['conditions']);
		}

		$limit = false;
		switch ($mode) {
			case 'object':
			case 'array':
			case 'full_object':
				if ($id) {
					$query->filter("`$table`.`$id_field` = :{$table}_id");
					$parameters[":{$table}_id"] = $id;
				} else {
					$query->limit(empty($limit) ? 1 : $limit);
				}
				break;
			case 'list':
				if (array_key_exists('limit', $options) && $options['limit'] != 'all') {
					$query->limit($options['limit']);
				}
				break;
		}

		if (array_key_exists('parameters', $options)) {
			if (is_array($options['parameters'])) {
				$parameters = array_merge($parameters, $options['parameters']);
			}
		}
		if (array_key_exists('order', $options)) {
			$query->order($options['order']);
		}
		return array($query, $parameters);
	}
	
	public function getRetrieveSQL() {
		extract($this->meta);
		$database = Backend::get('DB_' . $database, $database);
		$query = 'SELECT * FROM `' . $database . '`.`' . $table . '` WHERE `' . $id_field . '` = :parameter';
		if (array_key_exists('name', $fields)) {
			$query .= ' OR `name` = :parameter';
		}
		if (array_key_exists('title', $fields)) {
			$query .= ' OR `title` = :parameter';
		}
		return $query;
	}

	public function getCreateSQL($data, array $options = array()) {
		extract($this->meta);
		$database = Backend::get('DB_' . $database, $database);
		$query = false;
		$field_data = array();
		$value_data = array();
		$parameters = array();
		foreach ($fields as $name => $options) {
			if (!is_array($options)) {
				$options = array('type' => $options);
			}
			if (array_key_exists($name, $data)) {
				$type = array_key_exists('type', $options) ? $options['type'] : 'string';
				$field_data[] = $name;

				$do_add = true;
				$just_add = false;
				$value = null;
				switch (true) {
					case $type == 'lastmodified':
						$do_add = false;
						break;
					case $type == 'dateadded':
						$do_add = false;
						$just_add = true;
						$value = 'NOW()';
						break;
					case substr($type, 0, 8) == 'password':
						/*
						Use the default for now. method etc should be defined in the options array
						if (strpos($options, ':') !== false) {
							$temp = explode(':', $options);
							if (count($temp) >= 2) {
								$do_add = false;
								$just_add = true;
								$method = $temp[1];
								$value = $method . '(:' . $name . ')';
								$parameters[':' . $name] = $data[$name];
							}
						}
						if (is_null($value)) {
							$value = $data[$name];
						}
						break;
						*/
					default:
						$value = $data[$name];
						break;
				}
				if ($do_add) {
					$parameters[':' . $name] = $value;
					$value_data[] = ':' . $name;
				} else if ($just_add) {
					$value_data[] = $value;
				}
			}
		}
		if (count($field_data)) {
			if (count($value_data) == count($field_data)) {
				$field_str = implode(', ', $field_data);
				$value_str = implode(', ', $value_data);
				$query = "INSERT INTO `$database`.`$table` ($field_str) VALUES ($value_str)";
				if (!empty($options['on_duplicate'])) {
					$query .= ' ON DUPLICATE KEY UPDATE ' . $options['on_duplicate'];
				} else if (!empty($options['ignore'])) {
					$query = preg_replace('/^INSERT /', 'INSERT IGNORE ', $query);
				}
			} else {
				throw new Exception('Insert Query Fields and Values don\'t match');
			}
		}
		return array($query, count($parameters) ? $parameters : false);
	}

	public function getUpdateSQL($data, array $options = array()) {
		extract($this->meta);
		$database = Backend::get('DB_' . $database, $database);
		$query = false;
		$field_data = array();
		$value_data = array();
		$parameters = array();
		foreach ($fields as $name => $options) {
			$options = is_array($options) ? $options : array('type' => $options);
			$type = array_key_exists('type', $options) ? $options['type'] : 'string';
			if (array_key_exists($name, $data)) {
				$do_add = true;
				$just_add = false;
				$value = null;
				switch (true) {
				case preg_match(REGEX_SQL_FUNCTION, strtoupper($data[$name])):
					$do_add   = false;
					$just_add = true;
					$value = $data[$name];
					break;
				case substr($type, 0, 8) == 'password':
					if (!is_null($data[$name])) {
						/*if (strpos($type, ':') !== false) {
							$temp = explode(':', $type);
							if (count($temp) >= 2) {
								$do_add = false;
								$just_add = true;
								$method = $temp[1];
								$value = $method . '(:' . $name . ')';
								$parameters[':' . $name] = $data[$name];
							}
						}*/
						if (is_null($value)) {
							$value = $data[$name];
						}
					} else {
						unset($data['password']);
						$do_add = false;
					}
					break;
				case $type == 'lastmodified':
					$do_add = false;
					break;
				case $type == 'dateadded':
					$do_add = false;
					break;
				default:
					$value = $data[$name];
					break;
				}
				if ($do_add) {
					$field_data[] = $name;
					$parameters[':' . $name] = $value;
					$value_data[] = '`' . $name . '` = :' . $name;
				} else if ($just_add) {
					$field_data[] = $name;
					$value_data[] = '`' . $name . '` = ' . $value;
				}
			}
		}
		if (count($field_data)) {
			if (count($value_data) == count($field_data)) {
				$value_str = implode(', ', $value_data);
				$query = "UPDATE `$database`.`$table` SET $value_str WHERE `$id_field` = :id";
				$parameters[':id'] = $this->meta['id'];
			} else {
				throw new Exception('Insert Query Fields and Values don\'t match');
			}
		}
		return array($query, count($parameters) ? $parameters : false);
	}
	
	public function getDeleteSQL() {
		$query = false;
		if ($id) {
			extract($this->meta);
			$database = Backend::get('DB_' . $database, $database);
			$query = "DELETE FROM `$database`.`$table`";
			$query .= " WHERE `$table`.`$id_field` = :{$table}_id LIMIT 1";
		}
		return $query;
	}
	
	public function getInstallSQL() {
		extract($this->meta);
		$query_fields = array();
		$query_keys = array();
		$keys = empty($keys) ? array() : $keys;
		foreach($fields as $field => $options) {
			$field_arr = array();
			if (is_string($options)) {
				$options = array('type' => $options);
			}
			$type    = array_key_exists('type',    $options) ? $options['type']    : 'string';
			$default = array_key_exists('default', $options) ? $options['default'] : null;
			$null    = array_key_exists('null',    $options) ? $options['null']    : false;
			$field_arr[] = '`' . $field . '`';
			switch($type) {
			case 'primarykey':
				$keys[$field] = 'primary';
				$field_arr[] = 'BIGINT(20) AUTO_INCREMENT';
				break;
			case 'current_user':
				$field_arr[] = 'VARCHAR(255)';
				break;
			case 'foreignkey':
				$field_arr[] = 'BIGINT(20)';
				break;
			case 'current_query':
			case 'current_request':
			case 'previous_query':
			case 'previous_request':
			case 'user_agent':
				$field_arr[] = 'VARCHAR(1024)';
				break;
			case 'password':
				$string_size = empty($options['string_size']) ? 32 : $options['string_size'];
				$field_arr[] = 'VARCHAR(' . $string_size .')';
				break;
			case 'salt':
				$field_arr[] = 'VARCHAR(32)';
				break;
			case 'ip_address':
				//TODO think about storing this as a number
				$field_arr[] = 'VARCHAR(15)';
				break;
			case 'website':
				//No break;
			case 'email':
				//No break;
			case 'telnumber':
				//No break;
			case 'title':
				//No break;
			case 'string':
				$string_size = empty($options['string_size']) ? 255 : $options['string_size'];
				$field_arr[] = 'VARCHAR(' . $string_size .')';
				break;
			case 'large_string':
				$field_arr[] = 'VARCHAR(1024)';
				break;
			case 'medium_string':
				$field_arr[] = 'VARCHAR(100)';
				break;
			case 'small_string':
				$field_arr[] = 'VARCHAR(30)';
				break;
			case 'character':
				$field_arr[] = 'VARCHAR(1)';
				break;
			case 'serialized':
				//No break;
			case 'text':
				$field_arr[] = 'TEXT';
				break;
			case 'tiny_integer':
				$field_arr[] = 'TINYINT(4)';
				break;
			case 'small_integer':
				$field_arr[] = 'SMALLINT(6)';
				break;
			case 'medium_integer':
				$field_arr[] = 'MEDIUMINT(9)';
				break;
			case 'number':
				//No break;
			case 'integer':
				$int_size = empty($options['int_size']) ? 11 : $options['int_size'];
				$field_arr[] = 'INT(' . $int_size . ')';
				break;
			case 'large_integer':
				$field_arr[] = 'BIGINT(20)';
				break;
			case 'currency':
				//No break;
			case 'float':
				$field_arr[] = 'FLOAT';
				break;
			case 'long_blob':
				$field_arr[] = 'LONGBLOB';
				break;
			case 'medium_blob':
				$field_arr[] = 'MEDIUMBLOB';
				break;
			case 'blob':
				$field_arr[] = 'BLOB';
				break;
			case 'tiny_blob':
				$field_arr[] = 'TINYBLOB';
				break;
			case 'active':
				//No break;
			case 'boolean':
				$field_arr[] = 'TINYINT(1)';
				break;
			case 'lastmodified':
				$null = false;
				$default = 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
				//No break;
			case 'timestamp':
				$field_arr[] = 'TIMESTAMP';
				break;
			case 'date':
				$field_arr[] = 'DATE';
				break;
			case 'time':
				$field_arr[] = 'TIME';
				break;
			case 'dateadded':
				//No break;
			case 'datetime':
				$field_arr[] = 'DATETIME';
				break;
			default:
				var_dump('CreateSQL Failure: ', $field, $options);
				break;
			}
			if ($null) {
				$field_arr[] = 'NULL';
			} else {
				$field_arr[] = 'NOT NULL';
			}
			if (!is_null($default)) {
				$field_arr[] = 'DEFAULT ' . $default;
			}
			$query_fields[] = implode(' ', array_map('trim', $field_arr));
		}
		
		foreach($keys as $field => $type) {
			$fields = array_map('trim', explode(',', $field));
			$name = str_replace(' ', '', ucwords(implode(' ', $fields)));
			$fields = '`' . implode('`, `', $fields) . '`';
			switch ($type) {
			case 'primary':
				$query_keys[] = 'PRIMARY KEY(' . $fields . ')';
				break;
			case 'unique':
				$query_keys[] = 'UNIQUE KEY `Unique' . $name . '` (' . $fields . ')';
				break;
			case 'index':
				$query_keys[] = 'KEY `Index' . $name . '` (' . $fields . ')';
				break;
			}
		}

		//Add the keys and the fields together
		if (count($query_keys)) {
			$query_fields = array_merge($query_fields, $query_keys);
		}

		$query = 'CREATE TABLE IF NOT EXISTS `' . $database .'`.`' . $table . '` (';
		$query .= "\n\t" . implode(",\n\t", $query_fields);
		$query .= "\n)";
		if (!empty($engine)) {
			$query .= ' ENGINE=' . $engine;
		}
		if (!empty($charset)) {
			$query .= ' DEFAULT CHARSET=' . $charset;
		}
		return $query;
	}
	
	public function getMeta($name = false) {
		if ($name) {
			$toret = array_key_exists($name, $this->meta) ? $this->meta[$name] : null;
		} else {
			$toret = $this->meta;
		}
		return $toret;
	}
	
	public function getArea() {
		return class_for_url(get_class($this));
	}
	
	public function __toString() {
		$class = get_called_class();
		if (!$class) {
			print_stacktrace();
			return '(Unknown)';
		}
		return $class;
	}
}
