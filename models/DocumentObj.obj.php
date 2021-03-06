<?php
/**
 * Class file for Document
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
 * Generated with class_gen on 2009-07-02 20:40:57 
 */

/**
 * DBObject wrapper for the `documents` table
 * @package Models
 */
class DocumentObj extends FileObject {
	public $default_type = 'text/plain';

	function __construct($meta = array(), array $options = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'documents';
		$meta['name'] = 'Document';
		$meta['fields'] = array(
			'id' => 'primarykey',
			'name' => 'string',
			'active' => 'boolean',
			'modified' => 'lastmodified',
			'added' => 'dateadded',
		);
		return parent::__construct($meta, $options);
	}

	function getMetaInfo($filename) {
		$toret = array();
		if (is_array($filename)) {
			foreach ($filename as $file) {
				$tmp = self::getMetaInfo($file);
				$toret = array_merge($toret, array_filter($tmp));
			}
		} else {
			$parts = pathinfo($filename);
			$toret['extension'] = array_key_exists('extension', $parts) ? $parts['extension'] : null;
		}
		return $toret;
	}
}

