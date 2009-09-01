<?php
/**
 * The file that defines the View class.
 *
 * @author J Jurgens du Toit (JadeIT cc) <jurgens.dutoit@gmail.com> - initial API and implementation
 * @copyright Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available {@link http://www.eclipse.org/legal/epl-v10.html here}
 * @license http://www.eclipse.org/legal/epl-v10.html Eclipse Public License v1.0
 * @package Core
 */
 
/**
 * Default class to handle View specific functions
 */
class View {
	public $mode = false;
	
	function __construct() {
		$this->mode = Backend::getConfig('application.default.type', 'view');
	}

	public static function hook_init() {
		ob_start();
	}
	
	public static function hook_output($output) {
		$last = ob_get_clean();
		$start = Backend::get('start');
		$time = microtime(true) - $start;
		$last = 'Generated on ' . date('Y-m-d H:i:s') . ' in ' . number_format($time, 4) . ' seconds' . $last;
		$output = str_replace('#Last Content#', $last, $output);
		return $output;
	}

	/**
	 * Render the data into the correct format / as information
	 *
	 * This function takes data, and translates it into information.
	 */
	function display($data, $controller) {
		$data = Hook::run('display', 'pre', array($data, $controller), array('toret' => $data));
		
		$display_method = $this->mode . '_' . Controller::$action;
		if (method_exists($controller, $display_method)) {
			if (!is_null($data) && $controller->checkPermissions()) {
				$data = $controller->$display_method($data);
			}
		}
		
		$data = Hook::run('display', 'post', array($data, $controller), array('toret' => $data));

		$this->output($data);
	}

	/**
	 * Actually output the information
	 */
	function output($to_print = null) {
		$to_print = Hook::run('output', 'pre', array($to_print), array('toret' => $to_print));

		if (empty($to_print)) {
			$default_view = Backend::getConfig('backend.default.view');
			if ($default_view && class_exists($default_view, true) && method_exists($default_view, 'hook_output')) {
				$to_print = call_user_func_array(array($default_view, 'hook_output'), array($to_print));
			}
		}
		echo $to_print;
		
		$to_print = Hook::run('output', 'post', array($to_print), array('toret' => $to_print));
	}	
}
