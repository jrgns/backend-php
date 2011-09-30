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
			Backend::add('title', ConfigValue::get('Title'));
			Backend::add('link', SITE_LINK . '/?q=content');
			Backend::add('description', ConfigValue::get('Description'));
			if (!empty($result->list) && is_array($result->list)) {
				$list = array();
				foreach($result->list as $item) {
					if (ConfigValue::get('CleanURLs', false)) {
						$item['link'] = SITE_LINK . '/content/' . $item['id'];
					} else {
						$item['link'] = SITE_LINK . '/?q=content/' . $item['id'];
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

	public function get_create($id = false) {
		$result = parent::get_create();
		$values = Backend::get('values');
		$values = $values ? $values : array();
		if (!empty($id)) {
			$values['name']   = $id;
			$values['title']  = humanize($id);
		}
		$values['active'] = 1;
		Backend::add('values', $values);
		return $result;
	}

	public function post_create($id = false) {
		$result = parent::post_create();
		if ($result instanceof ContentObj) {
			/* TODO This can easily "overwrite" existing urls */
			if (is_post() && Component::isActive('BackendQuery')) {
				BackendQuery::add($result->array['name'], 'content/display/' . $result->array['id']);
			}
		}
		return $result;
	}

	public function get_display($id) {
		if (Backend::getDB('default')) {
		    $id = Hook::run('table_display', 'pre', array($id), array('toret' => $id));

		    $result = Content::retrieve($id, 'dbobject');
		    if ($result instanceof DBObject && !empty($result->object)) {
			    if (!$this->checkPermissions(array('subject_id' => $result->object->id, 'subject' => 'content'))) {
				    Controller::whoops(array('title' => 'Permission Denied', 'message' => 'You do not have permission to display ' . $result->object->title));
				    $result = false;
			    }
		    } else if ($result instanceof DBObject && $id == 'last') {
			    $result->read(array('limit' => 1, 'conditions' => array('`active` = 1'), 'order' => '`added` DESC', 'mode' => 'object'));
			    if (!$result->object) {
				    $result = false;
			    }
		    } else if (Permission::check('create', 'content')) {
			    Backend::addNotice('The content does not exist, but you can create it now');
			    Controller::redirect('?q=content/create/' . $id);
			    $result = false;
		    } else {
			    Controller::whoops(array('title' => 'Unknown Content', 'message' => 'The page you requested could not be found.'));
			    $result = false;
		    }
		    if ($result && Controller::$debug) {
			    Backend::addNotice('Content ID: ' . $result->object->id);
		    }

		    $object = Hook::run('table_display', 'post', array($result), array('toret' => $result));
		    return $result;
	    } else {
	        //DB less content
	        $template_file = array(
	            $id . '.tpl.php',
	            str_replace('/', '.', $id) . '.tpl.php',
	        );
		    if (Render::checkTemplateFile($template_file[0])) {
			    Backend::addContent(Render::file($template_file[0]));
		    } else if (Render::checkTemplateFile($template_file[1])) {
			    Backend::addContent(Render::file($template_file[1]));
		    } else {
		        Backend::addContent('Could not find file');
		    }
		    return true;
	    }
	}

	function html_display($content) {
		if ($content instanceof DBObject) {
			Backend::add('Sub Title', $content->array['title']);
			if ($content->array['from_file']) {
				//Move this to the object ??
				$filename = 'content/static/' . $content->array['name'] . '.html';
				$template = 'content/' . $content->array['name'] . '.tpl.php';
				if (Render::checkTemplateFile($template)) {
					$content->object->body = Render::file($template);
				} else if (file_exists(SITE_FOLDER . '/' . $filename)) {
					$content->object->body = file_get_contents(APP_FOLDER . '/' . $filename);
				} else if (file_exists(APP_FOLDER . '/' . $filename)) {
					$content->object->body = file_get_contents(APP_FOLDER . '/' . $filename);
				} else if (file_exists(BACKEND_FOLDER . '/' . $filename)) {
					$content->object->body = file_get_contents(BACKEND_FOLDER . '/' . $filename);
				//SITE FOLDER too?
				}
			}
			$meta_desc = Backend::get('meta_description');
			if (empty($meta_desc)) {
    			Backend::add('meta_description', plain(self::createPreview($content->object->body, false)));
			}
			$http_equiv = Backend::get('meta_http_equiv', array());
			$http_equiv['Last-Modified'] = $content->object->modified;
			Backend::add('meta_http_equiv', $http_equiv);
			if (!headers_sent()) {
				$max_age = ConfigValue::get('content.MaxAge', 86400);
				header('Last-Modified: ' . $content->object->modified);
				header('Expires: ' . gmdate('r', strtotime('+1 day')));
				header('Cache-Control: max-age=' . $max_age . ', must-revalidate');
				header('Pragma: cache');
			}
		}
		if (Backend::getDB('default')) {
		    //TODO Make some of the content values (such as added and lastmodified) available
		    //So you can add Last Modified on #lastmodified# to the content.
    		$content = parent::html_display($content);
		}
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

	public function action_search($term, $start, $count, array $options = array()) {
		if (Component::isActive('BackendSearch')) {
			$result = array('term' => $term);
			if ($term) {
				$result['results'] = BackendSearch::search($this, $term, array('`contents`.`active` = 1'));
			}
			return $result;
		} else {
		    //TODO Use normal table search
			Backend::addError('Backend Search is not enabled');
		}
		return false;
	}

	public function html_search($result) {
		foreach($result as $name => $value) {
			Backend::add($name, $value);
		}
		Backend::addContent(Render::file('backend_search.tpl.php'));
	}

	public static function createPreview($content, $ellips = true) {
		$pattern = '/(<br\/?><br\/?>|<!--break-->)/';
		$preview = current(preg_split($pattern, $content, 2));
		if ($preview == $content) {
			$preview = preg_split("/\n\n|\r\n\r\n|\n\r\n\r/", $content);
			$preview = reset($preview);
		}

		if (is_string($ellips)) {
			return $preview . $ellips;
		} else if ($ellips) {
			return $preview . '&hellip;';
		}
		return $preview;
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
			Controller::setVar('q', 'content/' . $row['id']);
		}
	}

	protected function getTabLinks($action) {
		if ($action != 'display') {
			return parent::getTabLinks($action);
		}
		return array();
	}

	public static function getSitemap() {
		$query = new SelectQuery('Content');
		$query
			->filter('`active` = 1');
		$list = $query->fetchAll();
		return array('list' => $list, 'options' => array());
	}

	public function daily($options) {
	    if (Component::isActive('BackendSearch')) {
	        BackendSearch::doIndex($this, array('name', 'title', 'markdown'));
	    }
	    return true;
	}

	public static function checkParameters($parameters) {
		$parameters = parent::checkParameters($parameters);
		if (
			   !method_exists(get_called_class(), 'action_' . Controller::$action)
			&& !method_exists(get_called_class(), 'get_' . Controller::$action)
			&& !method_exists(get_called_class(), 'post_' . Controller::$action)
			&& !method_exists(get_called_class(), 'put_' . Controller::$action)
			&& !method_exists(get_called_class(), 'delete_' . Controller::$action)
		) {
			$parameters[0] = Controller::$action;
			Controller::setAction('display');
		}
		return $parameters;
	}

	public static function install(array $options = array()) {
		$result = parent::install($options);

		if (!Backend::getDB('default')) {
			return $result;
		}
		$result = Hook::add('init', 'pre', get_called_class(), array('global' => true)) && $result;
		$result = Hook::add('table_display', 'pre', get_called_class()) && $result;

		$result = Permission::add('anonymous', TableCtl::$P_READONLY, get_called_class()) && $result;
		$result = Permission::add('authenticated', TableCtl::$P_READONLY, get_called_class()) && $result;
		return $result;
	}
}
