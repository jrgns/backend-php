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
	function image_read($result) {
		if ($result instanceof ImageObj && $result->array) {
			//Set the specific mime type for this image
			Controller::$view->mime_type = $result->getMimeType();
		} else {
			Controller::whoops();
		}
		return $result;
	}

	function html_display($result) {
		if ($result instanceof ImageObj && $result->array) {
			$extension = explode('/', $result->getMimeType());
			$extension = end($extension);
			Backend::add('TabLinks', $this->getTabLinks(Controller::$action));
			Backend::add('Sub Title', $result->array['name']);
			Backend::addContent('<div class="image_container"><img src="' . SITE_LINK . '?q=image/read/' . $result->array['id'] . '.' . $extension . '" title="' . $result->array['title'] . '" alt="' . $result->array['title'] . '" /></div>');
		} else {
			Controller::whoops();
		}
	}

	public function html_list($result) {
		Backend::add('Sub Title', $result->getMeta('name'));
		Backend::add('TabLinks', $this->getTabLinks(Controller::$action));
		Backend::addScript(SITE_LINK . '/js/jquery.js');
		Backend::addScript(SITE_LINK . '/js/image_list.js');
		Backend::addStyle(SITE_LINK . '/css/image_list.css');
		Backend::addContent(Render::renderFile('image.list.tpl.php', array('db_object' => $result)));
	}

	private function feed_list($result, $mode) {
		if ($result instanceof DBObject) {
			Backend::add('title', ConfigValue::get('Title'));
			Backend::add('link', SITE_LINK . '/?q=content');
			Backend::add('description', ConfigValue::get('Description'));
			if (!empty($result->list) && is_array($result->list)) {
				$list = array();
				foreach($result->list as $item) {
					$item['link'] = SITE_LINK . '/?q=image/' . $item['id'];
					$item['body'] = '<img src="' . SITE_LINK . '/?q=image/' . $item['id'] . '">';
					$list[] = $item;
				}
			} else {
				$list = false;
			}
			Backend::add('list', $list);
		}
		return $result;
	}

	function rss_list($result) {
		return $this->feed_list($result, 'rss');
	}

	function atom_list($result) {
		return $this->feed_list($result, 'atom');
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

	public static function checkParameters($parameters) {
		if (Controller::$action == 'index') {
			Controller::setAction('list');
		}
		if (Controller::$action == 'list' && !isset(Controller::$parameters[0])) {
			$parameters[0] = 0;
		}
		if (Controller::$action == 'list' && !isset(Controller::$parameters[1])) {
			$parameters[1] = ConfigValue::get('table.ListLength', 9);
		}
		return parent::checkParameters($parameters);
	}

	public static function install(array $options = array()) {
		$toret = parent::install($options);

		$toret = Permission::add('anonymous', 'display', 'image') && $toret;
		return $toret;
	}
}
