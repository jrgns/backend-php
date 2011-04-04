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
class Content extends CommentedController {
	public function html_list($content) {
		parent::html_list($content);
		Backend::add('Sub Title', '');
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
					$item['body'] = Content::createPreview($item['body'], false);
					$list[] = $item;
				}
			} else {
				$list = false;
			}
			Backend::add('list', $list);
		}
		return $result;
	}

	public function rss_list($result) {
		return $this->feed_list($result, 'rss');
	}

	public function atom_list($result) {
		return $this->feed_list($result, 'atom');
	}

	public function action_create($id = false) {
		if (is_get()) {
			$obj = Controller::getVar('obj');
			$obj = $obj ? $obj : array();
			if (!empty($id)) {
				$obj['name']   = $id;
				$obj['title']  = humanize($id);
			}
			$obj['active'] = 1;
			Controller::setVar('obj', $obj);
		}
		$result = parent::action_create();
		if ($result instanceof ContentObj) {
			/* TODO This can easily "overwrite" existing urls */
			if (is_post() && Component::isActive('BackendQuery')) {
				BackendQuery::add($result->array['name'], 'content/display/' . $result->array['id']);
			}
		}
		return $result;
	}

	public function action_display($id) {
		$id = Hook::run('table_display', 'pre', array($id), array('toret' => $id));

		$toret = Content::retrieve($id, 'dbobject');
		if ($toret instanceof DBObject && !empty($toret->object)) {
			if (!$this->checkPermissions(array('subject_id' => $toret->object->id, 'subject' => 'content'))) {
				Controller::whoops(array('title' => 'Permission Denied', 'message' => 'You do not have permission to display ' . $toret->object->title));
				$toret = false;
			}
		} else if ($toret instanceof DBObject && $id == 'last') {
			$toret->read(array('limit' => 1, 'conditions' => array('`active` = 1'), 'order' => '`added` DESC', 'mode' => 'object'));
			if (!$toret->object) {
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
			Backend::addNotice('Content ID: ' . $toret->object->id);
		}

		$object = Hook::run('table_display', 'post', array($toret), array('toret' => $toret));
		return $toret;
	}
	
	function html_display($content) {
		if ($content instanceof DBObject) {
			Backend::add('Sub Title', $content->array['title']);
			if ($content->array['from_file']) {
				//Move this to the object
				$filename = 'content/static/' . $content->array['name'] . '.html';
				$template = 'content/' . $content->array['name'] . '.tpl.php';
				if (Render::checkTemplateFile($template)) {
					$content->object->body = Render::renderFile($template);
				} else if (file_exists(APP_FOLDER . '/' . $filename)) {
					$content->object->body = file_get_contents(APP_FOLDER . '/' . $filename);
				} else if (file_exists(BACKEND_FOLDER . '/' . $filename)) {
					$content->object->body = file_get_contents(BACKEND_FOLDER . '/' . $filename);
				//SITE FOLDER too?
				}
			}
		}
		$content = parent::html_display($content);
		return $content;
	}
	
	public function action_search_index() {
		if (Component::isActive('BackendSearch')) {
			return BackendSearch::doIndex($this, array('title', 'markdown'));
		} else {
			Backend::addError('Backend Search is not enabled');
			return false;
		}
	}
	
	public function action_search($start, $count, $term, array $options = array()) {
		if (Component::isActive('BackendSearch')) {
			$result = array('term' => $term);
			if ($term) {
				$result['results'] = BackendSearch::search($this, $term, array('`contents`.`active` = 1'));
			}
			return $result;
		} else {
			Backend::addError('Backend Search is not enabled');
		}
		return false;
	}
	
	public function html_search($result) {
		foreach($result as $name => $value) {
			Backend::add($name, $value);
		}
		Backend::addContent(Render::renderFile('backend_search.tpl.php'));
	}
	
	public static function createPreview($content, $ellips = true) {
		$pattern = '/(<br\/?><br\/?>|<!--break-->)/';
		$content = current(preg_split($pattern, $content, 2));
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
			$content = array_key_exists('markdown', $content) ? markdown($content['markdown']) : $content['body'];
			Backend::addContent($content);
		}
	}
	
	public static function hook_table_display($id) {
		return $id;
	}
	
	/**
	 * We check if there's any content of the name ?q=:name
	 */
	public static function hook_init() {
		$query = Controller::getVar('q');
		if (empty($query)) {
			return;
		}
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

		$toret = Hook::add('init', 'pre', get_called_class(), array('global' => true)) && $toret;
		$toret = Hook::add('table_display', 'pre', get_called_class()) && $toret;

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
		if (!method_exists(get_called_class(), 'action_' . Controller::$action)) {
			$parameters[0] = Controller::$action;
			Controller::setAction('display');
		}
		return $parameters;
	}
}
