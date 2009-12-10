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

	public function action_read($id) {
		$toret = false;
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true)) {
			$toret = new $obj_name($id);
			if ($toret->array) {
				$mime_type = array_key_exists('mime_type', $toret->array) ? $toret->array['mime_type'] : false;
				if (!empty($mime_type)) {
					//Controller::$mime_type is deprecated. Find a way to set it in the view
					//Controller::$mime_type = $mime_type;
				} else {
					//Controller::$mime_type = self::$default_type;
				}
			}
		} else {
			Controller::whoops();
		}
		return $toret;
	}
	
	function html_read($file) {
		Controller::redirect('?q=' . $file->getArea() . '/display/' . $file->getMeta('id'));
	}
	
	function html_display($file) {
		Backend::add('TabLinks', $this->getTabLinks(Controller::$action));
		Backend::add('Sub Title', $file->array['name']);
		Controller::addContent('<a href="?q=' . class_for_url(get_class($this)) . '/read/' . $file->array['id'] . '" title="' . $file->array['name'] . '">' . $file->array['name'] . '</a>');
	}
	
	public function action_list($start, $count) {
		$toret = false;
		Backend::add('Sub Title', 'List');
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true)) {
			$object = new $obj_name();
			$object->load(array('limit' => $count));
			$toret = $object;
		} else {
			Controller::whoops();
		}
		return $toret;
	}
}
