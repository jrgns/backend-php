<?php
/**
 * The class file for BackendRequest
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
 * This is the controller for the table backend_requests.
 */
class BackendRequest extends TableCtl {
	public static function hook_post_init() {
		$data = array(
			'mode' => Controller::$view->mode,
		);
		$BR = new BackendRequestObj();
		return $BR->create($data);
	}
	
	public static function userLastSeen($user_id) {
		$query = new SelectQuery('BackendRequest');
		$query
			->field('MAX(`added`) AS `last_visit`')
			->filter('`user_id` = :user_id')
			->group('`user_id`');
		return $query->fetchColumn(array(':user_id' => $user_id));
	}
	
	public static function userVisits($user_id) {
		$query = new SelectQuery('BackendRequest');
		$query
			->field('COUNT(*) AS `visits`')
			->filter('`user_id` = :user_id')
			->group('`user_id`');
		return $query->fetchColumn(array(':user_id' => $user_id));
	}

	public static function install(array $options = array()) {
		$toret = parent::install($options);
		
		$toret = Hook::add('init', 'post', __CLASS__, array('global' => true)) && $toret;
		return $toret;
	}
}
