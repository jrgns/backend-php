<?php
/**
 * The class file for Query
 *
 * @author J Jurgens du Toit (JadeIT cc) <jurgens.dutoit@gmail.com> - initial API and implementation
 * @copyright Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available {@link http://www.eclipse.org/legal/epl-v10.html here}
 * @license http://www.eclipse.org/legal/epl-v10.html Eclipse Public License v1.0
 * @package Utilities
 */
/**
 * Base class to handle queries
 */
class Query {
	protected $connection;

	protected $action     = false;
	protected $table      = false;
	protected $query      = false;
	protected $distinct   = false;
	protected $fields     = array();
	protected $conditions = array();
	protected $parameters = array();
	protected $group      = array();
	protected $order      = array();
	protected $having     = array();
	protected $limit      = array();

	protected $last_stmt   = false;
	protected $last_params = array();
	
	public $error_msg  = false;
	public $error_code = 0;
	
	/**
	 * @param The type of query this is. Must be one of SELECT, INSERT, DELETE, UPDATE or SHOW.
	 * @param 
	 */
	function __construct($action, $table, array $options = array()) {
		$action = strtoupper($action);
		if (!in_array($action, array('SELECT', 'INSERT', 'REPLACE', 'DELETE', 'UPDATE', 'SHOW'))) {
			throw new Exception('Unknown Query Action');
			return false;
		}
		if (array_key_exists('connection', $options) && $options['connection'] instanceof PDO) {
			$this->connection = $options['connection'];
		} else {
			$this->connection = self::getConnection($table);
		}
		$this->action = $action;
		$this->table = self::getTable($table);
		if (array_key_exists('fields', $options)) {
			$this->fields = is_array($options['fields']) ? $options['fields'] : array($options['fields']);
		}
	}

	private function checkConnection() {
		if (!$this->connection instanceof PDO) {
			$this->connection = Backend::getDB();
		}
		return ($this->connection instanceof PDO);
	}
	
	public function execute(array $parameters = array(), array $options = array()) {
		$toret = false;
		$this->error_msg  = false;
		$this->error_code = 0;
		if (empty($this->query)) {
			$this->last_stmt = false;
			$this->query = $this->buildQuery();
		}
		if ($this->checkConnection() && !empty($this->query)) {
			$parameters = array_merge($this->parameters, $parameters);
			//Check if we've already executed this query, and that the parameters are the same
			$check_cache = array_key_exists('check_cache', $options) ? $options['check_cache'] : true;
			if ($check_cache && $this->last_stmt && !count(array_diff_assoc($parameters, $this->last_params))) {
				if (Controller::$debug >= 2) {
					var_dump('Executing Cached statement');
				}
				$toret = $this->last_stmt;
			} else {
				$stmt = $this->connection->prepare($this->query);
				if ($stmt) {
					if ($stmt->execute($parameters)) {
						$toret             = $stmt;
						$this->last_stmt   = $stmt;
						$this->last_params = $parameters;
					} else {
						$error_info = $stmt->errorInfo();
						
						$verbose_error = array('Query::execute Error:');
						if (!empty($error_info[2])) {
							$verbose_error[] = $error_info[2];
						}
						if (!empty($error_info[1])) {
							$verbose_error[] = '(' . $error_info[1] . ')';
						}
						$verbose_error = implode(' ', $verbose_error);
						if (class_exists('BackendError', false) && empty($options['dont_moan'])) {
							BackendError::add($verbose_error, 'execute');
						}

						if (Controller::$debug) {
							print_stacktrace();
							echo 'Error Info:';
							var_dump($error_info);
							if (Controller::$debug >= 2) {
								echo 'Query:<pre>' . PHP_EOL . $stmt->queryString . '</pre>';
							}
							$this->error_msg = $verbose_error;
						} else {
							$this->error_msg = 'Error executing statement';
							if (!empty($error_info[1])) {
								$this->error_msg .=  '(' . $error_info[1] . ')';
							}
						}
						$this->error_code = $error_info[1];
					}
				} else {
					$this->error_msg = 'Could not prepare statement';
				}
			}
		} else {
			$this->error_msg = 'Could not execute query';
		}
		return $toret;
	}
	
	function distinct() {
		$this->query = false;
		$this->distinct = true;
		return $this;
	}

