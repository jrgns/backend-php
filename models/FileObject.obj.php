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

	function __construct($meta = array(), array $options = array()) {
		$fields = $meta['fields'];
		$fields['mime_type'] = array_key_exists('mime_type', $fields) ? $fields['mime_type'] : 'string';
		$fields['from_db']   = array_key_exists('from_db', $fields) ? $fields['from_db'] : 'boolean';
		$fields['content']   = array_key_exists('content', $fields) ? $fields['content'] : 'long_blob';
		$fields['meta_info'] = array_key_exists('meta_info', $fields) ? $fields['meta_info'] : 'serialized';
		$meta['fields'] = $fields;
		return parent::__construct($meta, $options);
	}

	function getMetaInfo($filename) {
		return null;
	}

	function getMimeType() {
		if (!empty($this->data['mime_type'])) {
			return $this->data['mime_type'];
		}
		return $this->default_type;
	}

	function fromRequest() {
		$data = parent::fromRequest();
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
				} else {
					$folder = self::checkUploadFolder($this->meta['table']);
					if ($folder) {
						//We add the timestamp to ensure that the name is unique
						$destination = $folder . time() . '.' . basename($data['content']['name']);
						if (move_uploaded_file($data['content']['tmp_name'], $destination)) {
							$data['content'] = $destination;
						} else {
							Backend::addError('Could not upload file');
							$data = false;
						}
					} else {
						$data = false;
					}
				}
			}
		}
		return $data;
	}

	private static function checkUploadFolder($sub_folder = false) {
		$folder = Backend::getConfig('backend.application.file_store', SITE_FOLDER . '/files/');
		if ($sub_folder) {
			if (substr($folder, -1) != '/') {
				$folder .= '/';
			}
			$folder .= $sub_folder;
		}
		if (substr($folder, -1) != '/') {
			$folder .= '/';
		}

		if (!file_exists($folder)) {
			if (!@mkdir($folder, 0775)) {
				Backend::addError('Cannot create File Store');
				$folder = false;
			}
		} else if (!is_writeable($folder)) {
			$folder = false;
		}
		return $folder;
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
