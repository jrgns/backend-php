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
	protected $fields     = array();
	protected $conditions = array();
	protected $parameters = array();
	protected $group      = array();
	protected $order      = array();
	protected $limit      = array();

	protected $last_stmt   = false;
	protected $last_params = array();
	
	public $last_error = false;
	
	function __construct($action, $table, array $options = array()) {
		$action = strtoupper($action);
		if (!in_array($action, array('SELECT', 'INSERT', 'DELETE', 'UPDATE', 'SHOW'))) {
			trigger_error('Unknown Query Action', E_USER_ERROR);
			return false;
		}
		$this->action = $action;
		if ($table instanceof DBObject) {
			$table = $table->getSource();
		}
		if (array_key_exists('fields', $options)) {
			$this->fields = is_array($options['fields']) ? $options['fields'] : array($options['fields']);
		}
		$this->table = self::enclose($table);
	}

	private function checkConnection() {
		if (!$this->connection instanceof PDO) {
			$this->connection = Backend::getDB();
		}
		return ($this->connection instanceof PDO);
	}
	
	public function execute(array $parameters = array(), array $options = array()) {
		$toret = false;
		$this->last_error = false;
		if (empty($this->query)) {
			$this->query = $this->buildQuery();
		}
		if ($this->checkConnection() && !empty($this->query)) {
			$parameters = array_merge($this->parameters, $parameters);
			//Check if we've already executed this query, and that the parameters are the same
			$check_cache = array_key_exists('check_cache', $options) ? $options['check_cache'] : true;
			if ($check_cache && $this->last_stmt && serialize($parameters) == serialize($this->last_params)) {
				if (Controller::$debug >= 2) {
					var_dump('Executing Cached statement');
				}
				$toret = $this->last_stmt;
			} else {
				$stmt = $this->connection->prepare($this->query);
				if ($stmt) {
					if ($stmt->execute($parameters)) {
						$toret = $stmt;
						$this->last_stmt = $stmt;
					} else {
						if (Controller::$debug) {
							$error_info = $stmt->errorInfo();
							
							$error = array('Query Error:');
							if (!empty($error_info[2])) {
								$error[] = $error_info[2];
							}
							if (!empty($error_info[1])) {
								$error[] = '(' . $error_info[1] . ')';
							}
							$error = implode(' ', $error);
							print_stacktrace();
							echo 'Error Info:';
							var_dump($error_info);
							if (Controller::$debug >= 2) {
								echo 'Query:<pre>' . PHP_EOL . $stmt->queryString . '</pre>';
							}
						} else {
							$error = 'Error executing statement';
						}
						$this->last_error = $error;
					}
				} else {
					$this->last_error = 'Could not prepare statement';
				}
			}
		} else {
			$this->last_error = 'Could not execute query';
		}
		return $toret;
	}
	
	public function field($field) {
		if (is_array($field)) {
			$this->fields = array_merge($this->fields, $field);
		} else {
			$this->fields[] = $field;
		}
		return $this;
	}
	
	public function filter($condition) {
		if (is_array($condition)) {
			$this->conditions = array_merge($this->conditions, $condition);
		} else {
			$this->conditions[] = $condition;
		}
		return $this;
	}
	
	public function group($group_field) {
		if (is_array($group_field)) {
			$this->group = array_merge($this->group, $group_field);
		} else {
			$this->group[] = $group_field;
		}
		return $this;
	}
	
	public function order($order_field) {
		if (is_array($order_field)) {
			$this->order = array_merge($this->group, $order_field);
		} else {
			$this->order[] = $order_field;
		}
		return $this;
	}
	
	public function limit($one, $two = false) {
		if ($two !== false) {
			$this->limit = array($one, $two);
		} else {
			if (is_string($one)) {
				$tmp = split('[,]\s*', $one);
			} else if (is_array($one)) {
				$tmp = $one;
			}
			if (isset($tmp) && count($tmp) == 2) {
				$this->limit = $tmp;
			} else {
				$this->limit = array(0, $one);
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
	
	public function fetchAll(array $parameters = array(), array $options = array()) {
		$toret = $this->execute($parameters);
		if ($toret) {
			if (empty($options['with_key'])) {
				$toret = $toret->fetchAll(PDO::FETCH_ASSOC);
			} else {
				$toret = $toret->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
			}
		}
		return $toret;
	}
	
	public function fetchColumn(array $parameters = array(), int $column = null, array $options = array()) {
		$column = is_null($column) ? 0 : $column;
		$toret = $this->execute($parameters);
		if ($toret) {
			$toret = $toret->fetchColumn($column);
		}
		return $toret;
	}

	public function setQuery($query) {
		$this->last_stmt = false;
		if ($query instanceof Query) {
			$this->query = $query->__toString();
		} else {
			$this->query = $query;
		}
	}
	
	protected function buildQuery() {
		switch ($this->action) {
		case 'DELETE':
			$query = $this->action . ' FROM ' . $this->table;
			break;
		case 'SELECT':
			$query = $this->action;
			if (empty($this->fields)) {
				$query .= ' *';
			} else {
				$query .= ' ' . implode(', ', $this->fields);
			}
			$query .= ' FROM ' . $this->table;
			break;
		}
		if (!empty($this->conditions)) {
			$query .= ' WHERE (' . implode(') AND (', $this->conditions) . ')';
		}
		if (!empty($this->group)) {
			$query .= ' GROUP BY ' . implode(', ', $this->group);
		}
		if (!empty($this->order)) {
			$query .= ' ORDER BY ' . implode(', ', $this->order);
		}
		if (!empty($this->limit)) {
			$query .= ' LIMIT ' . implode(', ', $this->limit);
		}
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
	
	public function __toString() {
		$toret = empty($this->query) ? $this->buildQuery() : $this->query;
		return $toret;
	}
}