	public function field($field) {
		$this->query = false;
		if (is_array($field)) {
			$this->fields = array_merge($this->fields, $field);
		} else {
			$this->fields[] = $field;
		}
		return $this;
	}
	
	public function setFields(array $fields = array()) {
		$this->query = false;
		$this->fields = $fields;
		return $this;
	}
	
	public function getFields() {
		return $this->fields;
	}
	
	public function filter($condition) {
		$this->query = false;
		if (is_array($condition)) {
			$this->conditions = array_merge($this->conditions, $condition);
		} else {
			$this->conditions[] = $condition;
		}
		$this->conditions = array_filter(array_unique($this->conditions));
		return $this;
	}
	
	public function setFilter(array $filters = array()) {
		$this->query = false;
		$this->conditions = array_filter(array_unique($filters));
		return $this;
	}
	
	public function getFilterd() {
		return $this->conditions;
	}
	
	public function parameter($name, $value) {
		if (!empty($name)) {
			$this->parameters[$name] = $value;
		} else {
			$this->parameters[] = $value;
		}
		return $this;
	}

	public function setParameters(array $parameters = array()) {
		$this->parameters = $parameters;
		return $this;
	}
	
	public function getParameter($name) {
		if (array_key_exists($name, $this->parameters)) {
			return $this->parameters[$name];
		}
		return null;
	}
	
	public function getParameters() {
		return $this->parameters;
	}
	
	public function group($group_field) {
		$this->query = false;
		if (is_array($group_field)) {
			$this->group = array_merge($this->group, $group_field);
		} else {
			$this->group[] = $group_field;
		}
		return $this;
	}
	
	public function setGroup(array $group = array()) {
		$this->query = false;
		$this->group = $group;
		return $this;
	}
	
	public function getGroup() {
		return $this->group;
	}
	
	public function order($order_field) {
		$this->query = false;
		if (is_array($order_field)) {
			$this->order = array_merge($this->group, $order_field);
		} else if (!empty($order_field)) {
			$this->order[] = $order_field;
		}
		return $this;
	}
	
	public function setOrder(array $order = array()) {
		$this->query = false;
		$this->order = $order;
		return $this;
	}
	
	public function getOrder() {
		return $this->order;
	}
	
	public function having($condition) {
		$this->query = false;
		if (is_array($condition)) {
			$this->having = array_merge($this->having, $condition);
		} else {
			$this->having[] = $condition;
		}
		$this->having = array_filter(array_unique($this->having));
		return $this;
	}
	
	public function setHaving(array $having = array()) {
		$this->query  = false;
		$this->having = array_filter(array_unique($having));
		return $this;
	}
	
	public function limit($one, $two = false) {
		$this->query = false;
		if ($two !== false) {
			$this->limit = array($one, $two);
		} else {
			if (is_string($one)) {
				$tmp = preg_split('/[,]\s*/', $one);
			} else if (is_array($one)) {
				$tmp = $one;
			}
			if (isset($tmp) && count($tmp) == 2) {
				$this->limit = $tmp;
			} else if ($one) {
				$this->limit = array(0, $one);
			} else {
				$this->limit = false;
			}
		}
		return $this;
	}
	
	public function fetchAssoc(array $parameters = array(), array $options = array()) {
		$toret = $this->execute($parameters);
		if ($toret) {
			$toret = $toret->fetch(PDO::FETCH_ASSOC);
		}
		return $toret;
	}
	
