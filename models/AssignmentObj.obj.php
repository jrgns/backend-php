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
class AssignmentObj extends DBObject {
	function __construct($meta = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'assignments';
		$meta['name'] = 'Assignment';
		$meta['fields'] = array(
			'id' => 'primarykey',
			'access_type' => 'string',
			'access_id' => 'integer',
			'role_id' => 'integer',
			'active' => 'boolean',
			'modified' => 'lastmodified',
			'added' => 'dateadded',
		);
		return parent::__construct($meta);
	}
	
	function validate($data, $action, $options = array()) {
		$toret = false;
		$data = parent::validate($data, $action, $options);
		if ($data) {
			$toret = true;
			if ($action == 'create') {
				$data['active'] = array_key_exists('active', $data) && !is_null($data['active'])  ? $data['active'] : 1;
			}
		}
		return $toret ? $data : false;
	}
	
	public function getInstallSQL() {
		$toret = <<< END_SQL
CREATE TABLE `assignments` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `access_type` varchar(30) NOT NULL,
  `access_id` bigint(20) NOT NULL,
  `role_id` bigint(20) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `added` datetime NOT NULL,
  PRIMARY KEY (`id`)
) COMMENT='Defines Roles for different accessors.'
END_SQL;
		return $toret;
	}
}
