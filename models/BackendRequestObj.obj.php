<?php
/**
 * The class file for BackendRequest
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
 * This is the model definition for backend_requests
 */
class BackendRequestObj extends DBObject {
	function __construct($meta = array(), array $options = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'backend_requests';
		$meta['name'] = 'BackendRequest';
		$meta['fields'] = array(
			'id' => 'primarykey',
			'user_id' => 'current_user',
			'ip'     => 'ip_address',
			'user_agent' => 'user_agent',
			'mode' => array('type' => 'string', 'string_size' => 255),
			'request' => 'current_request',
			'query' => 'current_query',
			'added' => 'lastmodified',
		);
		return parent::__construct($meta, $options);
	}
	
	/*
	 * TODO Untested uncompleted code. Use with caution.
	public function create($data, array $options = array()) {
		//Do this to prevent requests from haning on a read lock
		if ($this->db instanceof PDO) {
			$orig_timeout = $this->db->getAttribute(PDO::ATTR_TIMEOUT);
			if ($orig_timeout > 5) {
				$this->db->setAttribute(PDO::ATTR_TIMEOUT, 5);
			} else {
				$orig_timeout = false;
			}
		}
		return false;
		
		$result = parent::create($data, $options);
		if (!empty($orig_timeout)) {
			$this->db->setAttribute(PDO::ATTR_TIMEOUT, $orig_timeout);
		}
		return $result;
	}
	*/
	
	function validate($data, $action, $options = array()) {
		$toret = true;
		$data = parent::validate($data, $action, $options);
		return $toret ? $data : false;
	}
}
