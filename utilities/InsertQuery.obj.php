<?php
/**
 * The class file for InsertQuery
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
 * Class to handle insert queries
 */
class InsertQuery extends Query {
	protected $data = array();

	function __construct($table, array $options = array()) {
		parent::__construct('INSERT', $table, $options);
	}

	protected function buildTable() {
		$query  = 'INSERT INTO ' . $this->table;
		$fields = array_map(array('Query', 'enclose'), array_keys($this->data));
		$this->parameters = array_merge($this->parameters, array_values($this->data));
		$query .= PHP_EOL . '(' . implode(', ', $fields) . ')';
		$query .= PHP_EOL . 'VALUES (' . implode(', ', array_fill(0, count($this->parameters), '?')) . ')';
		return $query;
	}

	public function data($name, $value = false) {
		if (!$value && is_array($name)) {
			foreach($name as $key => $value) {
				$this->data($key, $value);
			}
		} else {
			$this->data[$name] = $value;
		}
		return $this;
	}
}
