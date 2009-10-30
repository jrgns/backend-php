<?php
/**
 * Class file for BeFilter
 *
 * Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * @license http://www.eclipse.org/legal/epl-v10.html
 * @package Models
 *
 * Contributors:
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 *
 * Generated with class_gen on 2009-07-02 20:26:27 
 */

/**
 * DBObject wrapper for the `be_filters` table
 * @package Models
 */
class BEFilterObj extends DBObject {
	function __construct($meta = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'b_e_filters';
		$meta['name'] = 'BEFilter';
		$meta['fields'] = array(
			'id' => 'primarykey',
			'name' => 'string',
			'description' => 'text',
			'class' => 'string',
			'function' => 'string',
			'options' => 'string',
			'active' => 'boolean',
			'modified' => 'lastmodified',
			'added' => 'dateadded',
		);
		return parent::__construct($meta);
	}

	function validate($data, $action, $options = array()) {
		$toret = true;
		$data = parent::validate($data, $action, $options);
		if ($data) {
			$data['active'] = array_key_exists('active', $data) ? $data['active'] : 1;
		}
		return $toret ? $data : false;
	}

	public function getInstallSQL() {
		$toret = <<< END_SQL
CREATE TABLE `b_e_filters` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `class` varchar(255) CHARACTER SET ascii NOT NULL,
  `function` varchar(255) CHARACTER SET ascii NOT NULL,
  `options` varchar(1024) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UniqueFilters` (`class`,`function`)
)
END_SQL;
		return $toret;
	}
}

