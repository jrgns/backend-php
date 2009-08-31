<?php
/**
 * Class file for Files represented on disk or DB
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
 * DBObject wrapper for the `images` table
 * @package Models
 */
class FileObject extends DBObject {
	public $default_type = 'text/plain';
	
	function __construct($meta = array()) {
		$fields = $meta['fields'];
		$fields['mime_type'] = array_key_exists('mime_type', $fields) ? $fields['mime_type'] : 'hidden';
		$fields['from_db']   = array_key_exists('from_db', $fields) ? $fields['from_db'] : 'boolean';
		$fields['content']   = array_key_exists('content', $fields) ? $fields['content'] : 'blob';
		$fields['meta_info'] = array_key_exists('meta_info', $fields) ? $fields['meta_info'] : 'serialized';
		$meta['fields'] = $fields;
		return parent::__construct($meta);
	}
	
	function getMetaInfo($filename) {
		return null;
	}

	function fromPost(array $data = array()) {
		$data = parent::fromPost($data);
		$fields = $this->meta['fields'];
		if (array_key_exists('mime_type', $fields) && array_key_exists('from_db', $fields) && array_key_exists('content', $fields)) {
			switch (true) {
			case !empty($data['mime_type']):
				break;
			case !empty($data['content']['type']):
				$data['mime_type'] = $data['content']['type'];
				break;
			}
			if (!empty($data['content']['tmp_name'])) {
				//Mime type first
				if (empty($data['mime_type'])) {
					$data['mime_type'] = File::getMimeType($data['content']['tmp_name'], $this->default_type);
				}
				$data['meta_info'] = $this->getMetaInfo(array($data['content']['tmp_name'], $data['content']['name']));
				//Overwrite content with file contents
				if ($data['from_db']) {
					$data['content'] = file_get_contents($data['content']['tmp_name']);
				}
			}
		}
		return $data;
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

