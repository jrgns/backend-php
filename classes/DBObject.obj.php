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
 *
 * TODO Implement an increment field type (not reliant on MySQL's auto increment)
 */
class DBObject {
	private $db;
	protected $meta;
	protected $load_mode = 'array';

	public $list = null;
	public $array = null;
	public $object = null;
	public $inserted_id;
	public $list_count = null;

	//If you set $error_msg in a function, reset it in the beginning of the function as well.
	public $error_msg = false;

	private static $top_class = false;
	/**
	 * Construct a DB Object
	 *
	 * children have the following options:
	 * - conditions = array(ClassName => array(field_in_child => value | field_in_this_model))
	 * - relation = single | multiple, defaults to single
	 */
	function __construct($meta = array(), array $options = array()) {
		if (!is_array($meta)) {
			$meta = is_numeric($meta) ? array('id' => $meta) : array();
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

		$load_type  = array_key_exists('load_mode', $options) ? $options['load_mode'] : $this->load_mode;
		if ($this->checkConnection()) {
			if ($meta['id']) {
				$this->read(array('mode' => $load_type));
			}
		}
	}

	private function checkConnection() {
		$this->error_msg = false;
		if (!$this->db instanceof PDO) {
			try {
				$this->db = Backend::getDB($this->meta['database']);
			} catch (Exception $e) {
				Backend::addError($e->getMessage());
			}
			if (!$this->db instanceof PDO) {
				$this->error_msg = 'No Database setup';
				if (class_exists('BackendError', false)) {
					BackendError::add(get_class($this) . ': No Database setup', 'checkConnection');
				}
				return false;
			}
		}
		return ($this->db instanceof PDO);
	}

	private function loadRelation($class, $options, $load_mode) {
		$class_name = array_key_exists('model', $options) ? $options['model'] . 'Obj' : $class . 'Obj';
		if (!Component::isActive($class_name)) {
			return null;
		}
		$conds = array();
		$params = array();
		$relation = new $class_name();
		$conditions = array_key_exists('conditions', $options) ? $options['conditions'] : false;
		$type       = array_key_exists('type', $options)       ? $options['type']       : 'single';
		$order      = array_key_exists('order', $options)      ? $options['order']      : $relation->getMeta('order');
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
				} else if ($load_mode == 'object') {
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
		if (Controller::$debug >= 2) {
			var_dump(get_class($relation), $mode, $conds, $params, $order);
		}
		$relation->read(array('mode' => $mode, 'conditions' => $conds, 'parameters' => $params, 'order' => $order));
		$relation->loadDeep($mode);
		return $relation;
	}

	private function loadDeep($mode = 'array') {
		if (in_array($mode, array('array', 'object')) && $this->$mode) {
			foreach ($this->meta['relations'] as $name => $options) {
				$class = array_key_exists('class', $options) ? $options['class'] : $name;
				if (!in_array(self::$top_class, array($class, $class . 'Obj'))) {
					$type  = array_key_exists('type', $options)  ? $options['type']  : 'single';
					if ($relation = $this->loadRelation($class, $options, $mode)) {
						switch ($type) {
						case 'multiple':
							if ($mode == 'array') {
								$this->array[$name]  = $relation->list ? $relation->list : array();
							} else if ($mode == 'object') {
								$this->object->$name = $relation->list ? $relation->list : array();
							}
							break;
						default:
						case 'single':
							if ($mode == 'array') {
								$this->array[$name]  = $relation->array  ? $relation->array  : false;
							} else if ($mode == 'object') {
								$this->object->$name = $relation->object ? $relation->object : false;
							}
							break;
						}
					}
				}
			}
		}
	}

	public function loadArray(array $options = array()) {
		$this->read(array_merge($options, array('mode' => 'array')));
	}

	public function loadObject(array $options = array()) {
		$this->read(array_merge($options, array('mode' => 'object')));
	}

	public function loadList(array $options = array()) {
		$this->read(array_merge($options, array('mode' => 'list')));
	}

	public function load($options = array()) {
		return $this->read($options);
	}

	public function read($options = array()) {
		if (!self::$top_class) {
			self::$top_class = get_class($this);
		}

		if (is_string($options)) {
			$options = array('mode' => $options);
		}
		$this->error_msg = false;

		//Check the DB Connection
		if (!$this->checkConnection()) {
			if (class_exists('BackendError', false)) {
				BackendError::add(get_class($this) . ': DB Connection Error', 'load');
			}
			$this->error_msg = 'DB Connection Error';
			return false;
		}

		//Get the SQL Query
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
		if (empty($query)) {
			if (class_exists('BackendError', false)) {
				BackendError::add(get_class($this) . ': No Query to Load', 'load');
			}
			$this->error_msg = 'No Query to Load';
			return false;
		}
		if (!($query instanceof Query)) {
			$query = new CustomQuery($query, array('connection' => $this->db));
		}

		//Execute
		if ($result = $query->execute($params)) {
			switch ($options['mode']) {
			case 'object':
			case 'full_object':
				$this->object = $result->fetch(PDO::FETCH_OBJ);
				if ($this->object) {
					$this->loadDeep('object');
					if (empty($this->meta['id'])) {
						if (property_exists($this->object, $this->meta['id_field'])) {
							$id_field_name = $this->meta['id_field'];
							$this->meta['id'] = $this->object->$id_field_name;
						} else {
							BackendError::add('Non existant ID Field', get_class($this));
						}
					}
					$this->array = (array)$this->object;
					array_walk_recursive($this->array,create_function('&$input, $key', 'if (is_object($input)) { $input = (array)$input; } else { return $input; }'));
				} else {
					$this->object = null;
				}
				break;
			case 'array':
				$this->array = $result->fetch(PDO::FETCH_ASSOC);
				if ($this->array) {
					$this->loadDeep('array');
					if (empty($this->meta['id'])) {
						if (array_key_exists($this->meta['id_field'], $this->array)) {
							$this->meta['id'] = $this->array[$this->meta['id_field']];
						} else {
							BackendError::add('Non existant ID Field', get_class($this));
						}
					}
				} else {
					$this->array = null;
				}
				break;
			case 'list':
			default:
				$this->list = $result->fetchAll(PDO::FETCH_ASSOC);
				if ($query instanceof Query) {
					$this->list_count = $query->getCount($params);
				} else {
					$count_query = new CustomQuery(preg_replace(REGEX_MAKE_COUNT_QUERY, '$1 COUNT(*) $3', $query));
					$this->list_count = $count_query->fetchColumn($params);
				}
				break;
			}
			if ($this->object) {
				$this->object = $this->process($this->object, 'out');
			}
			if ($this->array) {
				$this->array = $this->process($this->array, 'out');
			}
		} else if (!empty($query->error_msg)) {
			$this->error_msg = $query->error_msg;
		}
		if (get_class($this) == self::$top_class) {
			self::$top_class = false;
		}
		return $result;
	}

	public function process($data, $direction) {
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
						if (is_array($data)) {
							$data[$name] = base64_encode(serialize($value));
						} else if (is_object($data)) {
							$data->$name = base64_encode(serialize($value));
						}
						break;
					case 'out':
						if (is_array($data)) {
							$data[$name] = @unserialize(base64_decode($value));
						} else if (is_object($data)) {
							$data->$name = @unserialize(base64_decode($value));
						}
						break;
					}
					break;
				case 'text':
					if (!empty($options['markdown']) && $direction == 'in' && function_exists('markdown')) {
						if (is_array($data)) {
							$data[$name] = markdown($value);
						} else if (is_object($data)) {
							$data->$name = markdown($value);
						}
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
		$this->error_msg = false;
		if (!$this->checkConnection()) {
			if (class_exists('BackendError', false)) {
				BackendError::add(get_class($this) . ': DB Connection Error', 'create');
			}
			$this->error_msg = 'DB Connection error';
			return false;
		}
		if ($data = $this->validate($data, 'create', $options)) {
			$data = $this->process($data, 'in');
			list ($query, $params) = $this->getCreateSQL($data, $options);
			$query = new CustomQuery($query, array('connection' => $this->db));
			if ($result = $query->execute($params, $options)) {
				//TODO This will potentially break if there are triggers in use
				$this->inserted_id = $this->db->lastInsertId();
				$this->array       = $data;
				$this->array['id'] = $this->inserted_id;
				$this->meta['id']  = $this->inserted_id;
				$result            = $this->inserted_id;
				if (array_key_exists('load', $options) ? $options['load'] : true) {
					$this->read();
				}
				return $result;
			}
			if (!empty($query->error_msg)) {
				if ($query->error_code == 1062) {
					$this->error_msg = 'The record or a duplicate of it already exists';
				} else {
					$this->error_msg = $query->error_msg;
				}
			}
			return false;
		}
		$this->error_msg = 'Could not validate data for creation';
		return false;
	}

	public function replace($data, array $options = array()) {
		$this->error_msg = false;
		if (!$this->checkConnection()) {
			if (class_exists('BackendError', false)) {
				BackendError::add(get_class($this) . ': DB Connection Error', 'replace');
			}
			$this->error_msg = 'DB Connection Error';
			return false;
		}
		if ($data = $this->validate($data, 'create', $options)) {
			$data = $this->process($data, 'in');
			list ($query, $params) = $this->getCreateSQL($data, $options);
			$query = preg_replace('/^INSERT/', 'REPLACE', $query);
			$query = new CustomQuery($query, array('connection' => $this->db));
			;
			if ($result = $query->execute($params, $options)) {
				//TODO This will potentially break if there are triggers in use
				$id_options =
					is_array($this->meta['fields'][$this->meta['id_field']]) ?
					$this->meta['fields'][$this->meta['id_field']] :
					array('type' => $this->meta['fields'][$this->meta['id_field']]);
				if (empty($id_options['non_automatic'])) {
					$new_id = $this->db->lastInsertId();
				} else if (!empty($data[$this->meta['id_field']])) {
					$new_id = $data[$this->meta['id_field']];
				} else {
					$new_id = false;
				}
				$this->inserted_id = $new_id;
				$result            = $new_id;
				if (array_key_exists('load', $options) ? $options['load'] : true) {
					$this->meta['id']  = $new_id;
					$this->read();
				}
				return $result;
			}
			if (!empty($query->error_msg)) {
				$this->error_msg = $query->error_msg;
			}
		}
		return false;
	}

	public function retrieve($parameter) {
		$this->error_msg = false;
		if (!$this->checkConnection()) {
			if (class_exists('BackendError', false)) {
				BackendError::add(get_class($this) . ': DB Connection Error', 'retrieve');
			}
			$this->error_msg = 'DB Connection Error';
			return null;
		}
		if ($query = $this->getRetrieveSQL()) {
			$stmt = $this->db->prepare($query);
			if ($stmt->execute(array(':parameter' => $parameter))) {
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
				return $result ? $result : null;
			}
			if (!empty($query->error_msg)) {
				$this->error_msg = $query->error_msg;
			}
		} else {
			if (class_exists('BackendError', false)) {
				BackendError::add(get_class($this) . ': No Retrieve SQL', 'retrieve');
			}
			$this->error_msg = 'No Retrieve SQL for ' . class_name($this);
		}
		return null;
	}

	public function update($data, array $options = array()) {
		$this->error_msg = false;
		if (!$this->checkConnection()) {
			if (class_exists('BackendError', false)) {
				BackendError::add(get_class($this) . ': DB Connection Error', 'update');
			}
			$this->error_msg = 'DB Connection Error';
			return false;
		}
		$data = $this->validate($data, 'update', $options);
		if (!$data) {
			if (Controller::$debug) {
				Backend::addError($this->getMeta('name') . ' Validation failed');
			}
			return false;
		}
		$data = $this->process($data, 'in');
		list ($query, $params) = $this->getUpdateSQL($data, $options);
		$query = new CustomQuery($query, array('connection' => $this->db));
		if ($result = $query->execute($params, $options)) {
			if (array_key_exists('load', $options) ? $options['load'] : true) {
				$this->read();
			}
			return $result;
		}
		if (!empty($query->error_msg)) {
			if ($query->error_code == 1062) {
				$this->error_msg = 'The record or a duplicate of it already exists';
			} else {
				$this->error_msg = $query->error_msg;
			}
		}
		return false;
	}

	public function delete(array $options = array()) {
		$this->error_msg = false;
		if (!$this->checkConnection()) {
			if (class_exists('BackendError', false)) {
				BackendError::add(get_class($this) . ': DB Connection Error', 'delete');
			}
			$this->error_msg = 'DB Connection Error';
			return false;
		}
		extract($this->meta);
		$query = new CustomQuery("DELETE FROM `$table` WHERE `$id_field` = :id LIMIT 1", array('connection' => $this->db));
		if ($result = $query->execute(array(':id' => $this->meta['id']), $options)) {
			return $result;
		}
		if (!empty($query->error_msg)) {
			$this->error_msg = $query->error_msg;
		}
		return false;
	}

	public function truncate(array $options = array()) {
		$toret = false;
		$this->error_msg = false;
		if (!$this->checkConnection()) {
			if (class_exists('BackendError', false)) {
				BackendError::add(get_class($this) . ': DB Connection Error', 'truncate');
			}
			$this->error_msg = 'DB Connection Error';
			return false;
		}
		extract($this->meta);
		$query = new CustomQuery("TRUNCATE `$table`", array('connection' => $this->db));
		if ($result = $query->execute(array(), $options)) {
			return $result;
		}
		if (!empty($query->error_msg)) {
			$this->error_msg = $query->error_msg;
		}
		return false;
	}

	public function install(array $options = array()) {
		$toret = false;
		$this->error_msg = false;
		if ($this->checkConnection()) {
			$drop_table = array_key_exists('drop_table', $options) ? $options['drop_table'] : false;
			$query = $this->getInstallSQL();
			if ($query) {
				if ($drop_table) {
					$table = $this->getSource();
					$drop_query = new CustomQuery('DROP TABLE IF EXISTS ' . Query::getTable($this) . '', array('connection' => $this->db));
					$drop_query->execute();
					Backend::addNotice('Dropping table ' . $table);
					if (!empty($drop_query->error_msg)) {
						$this->error_msg = $drop_query->error_msg;
					}
				}
				$query = new CustomQuery($query, array('connection' => $this->db));
				$toret = $query->execute();
				if (!empty($query->error_msg)) {
					$this->error_msg = $query->error_msg;
				}
			} else {
				if (class_exists('BackendError', false)) {
					BackendError::add(get_class($this) . ': No Install SQL', 'install');
				}
				$this->error_msg = 'No Install SQL for ' . class_name($this);
			}
		} else {
			if (class_exists('BackendError', false)) {
				BackendError::add(get_class($this) . ': DB Connection Error', 'install');
			}
			$this->error_msg = 'DB Connection error';
		}
		return $toret;
	}

	function validate($data, $action, $options = array()) {
		//TODO Try to use $this->error_msg here
		$ret_data = array();
		$toret = true;

		if (is_array($data)) {
			foreach($this->meta['fields'] as $name => $field_options) {
				$value = array_key_exists($name, $data) ? $data[$name] : null;
				if (!empty($options['non_parameter'])
						&& is_array($options['non_parameter'])
						&& array_search($name, $options['non_parameter']) !== false
				) {
					if (!is_null($value)) {
						$ret_data[$name] = $value;
					}
					continue;
				}
				$field_options = is_array($field_options) ? $field_options : array('type' => $field_options);
				$type          = array_key_exists('type', $field_options) ? $field_options['type'] : 'string';
				switch($type) {
				case 'primarykey':
					if (empty($field_options['non_automatic'])) {
						$value = null;
					} else if (is_null($value)) {
						Backend::addError('Missing Primary Key ' . $name);
						$toret = false;
					}
					break;
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
						//$value = plain($value);
					}
					break;
				case 'text':
					if ($value !== null) {
						//$value = simple($value);
					}
					break;
				case 'dateadded':
					if ($action == 'create') {
						$value = 'NOW()';
					} else {
						$value = null;
					}
					break;
				case 'date':
					if (!is_null($value)) {
						$value = date('Y-m-d',       is_numeric($value) ? $value : strtotime($value));
					}
					break;
				case 'datetime':
					if (!is_null($value)) {
						$value = date('Y-m-d H:i:s', is_numeric($value) ? $value : strtotime($value));
					}
					break;
				case 'time':
					if (!is_null($value)) {
						$value = date('H:i:s',       is_numeric($value) ? $value : strtotime($value));
					}
					break;
				case 'timestamp':
					if (!is_null($value)) {
						$value = is_numeric($value) ? $value : strtotime($value);
					}
					break;
				case 'email':
					if ($value !== null) {
						if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
							$value = filter_var($value, FILTER_VALIDATE_EMAIL);
						} else if (!empty($value)) {
							$this->error_msg = 'Validation Failed';
							Backend::addError('Please supply a valid email address');
							$toret = false;
						}
					}
					break;
				case 'website':
					//No break;
				case 'url':
					if ($value !== null && $value != '') {
						$parts = parse_url($value);
						//We need the scheme to validate the URL
						if (empty($parts['scheme'])) {
							$value = 'http://' . $value;
						}
						if (filter_var($value, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED)) {
							$value = explode('://', $value);
							$value = end($value);
						} else if (!empty($value)) {
							$this->error_msg = 'Validation Failed';
							Backend::addError('Please supply a valid URL');
							$toret = false;
						}
					}
					break;
				case 'url_with_scheme':
					if ($value !== null) {
						if (filter_var($value, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
							$value = filter_var($value, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
						} else if (!empty($value)) {
							$this->error_msg = 'Validation Failed';
							Backend::addError('Please supply a valid URL with a scheme');
							$toret = false;
						}
					}
					break;
				case 'ip_address':
					if ($value !== null) {
						if (!filter_var($value, FILTER_VALIDATE_IP)) {
							$this->error_msg = 'Validation Failed';
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
					} else if ($action == 'create') {
						$user = BackendUser::check();
						if ($user && $user->id > 0) {
							$value = $user->id;
						} else {
							$value = session_id();
						}
					}
					break;
				case 'current_query':
					$value = get_current_query();
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
					if (
						empty($value)
						&& !empty($field_options['required'])
						&& $type != 'primarykey'
					) {
						if (array_key_exists('default', $field_options)) {
							$ret_data[$name] = $field_options['default'];
						} else {
							$this->error_msg = 'Validation Failed';
							Backend::addError('Missing ' . $name);
							$toret = false;
							break;
						}
					} else {
						$ret_data[$name] = $value;
					}
				} else if (
					$action == 'create'
					&& !empty($field_options['required'])
					&& $type != 'primarykey'
				) {
					if (array_key_exists('default', $field_options)) {
						$ret_data[$name] = $field_options['default'];
					} else if (!in_array($type, array('lastmodified'))) {
						$this->error_msg = 'Validation Failed';
						Backend::addError('Missing ' . $name);
						$toret = false;
						break;
					}
				}
			}
		}
		return ($toret && count($ret_data)) ? $ret_data : false;
	}

	public function fromRequest() {
		$toret = array();
		foreach($this->meta['fields'] as $name => $options) {
			$toret[$name] = null;

			$options        = is_array($options) ? $options : array('type' => $options);
			$type           = array_key_exists('type', $options) ? $options['type'] : 'string';
			$filter         = array_key_exists('filter', $options) ? $options['filter'] : FILTER_DEFAULT;
			$filter_options = array_key_exists('filter_options', $options) ? $options['filter_options'] : array();
			//Files
			if (in_array($type, array('tiny_blob', 'blob', 'medium_blob', 'long_blob'))) {
				if (!empty($_FILES)) {
					if ($_FILES[$name]['error']) {
						switch ($_FILES[$name]['error']) {
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
							$message = 'Unknown file upload error (' . $_FILES[$name]['error'] . ')';
							break;
						}
						Backend::addError($message);
					} else {
						$toret[$name] = $_FILES[$name];
					}
				}
			//Other Types
			} else {
			    $value = Controller::getVar($name);
			    if (!is_null($value)) {
				    $toret[$name] = filter_var($value, $filter, $filter_options);
				    if ($toret[$name] === false) {
					    $toret[$name] = null;
					    Backend::addError('Invalid input');
				    }
				}
			}
		}
		return $toret;
	}

	public function getSource() {
		$database = Backend::getDBDefinition($this->meta['database']);
		return $database ? '`' . $database['database'] . '`.`' . $this->meta['table'] . '`' : false;
	}

	public function getConnection() {
		if ($this->db instanceof PDO) {
			return $this->db;
		}
		return false;
	}

	public function getSelectSQL($options = array()) {
		//Check the DB Connection
		$this->error_msg = false;
		if (!$this->checkConnection()) {
			if (class_exists('BackendError', false)) {
				BackendError::add(get_class($this) . ': DB Connection Error', 'getSelectSQL');
			}
			$this->error_msg = 'DB Connection Error';
			return false;
		}

		$mode = array_key_exists('mode', $options) ? $options['mode'] : 'list';

		$query = new SelectQuery($this, array('connection' => $this->db));
		//Fields
		$fields = array_key_exists('fields', $options) ? $options['fields'] : array();
		if (empty($fields)) {
			$query->field("`{$this->meta['table']}`.*");
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

		$q_params = array();

		if (!empty($options['conditions'])) {
			$query->filter($options['conditions']);
		}

		//Mode specific
		$limit = false;
		switch ($mode) {
			case 'object':
			case 'array':
			case 'full_object':
				if (!empty($this->meta['id'])) {
					$query->filter("`{$this->meta['table']}`.`{$this->meta['id_field']}` = :{$this->meta['table']}_id");
					$q_params[":{$this->meta['table']}_id"] = $this->meta['id'];
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

		//Parameters
		if (array_key_exists('parameters', $options)) {
			if (is_array($options['parameters'])) {
				$q_params = array_merge($q_params, $options['parameters']);
			} else {
				$q_params[] = $options['parameters'];
			}
		} else if (!empty($this->meta['parameters'])) {
			if (is_array($this->meta['parameters'])) {
				$q_params = array_merge($q_params, $this->meta['parameters']);
			} else {
				$q_params[] = $parameters;
			}
		}

		//Filters
		if (array_key_exists('filters', $options)) {
			$query->filter($options['filters']);
		} else if (!empty($this->meta['filters'])) {
			$query->filter($this->meta['filters']);
		}

		//Order
		if (array_key_exists('order', $options)) {
			$query->order($options['order']);
		} else if (!empty($this->meta['order'])) {
			$query->order($this->meta['order']);
		}

		//Group
		if (array_key_exists('group', $options)) {
			$query->group($options['group']);
		} else if (!empty($this->meta['group'])) {
			$query->group($this->meta['group']);
		}
		return array($query, $q_params);
	}

	public function getRetrieveSQL() {
		list($query, $parameters)  = $this->getSelectSQL();

		$filter = '`' . $this->getMeta('id_field') . '` = :parameter';
		if (array_key_exists('name', $this->meta['fields'])) {
			$filter .= ' OR `name` = :parameter';
		}
		if (array_key_exists('title', $this->meta['fields'])) {
			$filter .= ' OR `title` = :parameter';
		}
		$query->filter($filter);
		return $query;
	}

	public function getCreateSQL($data, array $options = array()) {
		extract($this->meta);

		$query = false;
		$field_data = array();
		$value_data = array();
		$parameters = array();

		$non_parameter_aware = array_key_exists('non_parameter', $options);
		$non_parameters = array_key_exists('non_parameter', $options) ? $options['non_parameter'] : array();
		foreach ($fields as $name => $field_options) {
			if (!is_array($field_options)) {
				$field_options = array('type' => $field_options);
			}
			if (array_key_exists($name, $data)) {
				$type = array_key_exists('type', $field_options) ? $field_options['type'] : 'string';
				$field_data[] = '`' . $name . '`';

				$do_add = true;
				$just_add = false;
				$value = null;
				switch (true) {
				case $non_parameter_aware && preg_match(REGEX_SQL_FUNCTION, strtoupper($data[$name])):
					$do_add   = false;
					$just_add = true;
					$value = $data[$name];
					break;
				case substr($type, 0, 8) == 'password':
					if (!is_null($data[$name])) {
						if (is_null($value)) {
							$value = $data[$name];
						}
					}
					break;
				case $type == 'lastmodified':
					$do_add = false;
					break;
				case $type == 'dateadded':
					$do_add = false;
					$just_add = true;
					$value = 'NOW()';
					break;
				case in_array($name, $non_parameters):
					$do_add   = false;
					$just_add = true;
					$value = $data[$name];
					break;
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
				$query = 'INSERT INTO ' . $this->getSource() . " ($field_str) VALUES ($value_str)";
				if (!empty($options['on_duplicate'])) {
					if (is_array($options['on_duplicate'])) {
						$temp = array();
						//This is potentially buggy if name isn't a simple string...
						foreach($options['on_duplicate'] as $name => $value) {
							$parameters[':update_' . $name] = $value;
							$temp[] = Query::enclose($name) . ' = :update_' . $name;
						}
						$query .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $temp);
					//Use this for fields that get's changed in process();
					} else if (array_key_exists($options['on_duplicate'], $fields)) {
						$query .= ' ON DUPLICATE KEY UPDATE ' . Query::enclose($options['on_duplicate']) . ' = :' . $options['on_duplicate'];
					} else {
						$query .= ' ON DUPLICATE KEY UPDATE ' . $options['on_duplicate'];
					}
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

		$query = false;
		$field_data = array();
		$value_data = array();
		$parameters = array();

		$non_parameter_aware = array_key_exists('non_parameter', $options);
		$non_parameters = array_key_exists('non_parameter', $options) ? $options['non_parameter'] : array();
		foreach ($fields as $name => $options) {
			$options = is_array($options) ? $options : array('type' => $options);
			$type = array_key_exists('type', $options) ? $options['type'] : 'string';
			if (array_key_exists($name, $data)) {
				$do_add = true;
				$just_add = false;
				$value = null;
				switch (true) {
				case $non_parameter_aware && preg_match(REGEX_SQL_FUNCTION, strtoupper($data[$name])):
					$do_add   = false;
					$just_add = true;
					$value = $data[$name];
					break;
				case substr($type, 0, 8) == 'password':
					if (!is_null($data[$name])) {
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
				case in_array($name, $non_parameters):
					$do_add   = false;
					$just_add = true;
					$value = $data[$name];
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
				$query = 'UPDATE ' . $this->getSource() . " SET $value_str WHERE `$id_field` = :id";
				$parameters[':id'] = $this->meta['id'];
			} else {
				throw new Exception('Update Query Fields and Values don\'t match');
			}
		}
		return array($query, count($parameters) ? $parameters : false);
	}

	public function getDeleteSQL() {
		$query = false;
		if ($id) {
			extract($this->meta);
			$query = new DeleteQuery($this);
			$query->filter("`$table`.`$id_field` = :{$table}_id LIMIT 1");
		}
		return $query;
	}

	public function getInstallSQL() {
		extract($this->meta);
		$query_fields = array();
		$query_keys = array();
		$keys = empty($keys) ? array() : $keys;
		foreach($fields as $field => $field_options) {
			$field_arr = array();
			if (is_string($field_options)) {
				$field_options = array('type' => $field_options);
			}
			$type    = array_key_exists('type',    $field_options) ? $field_options['type']    : 'string';
			$default = array_key_exists('default', $field_options) ? $field_options['default'] : null;
			$null    = array_key_exists('null',    $field_options) ? $field_options['null']    : false;
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
				$string_size = empty($field_options['string_size']) ? 32 : $field_options['string_size'];
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
			case 'url':
				//No break;
			case 'email':
				//No break;
			case 'telnumber':
				//No break;
			case 'title':
				//No break;
			case 'string':
				$string_size = empty($field_options['string_size']) ? 255 : $field_options['string_size'];
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
			case 'numeric':
				//No break;
			case 'integer':
				$int_size = empty($field_options['int_size']) ? 11 : $field_options['int_size'];
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
			case 'bool':
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
				var_dump('InstallSQL Failure: ', $field, $field_options);
				break;
			}
			if ($null) {
				$field_arr[] = 'NULL';
			} else {
				$field_arr[] = 'NOT NULL';
			}
			if (!is_null($default)) {
			    if (!in_array($default, array('NULL', 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))) {
			        $default = '"' . $default . '"';
			    }
				$field_arr[] = 'DEFAULT ' . $default;
			}
			$query_fields[] = implode(' ', array_map('trim', $field_arr));
		}

		foreach($keys as $key => $key_options) {
			//Legacy
			if (is_string($key_options)) {
				$fields = array_map('trim', explode(',', $key));
				$key    = ucwords($key_options) . str_replace(' ', '', ucwords(implode(' ', $fields)));
				$key_options = array(
					'fields' => $fields,
					'type'   => $key_options
				);
			}
			if (empty($key_options['type']) || empty($key_options['fields'])) {
				continue;
			}
			$fields = '`' . implode('`, `', $key_options['fields']) . '`';
			switch ($key_options['type']) {
			case 'primary':
				$query_keys[] = 'PRIMARY KEY(' . $fields . ')';
				break;
			case 'unique':
				$query_keys[] = 'UNIQUE KEY `' . $key . '` (' . $fields . ')';
				break;
			case 'index':
				$query_keys[] = 'KEY `' . $key . '` (' . $fields . ')';
				break;
			}
		}

		//Add the keys and the fields together
		if (count($query_keys)) {
			$query_fields = array_merge($query_fields, $query_keys);
		}

		$query = 'CREATE TABLE IF NOT EXISTS ' . $this->getSource() . ' (';
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

	public function getObjectName() {
		return $this->getMeta('name');
	}

	public function getSearchFields() {
		return array_map(array('Query', 'enclose'), array_keys($this->getMeta('fields')));
	}

	public function getArea() {
		return class_for_url(get_class($this));
	}

	/**
	 * If the object has an owner_id field, check that against the current user
	 *
	 * We ignore the action so that it checks every action. You can customize
	 * this per action by overriding this function in the model
	 */
	public function checkOwnership($action) {
		$data = $this->array ? $this->array : ($this->object ? (array)$this->object : false);
        if (!$data) {
        	//Return true, otherwise invalid objects trigger permission errors
			return true;
        }
		if (!array_key_exists('owner_id', $data)) {
			//No Owner defined
			return true;
		}
		$user = BackendUser::check();
		if (
		    ($user && $user->id == $data['owner_id'])
		    || in_array('superadmin', $user->roles)
		) {
			return true;
		}
		return false;
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
