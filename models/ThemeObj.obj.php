<?php
/**
 * The class file for Theme
 */
/**
 * Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * @license http://www.eclipse.org/legal/epl-v10.html
 * @package Controllers
 *
 * Contributors:
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 */

/**
 * This is the model definition for themes
 */
class ThemeObj extends DBObject {
	function __construct($meta = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'themes';
		$meta['name'] = 'Theme';
		$meta['fields'] = array(
			'id' => 'primarykey',
			'name' => 'string',
			'description' => 'text',
			'screenshot' => 'string (default)',
			'path' => 'string',
			'active' => 'boolean',
			'modified' => 'lastmodified',
			'added' => 'dateadded',
		);
		return parent::__construct($meta);
	}
	
	function validate($data, $action, $options = array()) {
		$toret = true;
		$data = parent::validate($data, $action, $options);
		return $toret ? $data : false;
	}

	public function getRetrieveSQL() {
		extract($this->meta);
		$database = Backend::get('DB_' . $database, $database);
		return 'SELECT * FROM `' . $database . '`.`' . $table . '` WHERE `id` = :parameter OR `name` = :parameter';
	}

	public function getInstallSQL() {
		$toret = <<< END_SQL
CREATE TABLE `themes` (
 `id` bigint(20) NOT NULL AUTO_INCREMENT,
 `name` varchar(255) NOT NULL,
 `description` text NOT NULL,
 `screenshot` blob,
 `path` varchar(1024) NOT NULL,
 `active` tinyint(1) NOT NULL,
 `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 `added` datetime NOT NULL,
 PRIMARY KEY (`id`)
)
END_SQL;
		return $toret;
	}
}

