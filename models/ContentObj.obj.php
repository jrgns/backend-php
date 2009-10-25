<?php
/**
 * Class file for ContentObj
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
 * DBObject wrapper for the `contents` table
 * @package Models
 */
class ContentObj extends DBObject {
	function __construct($meta = array()) {
		require_once(BACKEND_FOLDER . '/libraries/Markdown/markdown.php');
		$this->load_mode = 'object';

		if (!is_array($meta) && (is_numeric($meta) || is_string($meta))) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'contents';
		$meta['name'] = 'Content';
		$meta['fields'] = array(
			'id' => 'primarykey',
			'name' => 'string',
			'title' => 'title',
			'markdown' => 'text',
			'body' => 'text',
			'from_file' => 'boolean',
			'active' => 'boolean',
			'modified' => 'lastmodified',
			'added' => 'dateadded',
		);
		$meta['children'] = array();
		$meta['parents'] = array();
		//@jrgns 2009-10-25: Don't know if this is the right place to add these. Shouldn't this component be unaware of other components impacting it?
		if (Component::isActive('Comment')) {
			$meta['children']['Comment'] = array('conditions' => array());
		}
		if (Component::isActive('Tag')) {
			/*
			 * Conditions should be: array(parent_field => should be what)
			 */
			$meta['parents']['Tag'] = array('conditions' => array('id' => array('IN' => 'tags')));
		}
		return parent::__construct($meta);
	}

	function validate($data, $action, $options = array()) {
		$toret = false;
		$data = parent::validate($data, $action, $options);
		if ($data) {
			if (!empty($data['body']) || !empty($data['markdown'])) {
				$data['from_file'] = false;
			}
			$data['body'] = Markdown($data['markdown']);
			$toret = true;
		}
		return $toret ? $data : false;
	}
}

