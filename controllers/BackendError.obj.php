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
class BackendError extends TableCtl {
	public static function add($one, $two = false, $three = false, $four = false, $five = false) {
		if (!is_numeric($one) && !$three && !$four && !$five) {
			self::addBE($one, $two);
		} else {
			self::addPHP($one, $two, $three, $four, $five);
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
		self::addPHP(0, $string, basename($info['file']), $info['line'], $context);
	}
}
