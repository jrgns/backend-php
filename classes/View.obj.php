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

		if ($controller instanceof AreaCtl && $controller->checkPermissions()) {
			$display_method = $this->mode . '_' . Controller::$action;
			$view_method    = 'output_' . Controller::$action;
			$mode_method    = $this->mode;

			//Controller->view
			if (method_exists($controller, $mode_method)) {
				if (Controller::$debug) {
					Backend::addNotice('Running ' . get_class($controller) . '::' . $mode_method);
				}
				$data = $controller->$mode_method($data);
			}
			//Application->view
			$app_class = Backend::getConfig('backend.application.class', 'Application');
			if (is_callable(array($app_class, $mode_method))) {
				if (Controller::$debug) {
					Backend::addNotice('Running ' . $app_class . '::' . $mode_method);
				}
				$data = call_user_func(array($app_class, $mode_method), $data);
			}
			
			if (Controller::$debug) {
				Backend::addNotice('Checking ' . get_class($controller) . '::' . $display_method . ' and then ' . get_class($this) . '::' . $view_method);
			}
			if (method_exists($controller, $display_method)) {
				if (Controller::$debug) {
					Backend::addNotice('Running ' . get_class($controller) . '::' . $display_method);
				}
				$data = $controller->$display_method($data);
			} else if (method_exists($this, $view_method)) {
				if (Controller::$debug) {
					Backend::addNotice('Running ' . get_class($controller) . '::' . $view_method);
				}
				$data = $this->$view_method($data);
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
		if (Controller::$mode == Controller::MODE_REQUEST) {
			if (!headers_sent() && !Controller::$debug) {
				$content_type = $this->mime_type;
				if ($this->charset) {
					$content_type .= '; charset=' . $this->charset;
				}
				header('Content-Type: ' . $content_type);
			}
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
	
	public function whoops($title, $message, $code_hint = false) {
		$version = '1.1';
		$header  = $message;
		switch ($title) {
		case 'Permission Denied':
			$code = 401;
			break;
		case 'Invalid Object Returned':
			$code = 500;
			break;
		case 'Whoops!':
		default:
			$code = $code_hint;
			break;
		}
		if ($code) {
			header('HTTP/' . $version . ' ' . $code . ' ' . $header, true, $code);
			if ($code == 406) {
				echo "<html><head><title>$title</title></head></body><h2>$title ($code)</h2><p>$message</p></body></html>";
			}
		}
	}
}
