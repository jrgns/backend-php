<?php
/**
 * The file that defines the ImageView class.
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
 * Default class to handle ImageView specific functions
 */
class ImageView extends View {
	function __construct() {
		parent::__construct();
		$this->mode = 'image';
	}
	
	public static function hook_output($to_print) {
		if ($to_print && $to_print->array && !headers_sent()) {
			$mime_type = array_key_exists('mime_type', $to_print->array) ? $to_print->array['mime_type'] : false;
			if (empty($mime_type)) {
				$mime_type = $to_print->default_type;
			}
			if (Controller::$debug) {
				var_dump($mime_type);
				var_dump($to_print->array['content']); die;
			} else {
				header('Content-Type: ' . $mime_type);
				//header('Content-disposition: attachment; filename=' . $file->array['name']);
				header('Last-Modified: ' . $to_print->array['modified']);
				header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 60 * 60 * 24 * 7).'GMT');
				die($to_print->array['content']);
			}
		}
	}
	
	public static function hook_post_start() {
		if (Controller::$area == 'image' && Controller::$action == 'read' && !array_key_exists('mode', $_REQUEST)) {
			$_REQUEST['mode'] = 'image';
		}
	}

	public static function install() {
		$toret = true;
		$hook = new HookObj();
		$toret = $hook->replace(array(
				'name'        => 'ImageView Pre Display',
				'description' => '',
				'mode'        => 'image',
				'type'        => 'pre',
				'hook'        => 'output',
				'class'       => 'ImageView',
				'method'      => 'hook_output',
				'sequence'    => 0,
			)
		) && $toret;
		$toret = $hook->replace(array(
				'name'        => 'ImageView Post Start',
				'description' => '',
				'mode'        => '*',
				'type'        => 'post',
				'hook'        => 'start',
				'class'       => 'ImageView',
				'method'      => 'hook_post_start',
				'sequence'    => 0,
			)
		) && $toret;
		return $toret;
	}
}

