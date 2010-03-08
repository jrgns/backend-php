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

class ContentRevisionObj extends DBObject {
	function __construct($meta = array(), array $options = array()) {
		require_once(BACKEND_FOLDER . '/libraries/Markdown/markdown.php');
		if (!is_array($meta) && (is_numeric($meta) || is_string($meta))) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'content_revisions';
		$meta['name'] = 'Content Revision';
		$meta['fields'] = array(
			'id' => 'primarykey',
			'user_id' => 'integer',
			'content_id' => 'integer',
			'summary' => 'title',
			'markdown' => 'text',
			'body' => 'text',
			'active' => 'boolean',
			'modified' => 'lastmodified',
			'added' => 'dateadded',
		);
		return parent::__construct($meta, $options);
	}

	function validate($data, $action, $options = array()) {
		$toret = false;
		$data = parent::validate($data, $action, $options);
		if ($data) {
			if (empty($data['user_id']) && !empty($_SESSION['user']->id)) {
				$data['user_id'] = $_SESSION['user']->id;
			}
			if (!isset($data['active'])) {
				$data['active'] = 1;
			}
			$data['body'] = Markdown($data['markdown']);
			$toret = true;
		}
		return $toret ? $data : false;
	}
}

