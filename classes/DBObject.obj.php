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
	protected $last_error;
	

	public $list = null;
	public $array = null;
	public $object = null;
	public $inserted_id;

	function __construct($meta = array(), array $options = null) {
		if (!is_array($meta)) {
			if (is_numeric($meta)) {
				$meta = array('id' => $meta);
			} else {
				$meta = array();
			}
		}
		$options = $options ? $options : array();
		$load_type        = array_key_exists('load_type', $options) ? $options['load_type'] : 'array';
		$meta['id']       = array_key_exists('id', $meta) ? $meta['id'] : false;
		$meta['id_field'] = array_key_exists('id_field', $meta) ? $meta['id_field'] : 'id';
		$meta['table']    = array_key_exists('table', $meta) ? $meta['table'] : table_name(get_class($this));
		$meta['database'] = array_key_exists('database', $meta) ? $meta['database'] : Backend::getConfig('backend.dbs.default.alias', 'default');
		$meta['provider'] = array_key_exists('provider', $meta) ? $meta['provider'] : Backend::getConfig('backend.provider', 'MySQL');
		$meta['fields']   = array_key_exists('fields', $meta) ? $meta['fields'] : array();
		$meta['name']     = array_key_exists('name', $meta) ? $meta['name'] : class_name(get_class($this));
		$meta['objname']  = array_key_exists('objname', $meta) ? $meta['objname'] : get_class($this);
		$meta['children'] = array_key_exists('children', $meta) ? $meta['children'] : array();
		$this->meta = $meta;
		if ($this->checkConnection()) {
			if ($meta['id']) {
				$this->load(array('mode' => $load_type));
			}
		}
	}
	
	private function checkConnection() {
		if (!$this->db instanceof PDO) {
			$this->db = Backend::getDB($this->meta['database']);
			if (!$this->db instanceof PDO) {
				Controller::whoops(array('title' => 'No Database setup', 'message' => 'Please make sure that the application has been setup correctly'));
			}
		}
		return ($this->db instanceof PDO);
	}
	
	private function load_children() {
		if ($this->object) {
			foreach ($this->meta['children'] as $name => $options) {
				$class_name = array_key_exists('model', $options) ? $options['model'] . 'Obj' : false;
				if ($class_name && class_exists($class_name, true)) {
					$object = new $class_name();
					$conditions = array_key_exists('conditions', $options) ? $options['conditions'] : false;
					if ($conditions) {
						$conds = array();
						$params = array();
						foreach($conditions as $field => $value) {
							if (array_key_exists($value, $this->object)) {
								$conds[] = '`' . $field . '` = :' . $value;
								$params[':' . $value] = $this->object->$value;
							}
						}
						$object->load(array('conditions' => $conds, 'parameters' => $params, 'mode' => 'list'));
						if ($object->list) {
							$this->object->$name = $object->list;
						}
					}
				}
			}
		}
	}
	
	public function load($options = array()) {
		if ($this->checkConnection()) {
			if (!array_key_exists('mode', $options)) {
				if (empty($this->meta['id'])) {
					$options['mode'] = 'list';
				} else {
					$options['mode'] = 'array';
				}
			}

			if (array_key_exists('query', $options)) {
				$query = $options['query'];
				$params = array_key_exists('parameters', $options) ? $options['parameters'] : array();
			} else {
				list ($query, $params) = $this->getSelectSQL($options);
			}
			if (Controller::$debug) {
				var_dump('Options:', $options);
				echo 'Query:<br/><pre>';
				echo $query . '</pre>';
				var_dump('Params:', $params);
			}
			if (!empty($query)) {
				$stmt = $this->db->prepare($query);
				$result = $stmt->execute($params);
				if ($result) {
					switch ($options['mode']) {
					case 'full_object':
						$this->object = $stmt->fetch(PDO::FETCH_OBJ);
						$this->array = $stmt->fetch(PDO::FETCH_ASSOC);
						$this->load_children();
						break;
					case 'object':
						$this->object = $stmt->fetch(PDO::FETCH_OBJ);
						break;
					case 'array':
						$this->array = $stmt->fetch(PDO::FETCH_ASSOC);
					case 'list':
					default:
						$this->list = $stmt->fetchAll(PDO::FETCH_ASSOC);
						break;
					}
					if ($this->object) {
						$this->object = $this->process($this->object, 'out');
					}
					if ($this->array) {
						$this->array = $this->process($this->array, 'out');
					}
				} else {
					$this->last_error = $stmt->errorInfo();
					if (Controller::$debug) {
						echo 'Error Info:';
						var_dump('Error Info:', $stmt->errorInfo());
					}
				}
			} else {
				Controller::addError('No Query to load');
			}
		} else {
			Controller::addError('DB Connection error');
		}
	}
	
	function process($data, $direction) {
		foreach($data as $name => $value) {
			if (array_key_exists($name, $this->meta['fields'])) {
				switch ($this->meta['fields'][$name]) {
				case 'serialized':
					switch ($direction) {
					case 'in':
						$data[$name] = base64_encode(serialize($value));
						break;
					case 'out':
						$data[$name] = unserialize(base64_decode($value));
						break;
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
	
	public function create($data, $options = array()) {
		$toret = false;
		if ($this->checkConnection()) {
			$data = $this->validate($data, 'create', $options);
			if ($data) {
				$data = $this->process($data, 'in');
				list ($query, $params) = $this->getCreateSQL($data, $options);
				$stmt = $this->db->prepare($query);
				$toret = $stmt->execute($params);
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
				} else {
					$this->last_error = $stmt->errorInfo();
					if (Controller::$debug) {
						echo 'Error Info:';
						var_dump($stmt->errorInfo());
					}
				}
			}
		} else {
			Controller::addError('DB Connection error');
		}
		return $toret;
	}
	
	public function replace($data, $options = array()) {
		$toret = false;
		if ($this->checkConnection()) {
			$data = $this->validate($data, 'create', $options);
			if ($data) {
				$data = $this->process($data, 'in');
				list ($query, $params) = $this->getCreateSQL($data, $options);
				$query = preg_replace('/^INSERT/', 'REPLACE', $query);
				$stmt = $this->db->prepare($query);
				$toret = $stmt->execute($params);
				if ($toret) {
					//TODO This will potentially break if there are triggers in use
					$this->inserted_id = $this->db->lastInsertId();
					$this->meta['id']  = $this->inserted_id;
					$toret             = $this->inserted_id;
					if (array_key_exists('load', $options) ? $options['load'] : true) {
						$this->load();
					}
				} else {
					$this->last_error = $stmt->errorInfo();
					if (Controller::$debug) {
						echo 'Error Info:';
						var_dump($stmt->errorInfo());
					}
				}
			}
		} else {
			Controller::addError('DB Connection error');
		}
		return $toret;
	}
	
	public function retrieve($parameter) {
		$toret = false;
		if ($this->checkConnection()) {
			$query = $this->getRetrieveSQL();
			if ($query) {
				$stmt = $this->db->prepare($query);
				if ($stmt->execute(array(':parameter' => $parameter))) {
					$toret = $stmt->fetch(PDO::FETCH_ASSOC);
				}
			} else {
				die('error');
			}
		} else {
			Controller::addError('DB Connection error');
		}
		return $toret;
	}
	
	public function read($mode = false) {
		$toret = null;
		if ($this->checkConnection()) {
			$id = $this->meta['id'];
			$mode = $mode ? $mode : ($id ? 'array' : 'list');
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
			Controller::addError('DB Connection error');
		}
		return $toret;
	}

	function update($data, $options = array()) {
		$toret = false;
		if ($this->checkConnection()) {
			$data = $this->validate($data, 'update', $options);
			if ($data) {
				list ($query, $params) = $this->getUpdateSQL($data, $options);
				$stmt = $this->db->prepare($query);
				$toret = $stmt->execute($params);
				if ($toret) {
					if (array_key_exists('load', $options) ? $options['load'] : true) {
						$this->load();
					}
				} else {
					$this->last_error = $stmt->errorInfo();
					if (Controller::$debug) {
						echo 'Error Info:';
						var_dump($stmt->errorInfo());
					}
				}
			}
		} else {
			Controller::addError('DB Connection error');
		}
		return $toret;
	}
	
	function delete() {
		$toret = false;
		if ($this->checkConnection()) {
			extract($this->meta);
			$query = new CustomQuery("DELETE FROM `$table` WHERE `$id_field` = :id LIMIT 1");
			$toret = $query->execute(array(':id' => $this->meta['id']));
		} else {
			Controller::addError('DB Connection error');
		}
		return $toret;
	}
	
	function truncate() {
		$toret = false;
		if ($this->checkConnection()) {
			extract($this->meta);
			$query = new CustomQuery("TRUNCATE `$table`");
			$toret = $query->execute();
		} else {
			Controller::addError('DB Connection error');
		}
		return $toret;
	}
	
	function validate($data, $action, $options = array()) {
		$ret_data = array();
		$toret = true;
		if (is_array($data)) {
			foreach($this->meta['fields'] as $name => $field) {
				$value = array_key_exists($name, $data) ? $data[$name] : null;
				switch($field) {
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
							Controller::addError('Please supply a valid string for ' . humanize($name));
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
							Controller::addError('Please supply a valid email address');
							$toret = false;
						}
					}
					break;
				case 'url':
					if ($value !== null) {
						if (filter_var($value, FILTER_VALIDATE_URL)) {
							$value = filter_var($value, FILTER_VALIDATE_URL);
						} else {
							Controller::addError('Please supply a valid URL');
							$value = null;
						}
					}
					break;
				case 'url_with_scheme':
					if ($value !== null) {
						if (filter_var($value, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
							$value = filter_var($value, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
						} else {
							Controller::addError('Please supply a valid URL with a scheme');
							$value = null;
						}
					}
					break;
				case 'ip_address':
					if ($value !== null) {
						if (!filter_var($value, FILTER_VALIDATE_IP)) {
							Controller::addError('Please supply a valid URL with a scheme');
							$value = null;
						}
					}
					break;
				default:
					if ($value !== null) {
					}
					break;
				}
				if (!is_null($value)) {
					$ret_data[$name] = $value;
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
		foreach($this->meta['fields'] as $name => $field) {
			if (in_array($field, array('blob'))) {
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
						Controller::addError($message);
					} else {
						$file = array();
						$file['name']     = $_FILES['obj']['name'][$name];
						$file['type']     = $_FILES['obj']['type'][$name];
						$file['tmp_name'] = $_FILES['obj']['tmp_name'][$name];
						$file['error']    = $_FILES['obj']['error'][$name];
						$file['size']     = $_FILES['obj']['size'][$name];
						$data[$name] = $file;
					}
				}
			} else {
				$data[$name] = array_key_exists($name, $data) ? $data[$name] : null;
			}
		}
		return $data;
	}
	
	public function getLastError() {
		return $this->last_error;
	}
	
	public function getSelectSQL($options = array()) {
		extract($this->meta);
		$database = Backend::get('DB_' . $database, $database);
		$mode = array_key_exists('mode', $options) ? $options['mode'] : 'list';
		$joins = array_key_exists('joins', $options) ? $options['joins'] : array();
		if (is_string($joins)) {
			$joins = array($joins);
		}
		$fields = array_key_exists('fields', $options) ? $options['fields'] : array();
		if (is_string($fields)) {
			$fields = array($fields);
		}
		$conditions = array();
		$parameters = array();
		$limit = false;
		switch ($mode) {
			case 'object':
			case 'array':
			case 'full_object':
				if ($id) {
					$conditions[] = "`$table`.`$id_field` = :{$table}_id";
					$parameters[":{$table}_id"] = $id;
				} else {
					$limit = empty($limit) ? 1 : $limit;
				}
				break;
			case 'list':
				$limit = array_key_exists('limit', $options) ? $options['limit'] : false;
				break;
		}
		if (array_key_exists('conditions', $options)) {
			if (is_array($options['conditions'])) {
				$conditions = array_merge($conditions, $options['conditions']);
			} else if (is_string($options['conditions'])) {
				$conditions[] = $options['conditions'];
			}
		}
		if (array_key_exists('parameters', $options)) {
			if (is_array($options['parameters'])) {
				$parameters = array_merge($parameters, $options['parameters']);
			}
		}
		if (Controller::$debug >= 2) {
			var_dump('Conditions:', $conditions);
		}
		$query = 'SELECT';
		if (count($fields)) {
			$query .= implode(', ', $fields);
		} else {
			$query .= " `$table`.* ";
		}
		
		$query .= " FROM `$database`.`$table`";
		if (count($joins)) {
			foreach($joins as $join) {
				$query .= ' ' . $join;
			}
		}
		if (count($conditions)) {
			$query .= ' WHERE (' . implode(') AND (', $conditions) . ')';
		}
		if ($limit) {
			$query .= ' LIMIT ' . $limit;
		}
		if (array_key_exists('order', $options)) {
			$query .= ' ORDER BY ' . $options['order'];
		}
		return array($query, $parameters);
	}
	
	public function getRetrieveSQL() {
		extract($this->meta);
		$database = Backend::get('DB_' . $database, $database);
		return 'SELECT * FROM `' . $database . '`.`' . $table . '` WHERE `id` = :parameter';
	}

	public function getCreateSQL($data, array $options = array()) {
		extract($this->meta);
		$database = Backend::get('DB_' . $database, $database);
		$query = false;
		$field_data = array();
		$value_data = array();
		$parameters = array();
		foreach ($fields as $name => $field) {
			if (array_key_exists($name, $data)) {
				$field_data[] = $name;

				$do_add = true;
				$just_add = false;
				$value = null;
				switch (true) {
					case substr($field, 0, 8) == 'password':
						if (strpos($field, ':') !== false) {
							$temp = explode(':', $field);
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
					case $field == 'lastmodified':
						$do_add = false;
						break;
					case $field == 'dateadded':
						$do_add = false;
						$just_add = true;
						$value = 'NOW()';
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
				$query = "INSERT INTO `$database`.`$table` ($field_str) VALUES ($value_str)";
				if (!empty($options['on_duplicate'])) {
					$query .= ' ON DUPLICATE KEY UPDATE ' . $options['on_duplicate'];
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
		foreach ($fields as $name => $field) {
			if (array_key_exists($name, $data)) {
				$do_add = true;
				$just_add = false;
				$value = null;
				switch (true) {
					case substr($field, 0, 8) == 'password':
						if (!is_null($data[$name])) {
							if (strpos($field, ':') !== false) {
								$temp = explode(':', $field);
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
						} else {
							unset($data['password']);
							$do_add = false;
						}
						break;
					case $field == 'lastmodified':
						$do_add = false;
						break;
					case $field == 'dateadded':
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
				$query = "UPDATE `$database`.`$table` SET $value_str WHERE `id` = :id";
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
	
}
