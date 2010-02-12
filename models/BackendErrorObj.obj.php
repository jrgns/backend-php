<?php
/**
 * The class file for BackendErrorObj
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
 * This is the model definition for backend_errors
 */
class BackendErrorObj extends DBObject {
	function __construct($meta = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'backend_errors';
		$meta['name'] = 'BackendErrors';
		$meta['fields'] = array(
			'id'         => 'primarykey',
			'user_id'    => 'current_user',
			'mode'       => array('type' => 'string', 'string_size' => 255),
			'request'    => 'current_request',
			'query'      => 'current_query',
			'number'     => 'number',
			'string'     => 'large_string',
			'file'       => 'string',
			'line'       => 'number',
			'context'    => 'string',
			'stacktrace' => 'text',
			'added'      => 'lastmodified',
		);
		return parent::__construct($meta);
	}
	
	function validate($data, $action, $options = array()) {
		$toret = true;
		$data = parent::validate($data, $action, $options);
		return $toret ? $data : false;
	}
}
