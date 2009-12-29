<?php
/**
 * The class file for ValueObj
 *
 * @author J Jurgens du Toit (JadeIT cc) <jurgens.dutoit@gmail.com> - initial API and implementation
 * @copyright Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available {@link http://www.eclipse.org/legal/epl-v10.html here}
 * @license http://www.eclipse.org/legal/epl-v10.html Eclipse Public License v1.0
 * @package Models
 *
 * Generated with class_gen on 2009-08-07 09:55:19 
 */

/**
 * DBObject wrapper for the `values` table
 * @package Models
 */
class ValueObj extends DBObject {
	function __construct($meta = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'values';
		$meta['name'] = 'Value';
		$meta['fields'] = array(
			'id' => 'primarykey',
			'name' => 'string',
			'value' => 'text',
			'modified' => 'lastmodified',
		);
		$meta['keys'] = array(
			'name' => 'unique',
		);
		return parent::__construct($meta);
	}

	function validate($data, $action, $options = array()) {
		$toret = false;
		$data = parent::validate($data, $action, $options);
		if ($data) {
			$toret = true;
		}
		return $toret ? $data : false;
	}

	public function getRetrieveSQL() {
		extract($this->meta);
		$database = Backend::get('DB_' . $database, $database);
		return 'SELECT * FROM `' . $database . '`.`' . $table . '` WHERE `id` = :parameter OR `name` = :parameter';
	}
}

