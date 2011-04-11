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
 * Default class to handle View specific functions.
 *
 * This only acts as a factory, only children of this class will be instansiated.
 */
class View {
	public $mode       = false;
	public $mime_type  = false;
	public $charset    = false;
	
	private static $instance  = false;
	
	function __construct() {
		trigger_error('Instansiated Factory Class. Use View::getInstance instead');
		return false;
	}
	
	/**
	 * The factory method. Decide on which view to use
	 *
	 * In an ideal world, we will just use the first mime type in the Http-Acccept header. But IE decided
	 * to put a lot of crud in it's Accept header, so we need some hacks.
	 *
	 * Mode takes precedence over the extension, which takes precedence over the accept headers/
	 *
	 * @todo the extension screws up requests such as ?q=content/display/2.txt, as the id is now 2.txt, and not 2 as expected.
	 * @todo Make the process on deciding a view better / extendable! Or, setup preferences that ignore the
	 * Accept header, or just rely on what the client asks for (mode=[json|xml|xhtml|jpg])
	 */
	public static function getInstance() {
		if (self::$instance instanceof View) {
			return self::$instance;
		}
		$view_name  = false;

		//Check the mode parameter
		$query_vars = Controller::getQueryVars();
		if (array_key_exists('mode', $query_vars)) {
			$view_name = ucwords($query_vars['mode']) . 'View';
			if (!Component::isActive($view_name)) {
				return false;
			}
		}
		
		//No View found, check the accept header
		if (!$view_name) {
			$default_precedence = array(
				'text/html' => (float)1,
				'application/xhtml+xml' => 0.9,
				'application/xml' => 0,
			);
			$mime_ranges = Parser::accept_header(false, $default_precedence);
			if ($mime_ranges) {
				$types = array();
				$main_types = array();
				$view_name = false;
				foreach($mime_ranges as $mime_type) {
					$types[] = $mime_type['main_type'] . '/' . $mime_type['sub_type'];
					$main_types[] = $mime_type['main_type'];
					if (!$view_name) {
						$name = class_name(str_replace('+', ' ', $mime_type['main_type']) . ' ' . str_replace('+', ' ', $mime_type['sub_type'])) . 'View';
						if (Component::isActive($name)) {
							$view_name = $name;
						} else {
							$name = class_name(str_replace('+', ' ', $mime_type['main_type'])) . 'View';
							if (Component::isActive($name)) {
								$view_name = $name;
							} else {
								$name = class_name(str_replace('+', ' ', $mime_type['sub_type'])) . 'View';
								if (Component::isActive($name)) {
									$view_name = $name;
								}
							}
						}
					}
				}
				if (in_array('image', $main_types) && in_array('application', $main_types)) {
				//Probably IE
					$view_name = 'HtmlView';
				} else if (in_array('application/xml', $types) && in_array('application/xhtml+xml', $types) && in_array('text/html', $types)) {
				//Maybe another confused browser that asks for XML and HTML
					$view_name = 'HtmlView';
				} else if (count($mime_ranges) == 1 && $mime_ranges[0]['main_type'] == '*' && $mime_ranges[0]['sub_type'] == '*') {
					$view_name = Backend::getConfig('backend.default.view', 'HtmlView');
				}
			} else {
				$view_name = Backend::getConfig('backend.default.view', 'HtmlView');
			}
		}

		//Last chance to get a View / Modify the view
		$view_name = Hook::run('view_name', 'pre', array($view_name));

		if ($view_name == 'View') {
			//Unrecognized Request, abort
			return false;
		}
	
		//We have an active view, or an HTML Request on an uninstalled installation
		if (Component::isActive($view_name) || (!BACKEND_INSTALLED && $view_name == 'HtmlView')) {
			$view = new $view_name();
			if (!headers_sent()) {
				header('X-Backend-View: ' . get_class($this));
			}
			return $view;
		}
		return false;
	}
	
	public function getTemplateLocation($filename) {
		$folders = $this->getTemplateFolders();
		foreach($folders as $folder) {
			if (is_readable($folder . '/' . $filename)) {
				return $folder . '/' . $filename;
			}
		}
		return false;
	}
	
	/**
	 * Override / extend this function to provide extra folders to check in for templates.
	 * Usefull to do themes in
	 */
	protected static function getTemplateFolders() {
		$template_loc = Backend::getConfig('backend.templates.location', 'templates');
		$folders      = array();
		//SITE FOLDER
		if (defined('SITE_FOLDER') && is_readable(SITE_FOLDER . '/' . $template_loc)) {
			$folders[] = SITE_FOLDER . '/'. $template_loc;
		//APP FOLDER
		} else if (is_readable(APP_FOLDER . '/' . $template_loc)) {
			$folders[] = APP_FOLDER . '/'. $template_loc;
		//BACKEND_FOLDER
		} else if (is_readable(BACKEND_FOLDER . '/' . $template_loc)) {
			$folders[] = BACKEND_FOLDER . '/'. $template_loc;
		}
		return $folders;
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
		//Run the View first, so that the other hooks have the output to work on
		if (method_exists($this, 'hook_output')) {
			$to_print = $this->hook_output($to_print);
		}
		$to_print = Hook::run('output', 'pre', array($to_print), array('toret' => $to_print));

		echo $to_print;
		
		//Run the View first, so that the other hooks have the output to work on
		if (method_exists($this, 'hook_post_output')) {
			$to_print = $this->hook_post_output($to_print);
		}
		$to_print = Hook::run('output', 'post', array($to_print), array('toret' => $to_print));
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
