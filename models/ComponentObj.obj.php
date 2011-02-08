<?php
/**
 * The class file for Component
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
 * This is the model definition for components
 */
class ComponentObj extends DBObject {
	function __construct($meta = array(), array $options = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'components';
		$meta['name'] = 'Component';
		$meta['fields'] = array(
			'id'       => 'primarykey',
			'name'     => 'string',
			//TODO: base should say where the file / component resides: Core / Backend / Application
			//'base'     => array('type' => 'string', 'string_size' => 1024),
			'filename' => array('type' => 'string', 'string_size' => 1024),
			'options'  => 'text',
			'active'   => 'boolean',
			'modified' => 'lastmodified',
			'added'    => 'dateadded',
		);
		return parent::__construct($meta, $options);
	}
	
	function validate($data, $action, $options = array()) {
		$toret = true;
		$data = parent::validate($data, $action, $options);
		return $toret ? $data : false;
	}
}

