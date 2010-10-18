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
class Tag extends TableCtl {
	protected function getTabLinks($action) {
		$links = $action == 'display' ? array() : parent::getTabLinks($action);
		return $links;
	}
	
	public static function getTags($table, $table_id = null) {
		if (is_null($table_id)) {
			if (!$table instanceof DBObject) {
				return false;
			}
			$table_id = $table->getMeta('id');
			$table    = $table->getMeta('table');
		}
		$query = new SelectQuery('Tag');
		$query
			->leftJoin('TagLink', array('`tags`.`id` = `tag_links`.`tag_id`'))
			->filter('`tags`.`foreign_table` = :table')
			->filter('`tag_links`.`foreign_id` = :id');
		return $query->fetchAll(array(':table' => $table, ':id' => $table_id));
	}
	
	public static function getTagNames($table, $table_id) {
		$query = new SelectQuery('Tag');
		$query
			->field('`tags`.`id`, `tags`.`name`')
			->leftJoin('TagLink', array('`tags`.`id` = `tag_links`.`tag_id`'))
			->filter('`tags`.`foreign_table` = :table')
			->filter('`tag_links`.`foreign_id` = :id');
		$result = $query->fetchAll(array(':table' => $table, ':id' => $table_id), array('with_key' => true));
		if (!$result) {
			return $result;
		}
		foreach($result as $key => $value) {
			$result[$key] = current($value);
		}
		return $result;
	}
	
	public static function removeTags($table, $table_id) {
		/* TODO
		$query = new SelectQuery('Tag');
		$query
			->leftJoin('TagLink', array('`tags`.`id` = `tag_links`.`tag_id`'))
			->filter('`tags`.`foreign_table` = :table')
			->filter('`tag_links`.`foreign_id` = :id');
		return $query->fetchAll(array(':table' => $table, ':id' => $table_id));*/
	}
	
	/**
	 * Tag a data record with one or multiple tags
	 *
	 * @param mixed An array of string tags, or a string tag
	 * @param DBObject The data object to tag
	 */
	public static function add($tags, DBObject $object) {
		$result = true;
		if (is_array($tags)) {
			$tags = array_filter(array_map('plain', array_map('trim', $tags)));
			foreach($tags as $tag) {
				if ($tag_info = Tag::check($tag, $object)) {
					$result = TagLink::add($tag_info['id'], $object) && $result;
				} else {
					$result = false;
				}
			}
		} else {
			$tags   = explode(',', $tags);
			$result = self::add($tags, $object);
		}
		return $result;
	}
	
	/**
	 * Check if a tag exists for a specified Area, add it if it doesn't
	 */
	public static function check($name, $area) {
		if ($area instanceof DBObject) {
			$area = $area->getMeta('table');
		}
		
		//Check if tag exists
		$query = new SelectQuery('Tag');
		$query
			->filter('`foreign_table` = :table')
			->filter('`name` = :tag');
		if ($tag = $query->fetchAssoc(array(':tag' => $name, ':table' => $area))) {
			return $tag;
		}
		
		//Tag doesn't already exist
		$data = array(
			'name'          => $name,
			'foreign_table' => $area,
			'active'        => 1,
		);
		$tag = new TagObj();
		if ($tag->create($data)) {
			return $tag->array;
		}
		return false;
	}
	
	public function action_display($id, $start = 0, $count = false) {
		$count = $count === false ? Value::get('list_length', 5) : $count;
		$tag = self::getObject($this, $id);
		if (!($tag instanceof TagObj)) {
			return $tag;
		}

		$links = self::getObject($tag->array['foreign_table']);
		list($query, $params) = $links->getSelectSQL();
		if (!($query instanceof SelectQuery)) {
			return false;
		}
		$query_links = new SelectQuery('TagLink');
		$query_links
			->field('`foreign_id`')
			->filter('`tag_id` = :tag_id');

		$query
			->field(':tag_id AS `tag_id`')
			->filter('`' . $links->getMeta('id_field') . '` IN (' . $query_links . ')')
			->limit("$start, $count");

		$params = array(
			':tag_id' => $tag->array['id']
		);
		$links->load(array('mode' => 'list', 'query' => $query, 'parameters' => $params));

		$tag->array['list'] = $links->list;
		$tag->array['list_count'] = $links->list_count;
		return $tag;
	}
	
