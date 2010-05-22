<?php
/**
 * The class file for BackendFile
 */
/**
 * Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * @license http://www.eclipse.org/legal/epl-v10.html
 * @package Controllers
 *
 * Contributors:
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 */

/**
 * This is the model definition for backend_files
 */
class BackendFileObj extends DBObject {
	function __construct($meta = array(), array $options = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'backend_files';
		$meta['name'] = 'BackendFile';
		$meta['fields'] = array(
			'id' => 'primarykey',
			'name' => array('type' => 'string', 'string_size' => 255),
			'file' => array('type' => 'string', 'string_size' => 1024),
			'version' => array('type' => 'string', 'string_size' => 10),
			'dependencies' => 'text',
			'active' => 'boolean',
			'modified' => 'lastmodified',
			'added' => 'dateadded',
		);
		return parent::__construct($meta, $options);
	}
	
	function getRetrieveSQL() {
		$query = new SelectQuery(__CLASS__);
		$query->filter('`id` = :parameter OR `file` = :parameter');
		return $query;
	}
	
	function validate($data, $action, $options = array()) {
		$toret = true;
		$data = parent::validate($data, $action, $options);
		return $toret ? $data : false;
	}
}

