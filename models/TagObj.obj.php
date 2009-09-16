<?php
/**
 * Class file for TagObj
 *
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
 *
 * Generated with class_gen on 2009-05-10 20:59:26 
 */

/**
 * DBObject wrapper for the `tags` table
 * @package Models
 */
class TagObj extends DBObject {
	function __construct($meta = array()) {
		if (!is_array($meta) && (is_numeric($meta) || is_string($meta))) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'tags';
		$meta['name'] = 'Tag';
		$meta['fields'] = array(
			'id' => 'primarykey',
			'parent_id' => 'foreignkey',
			'name' => 'title',
			'description' => 'text',
			'active' => 'boolean',
			'weight' => 'integer',
			'modified' => 'lastmodified',
			'added' => 'dateadded',
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
}

