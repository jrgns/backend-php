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
	public static function add($number, $string, $file, $line, $context) {
		$bt = array_reverse(debug_backtrace());
		//Remove the call to BackendError::add :)
		array_pop($bt);

		$data = array(
			'mode'       => Controller::$view->mode,
			'number'     => $number,
			'string'     => $string,
			'file'       => $file,
			'line'       => $line,
			'context'    => $context,
			'stacktrace' => var_export($bt, true),
		);
		$BE = new BackendErrorObj();
		return $BE->create($data);
	}
}
