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
	function __construct($meta = array(), array $options = array()) {
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
			'body' => array('type' => 'text', 'markdown' => true),
			'from_file' => 'boolean',
			'active' => 'boolean',
			'modified' => 'lastmodified',
			'added' => 'dateadded',
		);
		$meta['relations'] = array();
		return parent::__construct($meta, $options);
	}

	public function getSelectSQL($options = array()) {
		if (empty($options['order'])) {
			$options['order'] = '`added` DESC';
		}
		if (!Permission::check('manage', 'content')) {
			//$user = BackendAccount::checkUser();
			//$filter = $user ? '`active` = 1 OR `user_id` = :user_id' : '`active` = 1';
			$filter = '`active` = 1';
			if (array_key_exists('filter', $options)) {
				$options['filter'] = is_array($options['filter']) ? array_push($options['filter'], $filter) : array($options['filter'], $filter);
			}
		}
		return parent::getSelectSQL($options);
	}

	public function getRetrieveSQL() {
		$query = new SelectQuery(__CLASS__);
		$query->filter('BINARY `id` = :parameter OR `name` = :parameter');
		return $query;
	}

	function validate($data, $action, $options = array()) {
		$toret = false;
		$data = parent::validate($data, $action, $options);
		if ($data) {
			if (!empty($data['body']) || !empty($data['markdown'])) {
				$data['from_file'] = false;
			}
			if (!empty($data['markdown'])) {
				$data['body'] = Markdown($data['markdown']);
			}
			$toret = true;
		}
		return $toret ? $data : false;
	}
}

