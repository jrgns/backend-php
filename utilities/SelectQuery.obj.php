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
	protected $joins = array();

	function __construct($table, array $options = array()) {
		parent::__construct('SELECT', $table, $options);
	}
	
	protected function buildQuery() {
		if (!empty($this->joins)) {
			$tables = array();
			foreach ($this->joins as $type => $join) {
				foreach ($join as $table => $conditions) {
					$one_table = $type . ' JOIN ' . $table;
					if (!empty($conditions)) {
						$one_table .= ' ON (' . implode(') AND (', $conditions) . ')';
					}
					$tables[] = $one_table;
				}
			}
		}
		$this->table .= ' ' . implode(PHP_EOL, $tables);
		return parent::buildQuery();
	}

	function leftJoin($table, $conditions) {
		return $this->join('LEFT', $table, $conditions);
	}
	
	function rightJoin($table, $conditions) {
		return $this->join('RIGHT', $table, $conditions);
	}

	function innerJoin($table, $conditions) {
		return $this->join('INNER', $table, $conditions);
	}

	function outerJoin($table, $conditions) {
		return $this->join('OUTER', $table, $conditions);
	}

	function join($type, $table, $conditions) {
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
		if (array_key_exists($table, $this->joins[$type])) {
			$this->joins[$type][$table] = array_merge($this->joins[$type][$table], $conditions);
		} else {
			$this->joins[$type][$table] = $conditions;
		}
		return $this;
	}
}

