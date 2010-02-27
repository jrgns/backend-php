<?php
/**
 * Class file for TagLinkObj
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
 */

/**
 * DBObject wrapper for the `tag_links` table
 * @package Models
 */
class TagLinkObj extends DBObject {
	function __construct($meta = array()) {
		if (!is_array($meta) && (is_numeric($meta) || is_string($meta))) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'tag_links';
		$meta['name'] = 'TagLink';
		$meta['fields'] = array(
			'id' => 'primarykey',
			'tag_id' => 'foreignkey',
			'foreign_id' => 'foreignkey',
			'added' => 'lastmodified',
		);
		$meta['keys'] = array(
			'tag_id,foreign_id' => 'unique',
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

