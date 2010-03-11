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
 * Class to handle insert queries
 */
class InsertQuery extends Query {
	protected $data    = array();

	function __construct($table, array $options = array()) {
		Controller::$debug = true;
		parent::__construct('INSERT', $table, $options);
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
