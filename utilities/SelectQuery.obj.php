<?php
/**
 * The class file for SelectQuery
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
 * Base class to handle select queries
 */
class SelectQuery extends Query {
	protected $joins    = array();

	function __construct($table, array $options = array()) {
		parent::__construct('SELECT', $table, $options);
	}

	protected function buildTable() {
		$query = 'SELECT';
		if ($this->distinct) {
			$query .= ' DISTINCT';
		}
		if (empty($this->fields)) {
			$query .= ' *';
		} else {
			$query .= PHP_EOL . "\t" . implode(',' . PHP_EOL . "\t", $this->fields);
		}
		$query .= PHP_EOL . 'FROM ' . $this->table;
		if (!empty($this->joins)) {
			$tables = array();
			foreach ($this->joins as $type => $join) {
				foreach ($join as $table => $conditions) {
					$one_table = $type . ' JOIN ';
					if (strpos($table, ' AS ') === false) {
						$table = Query::enclose($table);
					}
					$one_table .= $table;
					if (!empty($conditions)) {
						$one_table .= ' ON (' . implode(') AND (', $conditions) . ')';
					}
					$tables[] = $one_table;
				}
			}
			$query .= PHP_EOL . implode(PHP_EOL, $tables);
		}
		return $query;
	}

	function leftJoin($table, $conditions, array $options = array()) {
		return $this->join('LEFT', $table, $conditions, $options);
	}

	function rightJoin($table, $conditions, array $options = array()) {
		return $this->join('RIGHT', $table, $conditions, $options);
	}

	function innerJoin($table, $conditions, array $options = array()) {
		return $this->join('INNER', $table, $conditions, $options);
	}

	function outerJoin($table, $conditions, array $options = array()) {
		return $this->join('OUTER', $table, $conditions, $options);
	}

	function join($type, $table, $conditions, array $options = array()) {
		$this->query = false;
		$type = strtoupper($type);
		if (!in_array($type, array('RIGHT', 'LEFT', 'INNER', 'OUTER'))) {
			throw new Exception('Unsupported Join Type');
		}
		if (!is_array($conditions)) {
			$conditions = array($conditions);
		}
		if (!array_key_exists($type, $this->joins)) {
			$this->joins[$type] = array();
		}
		if ($table instanceof Query) {
			$table = $table->__toString();
			if (array_key_exists('alias', $options)) {
				$table = '(' . $table . ') AS ' . Query::enclose($options['alias']);
			} else {
				trigger_error('Joined sub queries should have aliases', E_USER_ERROR);
			}
		} else {
			$table = Query::getTable($table);
			if (array_key_exists('alias', $options)) {
				$table = Query::enclose($table) . ' AS ' . Query::enclose($options['alias']);
			}
		}
		if (array_key_exists($table, $this->joins[$type])) {
			$this->joins[$type][$table] = array_merge($this->joins[$type][$table], $conditions);
		} else {
			$this->joins[$type][$table] = $conditions;
		}
		return $this;
	}

	function joinArray(array $array) {
		if (count($array) == 3) {
			if (array_key_exists('type', $array) && array_key_exists('table', $array) && array_key_exists('conditions', $array)) {
				return $this->join($array['type'], $array['table'], $array['conditions']);
			} else {
				list($type, $table, $conditions) = $array;
				return $this->join($type, $table, $conditions);
			}
		}
		//Maybe add a warning or exception?
		return $this;
	}

	function getCount($parameters) {
		$this->buildTable();
		$count_query = clone($this);
		$count_query
			->setOrder(array())
			->setGroup(array());
		$count_query = new CustomQuery(preg_replace(REGEX_MAKE_COUNT_QUERY, '$1 COUNT(*) $3', $count_query));
		return $count_query->fetchColumn($parameters);
	}
}
