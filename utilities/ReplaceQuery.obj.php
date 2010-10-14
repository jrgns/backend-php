<?php
/**
 * The class file for ReplaceQuery
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
 * Class to handle replace queries
 */
class ReplaceQuery extends Query {
	protected $data = array();

	function __construct($table, array $options = array()) {
		parent::__construct('REPLACE', $table, $options);
	}

	protected function buildTable() {
		$query  = 'REPLACE INTO ' . $this->table . PHP_EOL;
		$fields = array_map(array('Query', 'enclose'), array_keys($this->data));
		$this->parameters = array_merge($this->parameters, array_values($this->data));
		if (count($fields)) {
			$query .= PHP_EOL . '(' . implode(', ', $fields) . ')';
		}
		if (count($this->parameters)) {
			$query .= PHP_EOL . 'VALUES (' . implode(', ', array_fill(0, count($this->parameters), '?')) . ')';
		}
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
