<?php
/**
 * The class file for UpdateQuery
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
 * Class to handle update queries
 */
class UpdateQuery extends Query {
	protected $data = array();

	function __construct($table, array $options = array()) {
		Controller::$debug = true;
		parent::__construct('UPDATE', $table, $options);
	}

	protected function buildTable() {
		$query  = 'UPDATE ' . $this->table . ' SET' . PHP_EOL;
		$fields = array();
		foreach($this->data as $field => $value) {
			$fields[] = Query::enclose($field) . ' = ' . $value;
		}
		$query .= implode(', ' . PHP_EOL, $fields);
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

	public function execute(array $parameters = array(), array $options = array()) {
		if ($stmt = parent::execute($parameters, $options)) {
			return $stmt->rowCount();
		}
		return $stmt;
	}
}
