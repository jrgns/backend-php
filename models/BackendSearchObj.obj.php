<?php
/**
 * The class file for BackendSearch
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
 * This is the model definition for backend_search
 */
class BackendSearchObj extends DBObject {
	function __construct($meta = array(), array $options = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'backend_search';
		$meta['name'] = 'BackendSearch';
		$meta['fields'] = array(
			'id' => 'primarykey',
			'table' => array('type' => 'string', 'string_size' => 255),
			'table_id' => 'number',
			'word' => array('type' => 'string', 'string_size' => 255),
			'count' => 'number',
			'sequence' => 'number',
			'added' => 'lastmodified',
		);
		$meta['keys'] = array(
			'table,table_id,word' => 'unique',
		);
		return parent::__construct($meta, $options);
	}
	
	function validate($data, $action, $options = array()) {
		$toret = true;
		$data = parent::validate($data, $action, $options);
		return $toret ? $data : false;
	}
}
	
