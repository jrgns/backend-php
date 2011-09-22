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

	/**
	 * Read defaults to dbobject, as we need to get mime types, etc.
	 */
	public function action_read($id, $mode = 'dbobject') {
		$result = parent::action_read($id, $mode);
		return $result;
	}
	
	function html_read($file) {
		Controller::redirect('?q=' . $file->getArea() . '/display/' . $file->getMeta('id'));
	}
	
	function html_display($file) {
		Backend::add('TabLinks', $this->getTabLinks(Controller::$action));
		Backend::add('Sub Title', $file->array['name']);
		Backend::addContent('<a href="?q=' . class_for_url(get_class($this)) . '/read/' . $file->array['id'] . '" title="' . $file->array['name'] . '">' . $file->array['name'] . '</a>');
	}
	
	public function get_list($start, $count, array $options = array()) {
		$toret = false;
		Backend::add('Sub Title', 'List');
		$obj_name = (class_name(Controller::$area) . 'Obj');
		if (class_exists($obj_name, true)) {
			$object = new $obj_name();
			if ($start === 'all') {
				$object->read(array());
			} else {
				$object->read(array('limit' => "$start, $count"));
			}
			$toret = $object;
		} else {
			Controller::whoops();
		}
		return $toret;
	}
}
