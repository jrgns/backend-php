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
class HookObj extends DBObject {
	function __construct($meta = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'hooks';
		$meta['name'] = 'Hook';
		$meta['fields'] = array(
			'id'          => 'primarykey',
			'name'        => 'title',
			'description' => 'text',
			'mode'        => 'string',
			'type'        => 'string',
			'hook'        => 'string',
			'class'       => 'string',
			'method'      => 'string',
			'sequence'    => 'integer',
			'active'      => 'boolean',
			'modified'    => 'lastmodified',
			'added'       => 'dateadded',
		);
		return parent::__construct($meta);
	}
	
	function validate($data, $action, $options = array()) {
		$toret = true;
		$data = parent::validate($data, $action, $options);
		if ($data) {
			$data['active'] = array_key_exists('active', $data) ? $data['active'] : 1;
		}
		return $toret ? $data : false;
	}
}