	/**
	 * Return all the matching records
	 *
	 * Options:
	 * with_key - ??
	 * group - Groups values by the first column
	 * column - Fetch only one column
	 */
	public function fetchAll(array $parameters = array(), array $options = array()) {
		if ($results = $this->execute($parameters)) {
			if (!empty($options['with_key'])) {
				//
				$results = $results->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
				if ($results) {
					$results = array_map('reset', $results);
				}
			} else if (!empty($options['group'])) {
				//Groups values by the first column
				$results = $results->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP);
				if ($results) {
					$results = array_map('reset', $results);
				}
			} else if (array_key_exists('column', $options)) {
				//Fetch only one column
				$results = $results->fetchAll(PDO::FETCH_COLUMN, $options['column']);
			} else {
				$results = $results->fetchAll(PDO::FETCH_ASSOC);
			}
		}
		return $results;
	}
	
	public function fetchColumn(array $parameters = array(), $column = null, array $options = array()) {
		$column = is_null($column) ? 0 : $column;
		$toret = $this->execute($parameters);
		if ($toret) {
			$toret = $toret->fetchColumn($column);
		}
		return $toret;
	}
	
	public function fetchCSV(array $parameters = array(), array $options = array()) {
		$result = false;
		if ($stmt = $this->execute($parameters)) {
			if (empty($options['filename'])) {
				if (!$fp = fopen('php://temp', 'r+')) {
					self::$error_msg = 'Could not open output file for CSV';
					return false;
				}
			} else {
				if (!$fp = fopen($options['filename'], 'w')) {
					self::$error_msg = 'Could not open output file for CSV';
					return false;
				}
			}
			$first = false;
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				if (!$first) {
					fputcsv($fp, array_keys($row));
					$first = true;
				}
				fputcsv($fp, $row);
			}
			if (empty($options['filename'])) {
				$result = $fp;
			} else {
				$result = $options['filename'];
			}
		}
		return $result;
	}

	public function setQuery($query, array $options = array()) {
		if (array_key_exists('connection', $options) && $options['connection'] instanceof PDO) {
			$this->connection = $options['connection'];
		}
		$this->last_stmt = false;
		if ($query instanceof Query) {
			$this->query = $query->__toString();
		} else {
			$this->query = $query;
		}
		return $this;
	}
	
	protected function buildQuery() {
		$query = $this->buildTable();		
		if (!empty($this->conditions)) {
			$query .= PHP_EOL . 'WHERE (' . implode(') AND (', $this->conditions) . ')';
		}
		if (!empty($this->group)) {
			$query .= PHP_EOL . 'GROUP BY ' . implode(', ', $this->group);
		}
		if (!empty($this->having)) {
			$query .= PHP_EOL . 'HAVING (' . implode(') AND (', $this->having) . ')';
		}
		if (!empty($this->order)) {
			$query .= PHP_EOL . 'ORDER BY ' . implode(', ', $this->order);
		}
		if (!empty($this->limit)) {
			if (get_class($this) == 'SelectQuery') {
				$query .= PHP_EOL . 'LIMIT ' . implode(', ', $this->limit);
			} else {
				$query .= PHP_EOL . 'LIMIT ' . end($this->limit);
			}
		}
		return $query;
	}
	
	protected function buildTable() {
		$query = '';
		return $query;
	}
	
	public static function enclose($element) {
		if (strpos($element, '.')) {
			$toret = array();
			foreach(explode('.', $element) as $elm) {
				$toret[] = self::enclose($elm);
			}
			return implode('.', $toret);
		} else if (
			substr($element, 0, 1) != '`' && 
			substr($element, 0, -1) != '`' &&
			$element != '*'
		) {
			return '`' . $element . '`';
		}
		return $element;
	}
	
	public static function getTable($table) {
		if ($table instanceof DBObject) {
			$table = $table->getSource();
		} else if ($table instanceof Query) {
			//Dont enclose
			return $table->table;
		} else if (is_array($table)) {
			$tables = array();
			foreach($table as $one_table) {
				$tables[] = self::getTable($one_table);
			}
			//Dont enclose
			return implode(', ', $tables);
		} else if ($components = Component::getActive()) {
			if (substr($table, -3) == 'Obj') {
				$table = substr($table, 0, strlen($table) - 3);
			}
			$components = array_flatten($components, null, 'name');
			if (in_array($table, $components) && class_exists($table . 'Obj', true)) {
				$name  = $table . 'Obj';
				$table = new $name();
				$table = $table->getSource();
			}
		}
		if (empty($table)) {
			throw new Exception('Empty Table for Query');
		}
		return self::enclose($table);
	}
	
	public static function getConnection($table) {
		if ($table instanceof DBObject) {
			return $table->getConnection();
		} else if (is_array($table)) {
			$table = current($table);
			return self::getConnection($table);
		} else if ($components = Component::getActive()) {
			if (substr($table, -3) == 'Obj') {
				$table = substr($table, 0, strlen($table) - 3);
			}
			$components = array_flatten($components, null, 'name');
			if (in_array($table, $components) && class_exists($table . 'Obj', true)) {
				$name  = $table . 'Obj';
				$table = new $name();
				return $table->getConnection();
			}
		}
		return false;
	}
	
	public function __toString() {
		$toret = empty($this->query) ? $this->buildQuery() : $this->query;
		return $toret;
	}
}
