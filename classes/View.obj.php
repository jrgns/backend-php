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
	public $mode       = false;
	public $mime_type  = false;
	public $charset    = false;
	
	function __construct() {
		$this->mode = Backend::getConfig('application.default.type', 'view');
		if (!headers_sent()) {
			header('X-Backend-View: ' . get_class($this));
		}
	}

	/**
	 * Render the data into the correct format / as information
	 *
	 * This function takes data, and translates it into information.
	 */
	function display($data, $controller) {
		$data = Hook::run('display', 'pre', array($data, $controller), array('toret' => $data));
		if (method_exists($this, 'hook_display')) {
			$data = $this->hook_display($data, $controller);
		}
		
		$display_method = $this->mode . '_' . Controller::$action;
		if (method_exists($controller, $display_method)) {
			if ($controller->checkPermissions()) {
				$data = $controller->$display_method($data);
			}
		}
		
		$data = Hook::run('display', 'post', array($data, $controller), array('toret' => $data));
		if (method_exists($this, 'hook_post_display')) {
			$data = $this->hook_post_display($data, $controller);
		}

		$this->output($data);
	}

	/**
	 * Actually output the information
	 */
	function output($to_print = null) {
		if (!headers_sent()) {
			$content_type = $this->mime_type;
			if ($this->charset) {
				$content_type .= '; charset=' . $this->charset;
			}
			header('Content-Type: ' . $content_type);
		}
		$to_print = Hook::run('output', 'pre', array($to_print), array('toret' => $to_print));
		if (method_exists($this, 'hook_output')) {
			$to_print = $this->hook_output($to_print);
		}

		echo $to_print;
		
		$to_print = Hook::run('output', 'post', array($to_print), array('toret' => $to_print));
		if (method_exists($this, 'hook_post_output')) {
			$to_print = $this->hook_post_output($to_print);
		}
	}	
}
