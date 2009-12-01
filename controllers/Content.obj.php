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
class Content extends TableCtl {
	public function html_list($content) {
		parent::html_list($content);
		Backend::add('Sub Title', '');
	}

	function html_display($content) {
		$toret = false;
		if ($content instanceof DBObject) {
			Backend::add('Sub Title', $content->array['title']);
			if ($content->array['from_file']) {
				$filename = 'content/static/' . $content->array['name'] . '.html';
				$template = 'content/' . $content->array['name'] . '.tpl.php';
				if (Render::checkTemplateFile($template)) {
					Controller::addContent(Render::renderFile($template));
					$toret = $content;
				} else if (file_exists(APP_FOLDER . '/' . $filename)) {
					Controller::addContent(file_get_contents(APP_FOLDER . '/' . $filename));
					$toret = $content;
				} else if (file_exists(BACKEND_FOLDER . '/' . $filename)) {
					Controller::addContent(file_get_contents(BACKEND_FOLDER . '/' . $filename));
					$toret = $content;
				//SITE FOLDER too?
				}
			} else {
				Backend::add('Content', $content);
				Controller::addContent(Render::renderFile('content.display.tpl.php'));
				$toret = $content;
			}
		}
		/**
		 * Is this necessary?
		if (!$toret) {
			if (Controller::$debug) {
				$filename = 'content/' . Controller::$id . '.tpl.php';
				if (Render::checkTemplateFile($filename)) {
					Controller::addNotice('File available for content');
				}
			}
		}
		 */
		return $toret;
	}
	
	function html_update($content) {
		$toret = parent::html_update($content);
		if ($toret) {
			Backend::add('Sub Title', 'Update: ' . $content->array['title']);
		}
		return $toret;
	}
	
	function rss_list($result) {
		if ($result instanceof DBObject) {
			Backend::add('title', Backend::getConfig('application.Title'));
			Backend::add('link', SITE_LINK . '?q=content');
			Backend::add('description', Backend::getConfig('application.description'));
			if (!empty($result->list) && is_array($result->list)) {
				$list = array();
				foreach($result->list as $item) {
					$item['link'] = SITE_LINK . '?q=content/' . $item['id'];
					$list[] = $item;
				}
			} else {
				$list = false;
			}
			Backend::add('list', $list);
		}
		return $result;
	}

	function atom_list($result) {
		if ($result instanceof DBObject) {
			Backend::add('title', Backend::getConfig('application.Title'));
			Backend::add('link', SITE_LINK . '?q=content');
			Backend::add('description', Backend::getConfig('application.description'));
			if (!empty($result->list) && is_array($result->list)) {
				$list = array();
				foreach($result->list as $item) {
					$item['link'] = SITE_LINK . '?q=content/' . $item['id'];
					$list[] = $item;
				}
			} else {
				$list = false;
			}
			Backend::add('list', $list);
		}
		return $result;
	}

	function action_display($id) {
		$toret = false;
		if (is_numeric($id)) {
			$toret = self::action_read($id);
		} else {
			$conds = array('`name` = :name');
			$params = array(':name' => $id);

			$toret = new ContentObj();
			list($query, $params) = $toret->getSelectSQL(array('parameters' => $params, 'conditions' => $conds));
			$toret->loadObject(array('query' => $query, 'parameters' => $params));
		}

		if ($toret && !empty($toret->object)) {
			if (!$this->checkPermissions(array('subject_id' => $toret->object->id, 'subject' => 'content'))) {
				Controller::whoops(array('title' => 'Permission Denied', 'message' => 'You do not have permission to display ' . $toret->object->title));
				$toret = false;
			}
		} else {
			Controller::whoops(array('title' => 'Unknown Content', 'message' => 'The page you requested could not be found.'));
			$toret = false;
		}
		if ($toret && Controller::$debug) {
			var_dump('Content ID: ' . $toret->object->id);
		}
		return $toret;
	}
	
	/**
	 * Trim content to a certain number of words
	 *
	 * Copied from http://www.lullabot.com/articles/trim_a_string_to_a_given_word_count
	 */
	public static function createPreview($content, $count, $ellips = true) {
		$words = explode(' ', $content);
		if (count($words) > $count) {
			array_splice($words, $count);
			$content = implode(' ', $words);
			if (is_string($ellips)) {
				$content .= $ellips;
			} elseif ($ellips) {
				$content .= '&hellip;';
			}
		}
		return $content;		
	}
	
	public static function install(array $options = array()) {
		$toret = parent::install($options);

		$toret = Permission::add('anonymous', 'display', 'content') && $toret;
		$toret = Permission::add('anonymous', 'list', 'content') && $toret;
		return $toret;
	}

	/*
	 * @todo This isn't entirely accurate. If you want to create a random action_something, it need's to be
	 * added to the array below... This isn't optimal. Either get the array dynamically (get_class_methods) or refactor.
	 */
	public static function checkParameters($parameters) {
		$parameters = parent::checkParameters($parameters);
		if (!method_exists(__CLASS__, 'action_' . Controller::$action)) {
			$parameters[0] = Controller::$action;
			Controller::setAction('display');
		}
		return $parameters;
	}
}
