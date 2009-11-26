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
class Image extends File {
	function image_read($image) {
		return $image;
	}
	
	function html_display($image) {
		Backend::add('TabLinks', $this->getTabLinks(Controller::$action));
		Backend::add('Sub Title', $image->array['name']);
		Controller::addContent('<div class="image_container"><img src="?q=image/read/' . $image->array['id'] . '" title="' . $image->array['title'] . '" alt="' . $image->array['title'] . '" /></div>');
	}
	
	public function html_list($content) {
		Backend::add('Sub Title', $content->getMeta('name'));
		Backend::add('TabLinks', $this->getTabLinks(Controller::$action));
		Backend::add('Object', $content);
		Controller::addScript(SITE_LINK . 'scripts/jquery.js');
		Controller::addScript(SITE_LINK . 'scripts/image_list.js');
		Controller::addStyle(SITE_LINK . 'styles/image_list.css');
		Controller::addContent(Render::renderFile('image_list.tpl.php'));
	}
	
	public function action_list($count) {
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

	public static function install(array $options = array()) {
		$toret = parent::install($options);

		$toret = Permission::add('anonymous', 'display', 'image') && $toret;
		return $toret;
	}
}