	public function html_display($result) {
		$result = parent::html_display($result);
		if (!($result instanceof DBObject)) {
			return $result;
		}
		Backend::add('Sub Title', $result->array['name']);
		if (Render::checkTemplateFile('tag.' . $result->array['foreign_table'] . '.list.tpl.php')) {
			Backend::addContent(Render::renderFile('tag.' . $result->array['foreign_table'] . '.list.tpl.php'));
		} else {
			Backend::addContent(Render::renderFile('tag.display.list.tpl.php'));
		}
		return $result;
	}
	
	private function feed_display($result, $mode) {
		if (!($result instanceof DBObject)) {
			return $result;
		}
		Backend::add('title', $result->array['name']);
		Backend::add('link', SITE_LINK . '?q=tag/' . $result->array['id']);
		Backend::add('description', $result->array['description']);
		if (!empty($result->array['list']) && is_array($result->array['list'])) {
			$list = array();
			foreach($result->array['list'] as $item) {
				$link = SITE_LINK;
				if (Value::get('clean_urls', false)) {
					$link .= $result->array['foreign_table'] . '/' . $item['id'];
				} else {
					$link .= '?q=' . $result->array['foreign_table'] . '/' . $item['id'];
				}
				$item['link'] = $link;
				if ($result->array['foreign_table'] == 'contents') {
					$item['body'] = Content::createPreview($item['body']);
				}
				$list[] = $item;
			}
		} else {
			$list = false;
		}
		Backend::add('list', $list);
		return $result;
	}

	function rss_display($result) {
		return $this->feed_display($result, 'rss');
	}

	function atom_display($result) {
		return $this->feed_display($result, 'atom');
	}

	/*
	public static function hook_form($object) {
		if (Controller::$area == 'content' && in_array(Controller::$action, array('create', 'update'))) {
			$tags = self::getTags($object->array['id']);
			//Don't add Content, only render it.
			Backend::add('obj_tags', $tags);
			echo Render::renderFile('tags_form.tpl.php');
		}
		return $object;
	}
	*/

	public static function hook_post_table_display($object) {
		if (!($object instanceof DBObject)) {
			return $object;
		}
		if ($object instanceof DBObject && Controller::$area == 'content' && in_array(Controller::$action, array('display'))) {
			$tags = self::getTags($object);
			//Don't add Content, only render it.
			Backend::add('obj_tags', $tags);
			Backend::addContent(Render::renderFile('tags.tpl.php'));
		}
		return $object;
	}

	public static function hook_post_create($data, $object) {
		if (!($object instanceof DBObject) || !is_post()) {
			return true;
		}
		if ($tags = filter_input(INPUT_POST, 'tags')) {
			self::add($tags, $object);
			var_dump('Adding tags: ' . $tags);
		}
		return true;
	}

	public static function hook_post_update($data, $object) {
		if (!($object instanceof DBObject) || !is_post()) {
			return true;
		}
		$tags = array_key_exists('tags', $_POST) ? $_POST['tags'] : false;
		if (!empty($tags) && $object instanceof ContentObj) {
			$tags = array_filter(array_map('trim', explode(',', $tags)));
			self::add($tags, $object);
		}
		return true;
	}

	public static function checkParameters($parameters) {
		$parameters = parent::checkParameters($parameters);
		if (Controller::$action == 'display') {
			$parameters[1] = array_key_exists(1, $parameters) ? $parameters[1] : 0;
			$parameters[2] = array_key_exists(2, $parameters) ? $parameters[2] : Value::get('TagContentListLength', 10);
		} else if (in_array(Controller::$action, array('display'))) {
			if (!isset(Controller::$parameters[0])) {
				$parameters[1] = 0;
			}
			if (!isset(Controller::$parameters[2])) {
				$parameters[2] = Value::get('list_length', 5);
			}
		}
		return $parameters;
	}

	public static function install(array $options = array()) {
		$toret = parent::install($options);

		$toret = Permission::add('anonymous', 'display', __CLASS__) && $toret;
		$toret = Permission::add('authenticated', 'display', __CLASS__) && $toret;
		$toret = Permission::add('anonymous', 'list', __CLASS__) && $toret;
		$toret = Permission::add('authenticated', 'list', __CLASS__) && $toret;
		
		$toret = Hook::add('form',    'pre',  __CLASS__, array('global' => 1)) && $toret;
		$toret = Hook::add('table_display', 'post', __CLASS__, array('global' => 1)) && $toret;
		$toret = Hook::add('update',  'post', __CLASS__, array('global' => 1)) && $toret;
		$toret = Hook::add('create',  'post', __CLASS__, array('global' => 1)) && $toret;

		return $toret;
	}
}
