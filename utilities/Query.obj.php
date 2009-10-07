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
	var $connection;
	var $query = false;
	
	function __construct($action, $table, array $options = array()) {
	}

	private function checkConnection() {
		if (!$this->connection instanceof PDO) {
			$this->connection = Backend::getDB();
		}
		return ($this->connection instanceof PDO);
	}
	
	public function execute(array $parameters = array()) {
		$toret = false;
		if ($this->checkConnection() && !empty($this->query)) {
			$stmt = $this->connection->prepare($this->query);
			if ($stmt) {
				if ($stmt->execute($parameters)) {
					$toret = $stmt;
				} else {
					if (Controller::$debug) {
						echo $this->query;
						var_dump($stmt->errorInfo());
					}
					Controller::addError('Could not execute statement');
				}
			} else {
				Controller::addError('Could not prepare statement');
			}
		} else {
			Controller::addError('Could not execute query');
		}
		return $toret;
	}
	
	public function fetchAssoc(array $parameters = array()) {
		$toret = $this->execute($parameters);
		if ($toret) {
			$toret = $toret->fetch(PDO::FETCH_ASSOC);
		}
		return $toret;
	}
	
	public function fetchAll(array $parameters = array()) {
		$toret = $this->execute($parameters);
		if ($toret) {
			$toret = $toret->fetchAll(PDO::FETCH_ASSOC);
		}
		return $toret;
	}
	
	public function fetchColumn(array $parameters = null, int $column = null) {
		$parameters = is_null($parameters) ? array() : $parameters;
		$column = is_null($column) ? 0 : $column;
		$toret = $this->execute($parameters);
		if ($toret) {
			$toret = $toret->fetchColumn($column);
		}
		return $toret;
	}

	public function setQuery($query) {
		$this->query = $query;
	}
}
