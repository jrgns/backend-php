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
class PermissionObj extends DBObject {
	function __construct($meta = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'permissions';
		$meta['name'] = 'Permission';
		$meta['fields'] = array(
			'id' => 'primarykey',
			'role' => 'string',
			'control' => 'integer',
			'action' => 'string',
			'subject' => 'string',
			'subject_id' => 'string',
			'system' => 'integer',
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
CREATE TABLE `permissions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `role` varchar(60) NOT NULL,
  `control` tinyint(3) NOT NULL,
  `action` varchar(60) NOT NULL,
  `subject` varchar(60) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `system` smallint(5) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `role_type` (`role`,`action`,`subject`,`subject_id`),
  KEY `subaction` (`subject`,`action`,`subject_id`)
)
END_SQL;
		return $toret;
	}
}
