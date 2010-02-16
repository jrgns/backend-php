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
class CommentObj extends DBObject {
	function __construct($meta = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'comments';
		$meta['name'] = 'Comment';
		$meta['fields'] = array(
			'id' => 'primarykey',
			'user_id' => 'current_user',
			'foreign_table' => 'string',
			'foreign_id' => 'foreignkey',
			'in_reply_to' => 'foreignkey',
			'title' => 'title',
			'content' => 'text',
			'active' => 'boolean',
			'modified' => 'lastmodified',
			'added' => 'dateadded',
		);
		$meta['relations'] = array(
			'Account' => array('conditions' => array('id' => 'user_id')),
		);

		$meta['parents'] = array();
		return parent::__construct($meta);
	}
	
	function validate($data, $action, $options = array()) {
		$toret = true;
		$data = parent::validate($data, $action, $options);
		if ($data) {
			if (preg_match(
				REGEX_LINKS,
				$data['content']
			)) {
				$data['active'] = empty($data['active']) ? 0 : $data['active'];
			} else {
				$data['active'] = empty($data['active']) ? 1 : $data['active'];
			}
		}
		return $toret ? $data : false;
	}
}

