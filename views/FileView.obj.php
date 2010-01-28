<?php
/**
 * The file that defines the FileView class.
 *
 * @author J Jurgens du Toit (JadeIT cc) <jurgens.dutoit@gmail.com> - initial API and implementation
 * @copyright Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available {@link http://www.eclipse.org/legal/epl-v10.html here}
 * @license http://www.eclipse.org/legal/epl-v10.html Eclipse Public License v1.0
 * @package View
 */
 
/**
 * Default class to handle FileView specific functions
 */
class FileView extends View {
	function __construct() {
		parent::__construct();
		$this->mode = 'file';
	}
	
	public static function hook_output($to_print) {
		if ($to_print && $to_print->array && !headers_sent()) {
			$mime_type = array_key_exists('mime_type', $to_print->array) ? $to_print->array['mime_type'] : false;
			if (empty($mime_type)) {
				$mime_type = $to_print->default_type;
			}
			if ($to_print->array['from_db']) {
				$content = $to_print->array['content'];
			} else {
				$content = file_get_contents($to_print->array['content']);
			}
			if (Controller::$debug) {
				var_dump($mime_type);
				var_dump($content); die(get_called_class() . '::hook_output');
			} else {
				$headers = apache_request_headers();
				header('Content-Type: ' . $mime_type);
				//header('Content-disposition: attachment; filename=' . $file->array['name']);
				header('Last-Modified: ' . gmdate('D, d M Y H:i:s', strtotime($to_print->array['modified'])));
				header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 60 * 60 * 24 * 7).'GMT');
				header('Cache-Control: max-age: ' . 60 * 60 * 24 * 7);
				header('Pragma: cache');
				die($content);
			}
		}
	}
	
	public static function hook_post_start() {
		if (Controller::$area == $this->mode && Controller::$action == 'read' && !array_key_exists('mode', $_REQUEST)) {
			$_REQUEST['mode'] = $this->mode;
		}
	}

	public static function install() {
		$toret = true;
		$toret = Hook::add('start', 'post', __CLASS__, array('mode' => 'file', 'global' => 1)) && $toret;
		return $toret;
	}
}

