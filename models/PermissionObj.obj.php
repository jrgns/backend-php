<?php
/**
 * @author J Jurgens du Toit (JadeIT cc) <jurgens.dutoit@gmail.com> - initial API and implementation
 * @copyright Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available {@link http://www.eclipse.org/legal/epl-v10.html here}
 * @license http://www.eclipse.org/legal/epl-v10.html Eclipse Public License v1.0
 * @package Models
 *
 * Contributors:
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 */
class PermissionObj extends DBObject {
	function __construct($meta = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'permissions';
		$meta['name'] = 'Permission';
		$meta['fields'] = array(
			'id' => 'primarykey',
			'role' => array('type' => 'string', 'string_size' => 60),
			'control' => 'integer',
			'action' => array('type' => 'string', 'string_size' => 60),
			'subject' => array('type' => 'string', 'string_size' => 60),
			'subject_id' => 'integer',
			'system' => 'integer',
			'active' => 'boolean',
			'modified' => 'lastmodified',
			'added' => 'dateadded',
		);
		$meta['keys'] = array(
			'role,action,subject,subject_id' => 'unique',
			'subject,action,subject_id'      => 'index',
		);
		return parent::__construct($meta);
	}
	
	function validate($data, $action, $options = array()) {
		$toret = false;
		$data = parent::validate($data, $action, $options);
		if ($data) {
			$toret = true;
			if ($action == 'create') {
				$data['active'] = array_key_exists('active', $data) && !is_null($data['active'])  ? $data['active'] : 1;
			}
		}
		return $toret ? $data : false;
	}
}
