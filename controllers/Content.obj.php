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
					Backend::addContent(Render::renderFile($template));
					$toret = $content;
				} else if (file_exists(APP_FOLDER . '/' . $filename)) {
					Backend::addContent(file_get_contents(APP_FOLDER . '/' . $filename));
					$toret = $content;
				} else if (file_exists(BACKEND_FOLDER . '/' . $filename)) {
					Backend::addContent(file_get_contents(BACKEND_FOLDER . '/' . $filename));
					$toret = $content;
				//SITE FOLDER too?
				}
			} else {
				Backend::add('Content', $content);
				Backend::addContent(Render::renderFile('content.display.tpl.php'));
				$toret = $content;
			}
		}
		/**
		 * Is this necessary?
		if (!$toret) {
			if (Controller::$debug) {
				$filename = 'content/' . Controller::$id . '.tpl.php';
				if (Render::checkTemplateFile($filename)) {
					Backend::addNotice('File available for content');
				}
			}
		}
		 */
		return $toret;
	}
	
	function html_update($result) {
		$result = parent::html_update($result);
		if ($result === true) {
			$content = self::getObject(get_class($this), Controller::$parameters[0]);
			Backend::add('Sub Title', 'Update: ' . $content->array['title']);
		}
		return $result;
	}
	
	private function feed_list($result, $mode) {
		if ($result instanceof DBObject) {
			Backend::add('title', Backend::getConfig('application.Title'));
			Backend::add('link', SITE_LINK . '?q=content');
			Backend::add('description', Backend::getConfig('application.description'));
			if (!empty($result->list) && is_array($result->list)) {
				$list = array();
				foreach($result->list as $item) {
					if (Value::get('clean_urls', false)) {
						$item['link'] = SITE_LINK . 'content/' . $item['id'];
					} else {
						$item['link'] = SITE_LINK . '?q=content/' . $item['id'];
					}
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

	function action_create($id = false) {
		if (!empty($id)) {
			$_POST['obj']['name'] = $id;
			$_POST['obj']['title'] = humanize($id);
		}
		$toret = parent::action_create();
		return $toret;
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
		} else if (Permission::check('create', 'content')) {
			Backend::addNotice('The content does not exist, but you can create it now');
			Controller::redirect('?q=content/create/' . $id);
			$toret = false;
		} else {
			Controller::whoops(array('title' => 'Unknown Content', 'message' => 'The page you requested could not be found.'));
			$toret = false;
		}
		if ($toret && Controller::$debug) {
			var_dump('Content ID: ' . $toret->object->id);
		}
		return $toret;
	}
	
	public static function createPreview($content, $ellips = true) {
		$pattern = '(<br\/?><br\/?>|<!--break-->)';
		$content = current(split($pattern, $content, 2));
		if (is_string($ellips)) {
			return $content . $ellips;
		} else if ($ellips) {
			return $content . '&hellip;';
		}
		return $content;
	}

	/**
	 * Trim content to a certain number of words
	 *
	 * Copied from http://www.lullabot.com/articles/trim_a_string_to_a_given_word_count
	 */
	public static function trimWords($content, $count = 50, $ellips = true) {
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
	
	public static function show($id) {
		$content = Content::retrieve($id);
		if ($content) {
			$content = array_key_exists('markdown', $content) ? $content['markdown'] : $content['body'];
			Backend::addContent($content);
		}
	}
	
	public static function hook_init() {
		if (empty($_REQUEST['q'])) {
			return;
		}
		$query = $_REQUEST['q'];
		if (substr($query, -1) == '/') {
			$query = substr($query, 0, strlen($query) - 1);
		}
		$select = new SelectQuery('Content');
		$select->filter('`name` = :query');
		$row = $select->fetchAssoc(array(':query' => $query));
		if ($row) {
			$_REQUEST['q'] = 'content/' . $row['id'];
		}
	}
	
	public static function install(array $options = array()) {
		$toret = parent::install($options);

		$toret = Hook::add('init', 'pre', __CLASS__, array('global' => true)) && $toret;

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
