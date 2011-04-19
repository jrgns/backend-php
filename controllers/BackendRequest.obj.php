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
	public static function hook_start() {
		$data = array(
			'mode' => Controller::$view instanceof View ? Controller::$view->mode : 'unknown',
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
		
		$toret = Hook::add('start', 'pre', __CLASS__, array('global' => true, 'sequence' => 1000)) && $toret;
		return $toret;
	}
	
	public function action_filter ($pageId = 1)
	{
		
		$query = new SelectQuery('BackendRequest');
		
		$query->setFields(array('user_id', 'ip', 'user_agent', 'mode', 'request', 'query', 'COUNT(id) AS `occured`', 'MAX(`added`) AS `last_occured`'));
		$query->setGroup(array('user_id', 'ip', 'user_agent', 'mode', 'request', 'query'));
		
		$params = $queryFilter = array();
		$parameters = Controller::getVar('params');
		$sort = Controller::getVar('sort');
		if (!empty($parameters['userId']))
		{
			$queryFilter[] = 'user_id = :userId';
			$params[':userId'] = $parameters['userId'];
		}
		if (!empty($parameters['query']))
		{
			$queryFilter[] = "query LIKE('%{$parameters['query']}%')";
		}
		if (!empty($parameters['ip']))
		{
			$queryFilter[] = "ip LIKE('%{$parameters['ip']}%')";
		}
		if (!empty($parameters['user_agent']))
		{
			$queryFilter[] = "user_agent LIKE('%{$parameters['user_agent']}%')";
		}
		$query->filter($queryFilter);
		
		$count = 10;
		
		if (!empty($sort['field']))
		{
			$query->setOrder(array($sort['field'] . '  ' . $sort['order']));
		}
		
		if ($pageId == 1)
		{
			$start = 0;
		} elseif ($pageId == 0)
		{
			$start = false;
			$count = false;
		} else
		{
			$start = floor(($pageId - 1) * $count);
		}
		
		
		$pager = array();
		
		if ($start === 'all') {
			$limit = 'all';
		} else if ($start || $count) {
			$limit = "$start, $count";
		} else {
			$limit = false;
		}
		
		$query->limit($limit);
		
		
		$items = $query->fetchAll($params);
		
		$totalItems = $query->getCount($params);
		
		$pager = '';
		
		if ($start || $count) 
		{
			$pager = array (
						'currentPage'	=> $pageId,
						'itemCount'		=> count($items),
						'itemTotal'		=> $totalItems,
						'totalPages'	=> round(($totalItems - 1) / $count, 0)
						);
		}
		
		$retArray['pager'] = $pager;
		$retArray['data'] = $items;
		$retArray['params'] = $parameters;
		$retArray['sort'] = $sort;
		

		return $retArray;
	}
	
	public function html_filter ($resultArray)
	{
		//backend_error.filter.tpl.php
		Backend::addContent(Render::renderFile('backend_request.filter.tpl.php', array(
																					'data' => $resultArray['data'], 
																					'params' => $resultArray['params'], 
																					'pager' => $resultArray['pager'],
																					'sort' => $resultArray['sort'],
																					)));
	}
}
