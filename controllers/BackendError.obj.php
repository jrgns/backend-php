<?php
/**
 * The class file for BackendError
 *
 * @copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * @license http://www.eclipse.org/legal/epl-v10.html
 * @package ControllerFiles
 * Contributors:
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 */

/**
 * This is the controller for the table `backend_errors`.
 *
 * @package Controllers
 */
class BackendError extends TableCtl {
	private static $adding = false;
	/**
	 * Add an error.
	 *
	 * This function is used by both builtin PHP functions and Coders.
	 * Only the first two parameters is needed if adding an error through code.
	 * @param string Error
	 * @param string Description
	 */
	public static function add($one, $two = false, $three = false, $four = false, $five = false) {
		//Some low level utilities only checks if the class exists, not if it is active
		if (Component::isActive('BackendError')) {
			if (!self::$adding) {
				self::$adding = true;
				if (!is_numeric($one) && !$three && !$four && !$five) {
					self::addBE($one, $two);
				} else {
					self::addPHP($one, $two, $three, $four, $five);
				}
				self::$adding = false;
			} else {
				var_dump($one, $two, $three, $four, $five);
				print_stacktrace();
				die('Recursive Backend Errors');
			}
		}
	}
	
	public static function addPHP($number, $string, $file, $line, $context) {
		$bt = array_reverse(debug_backtrace());
		//Remove the call to BackendError::add :)
		array_pop($bt);

		$data = array(
			'mode'       => is_object(Controller::$view) ? Controller::$view->mode : 'uninitialized',
			'number'     => $number,
			'string'     => $string,
			'file'       => $file,
			'line'       => $line,
			'context'    => is_string($context) ? $context : var_export($context, true),
			'stacktrace' => var_export($bt, true),
		);
		$BE = new BackendErrorObj();
		return $BE->create($data, array('load' => false));
	}
	
	public static function addBE($string, $context = false) {
		$bt = array_reverse(debug_backtrace());
		array_pop($bt);
		$bt      = array_reverse($bt);
		$info    = reset($bt);
		if (!$context) {
			$context = next($bt);
			$context = var_export($context['args'], true);
		}
		$file = array_key_exists('file', $info) ? basename($info['file']) : 'unknown';
		$line = array_key_exists('line', $info) ? $info['line'] : 0;
		self::addPHP(0, $string, basename($file), $line, $context);
	}
	
	public function action_filter ($pageId = 1)
	{
		
		$query = new SelectQuery('BackendError');
		
		$query->setFields(array('string', 'request', 'number', 'file', 'mode', 'query', 'user_id', 'COUNT(id) AS `occured`', 'MAX(`added`) AS `last_occured`'));
		$query->setGroup(array('string', 'request', 'number', 'file', 'mode', 'query', 'user_id'));
		
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
		if (!empty($parameters['number']))
		{
			$queryFilter[] = 'number = :number';
			$params[':number'] = $parameters['number'];
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
		Backend::addContent(Render::renderFile('backend_error.filter.tpl.php', array(
																					'data' => $resultArray['data'], 
																					'params' => $resultArray['params'], 
																					'pager' => $resultArray['pager'],
																					'sort' => $resultArray['sort'],
																					)));
	}
}
