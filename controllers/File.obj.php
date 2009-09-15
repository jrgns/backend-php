<?php
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
class File extends TableCtl {
	public static function getMimeType($file, $default = false) {
		$toret = false;
		if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME); 
			$toret = finfo_file($finfo, $file);
			finfo_close($finfo);
		} else if (function_exists('mime_content_type')) {
			$toret = mime_content_type($file);
		}
		$toret = $toret ? $toret : $default;
		return $toret;
	}

	public function action_read() {
		$toret = false;
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true)) {
			$toret = new $obj_name(Controller::$id);
			if ($toret->array) {
				$mime_type = array_key_exists('mime_type', $toret->array) ? $toret->array['mime_type'] : false;
				if (!empty($mime_type)) {
					Controller::$mime_type = $mime_type;
				} else {
					//Controller::$mime_type = self::$default_type;
				}
			}
		} else {
			Controller::whoops();
		}
		return $toret;
	}
	
	/*function file_read($file) {
		$toret = $file;
		if ($file && $file->array) {
			if ($file->array['from_db']) {
				$mime_type = array_key_exists('mime_type', $file->array) ? $file->array['mime_type'] : false;
				if (empty($mime_type)) {
					$mime_type = $file->default_type;
				}
				header('Content-Type: ' . $mime_type);
				header('Content-disposition: attachment; filename=' . $file->array['name']);
				header('Last-Modified: ' . $file->array['modified']);
				header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 60 * 60 * 24 * 7).'GMT');
				die($file->array['content']);
			} else {
				die('Finish this: File::file_read');
			}
		} else {
			die('Invalid File');
		}
		return $toret;
	}*/
	
	function html_read($file) {
		if (!empty($this)) {
			$this->file_read($file);
		} else {
			self::file_read($file);
		}
	}
	
	function html_display($file) {
		Backend::add('TabLinks', $this->getTabLinks(Controller::$action));
		Backend::add('Sub Title', $file->array['name']);
		Controller::addContent('<a href="?q=' . class_for_url(get_class($this)) . '/read/' . $file->array['id'] . '" title="' . $file->array['name'] . '">' . $file->array['name'] . '</a>');
	}
	
	public function action_list() {
		$toret = false;
		Backend::add('Sub Title', 'List');
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true)) {
			$object = new $obj_name();
			$object->load(array('limit' => Controller::$count));
			$toret = $object;
		} else {
			Controller::whoops();
		}
		return $toret;
	}
}
