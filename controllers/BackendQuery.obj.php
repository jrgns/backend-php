<?php
/**
 * The class file for BackendQuery
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
 * This is the controller for the table backend_queries.
 */
class BackendQuery extends TableCtl {
	public static function hook_init() {
		if (empty($_REQUEST['q'])) {
			return;
		}
		$query = $_REQUEST['q'];
		if (substr($query, -1) == '/') {
			$query = substr($query, 0, strlen($query) - 1);
		}
		$query = BackendQuery::retrieve($query);
		if ($query) {
			$_REQUEST['q'] = $query['query'];
		}
	}

	public static function install(array $options = array()) {
		$toret = parent::install($options);
		
		$toret = Hook::add('init', 'pre', __CLASS__, array('global' => true)) && $toret;

		return $toret;
	}
}

