<?php
/**
 * Class file for Image
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
class ImageObj extends FileObject {
	public $default_type = 'image/jpeg';

	function __construct($meta = array(), array $options = array()) {
		if (!is_array($meta)) {
			if (is_numeric($meta)) {
				$meta = array('id' => $meta);
			} else {
				$meta = array();
			}
		}
		$meta['table'] = 'images';
		$meta['name'] = 'Image';
		$meta['fields'] = array(
			'id' => 'primarykey',
			'name' => 'string',
			'title' => 'string',
			'active' => 'boolean',
			'modified' => 'lastmodified',
			'added' => 'dateadded',
		);
		return parent::__construct($meta, $options);
	}

	function fromPost() {
		$data = parent::fromPost();
		if (is_post() && array_key_exists('mime_type', $data)) {
			if (!empty($data['meta_info']['mime']) && $data['mime_type'] != $data['meta_info']['mime']) {
				$data['mime_type'] = $data['meta_info']['mime'];
			}
		}
		return $data;
	}

	function getMimeType() {
		if (!empty($this->data['meta_info']['mime'])) {
			return $this->data['meta_info']['mime'];
		}
		return parent::getMimeType();
	}

	function getMetaInfo($filename) {
		$toret = array();
		if (is_array($filename)) {
			foreach ($filename as $file) {
				$toret += self::getMetaInfo($file);
			}
		} else if (file_exists($filename)) {
			$size = getimagesize($filename, $image_info);
			if ($size) {
				$toret['width']     = $size[0];
				$toret['height']    = $size[1];
				$toret['imagetype'] = $size[2];
				if (count($size) > 4) {
					$toret += array_slice($size, 4);
				}
			}
			if (count($image_info)) {
				$toret += $image_info;
			}
		}
		return $toret;
	}
}

