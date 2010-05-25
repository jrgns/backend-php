<?php
/**
 * The class file for BackendQuery
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
 * This is the model definition for backend_queries
 */
class BackendQueryObj extends DBObject {
	function __construct($meta = array(), array $options = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'backend_queries';
		$meta['name'] = 'BackendQuery';
		$meta['fields'] = array(
			'id' => 'primarykey',
			'alias' => array('type' => 'string', 'string_size' => 255),
			'query' => array('type' => 'string', 'string_size' => 1024),
			'active' => 'boolean',
			'modified' => 'lastmodified',
			'added' => 'dateadded',
		);
		$meta['keys'] = array(
			'alias' => 'unique',
		);
		return parent::__construct($meta, $options);
	}
	
	function validate($data, $action, $options = array()) {
		$toret = true;
		$data = parent::validate($data, $action, $options);
		return $toret ? $data : false;
	}

	public function getRetrieveSQL() {
		$query = new SelectQuery(__CLASS__);
		$query->filter('BINARY `id` = :parameter OR `alias` = :parameter');
		return $query;
	}
}

