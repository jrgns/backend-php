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
	
	public function action_display($id) {
		$toret = parent::action_display($id);
		if ($toret instanceof DBObject) {
			$query = new CustomQuery('SELECT * FROM `contents` WHERE FIND_IN_SET(:tag_id, `tags`)');
			$toret->array['content_list'] = $query->fetchAll(array(':tag_id' => $toret->array['id']));
		}
		return $toret;
	}
	
	public function html_display($result) {
		$toret = parent::html_display($result);
		if ($result instanceof DBObject) {
			Backend::add('Sub Title', $result->array['name']);
		}
		return $toret;
	}
	
	public function rss_display($result) {
		if ($result instanceof DBObject) {
			Backend::add('title', $result->array['name']);
			Backend::add('link', SITE_LINK . '?q=tag/' . $result->array['id']);
			Backend::add('description', $result->array['description']);
			if (!empty($result->array['content_list']) && is_array($result->array['content_list'])) {
				$list = array();
				foreach($result->array['content_list'] as $item) {
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

	public static function addTags($content_id, array $tags) {
		$Tag = new TagObj();
		foreach($tags as $tag) {
			$data = array(
				'name'   => $tag,
				'active' => 1,
				'weight' => 0,
			);
			$Tag->create($data, array('ignore' => true));
			//If you want to keep track of the last time a tag was added to content, do this
			//$Tag->create($data, array('on_duplicate' => '`modified` = NOW()'));
		}
		$query = new CustomQuery('UPDATE `contents` SET `contents`.`tags` = (SELECT GROUP_CONCAT(DISTINCT `tags`.`id` ORDER BY `tags`.`id` SEPARATOR \',\') FROM `tags` WHERE `tags`.`name` IN (' . implode(', ', array_fill(0, count($tags), '?')) . ')) WHERE `contents`.`id` = ?');
		$tags[] = $content_id;
		return $query->execute($tags);
	}
	
	public static function getTags($content_id) {
		$query = new CustomQuery('SELECT `tags`.* FROM `tags` LEFT JOIN `contents` ON FIND_IN_SET(`tags`.`id`, `contents`.`tags`) WHERE `contents`.`id` = :id');
		return $query->fetchAll(array(':id' => $content_id));
	}
	
	public static function hook_form($object) {
		if (Controller::$area == 'content' && in_array(Controller::$action, array('create', 'update'))) {
			$tags = self::getTags($object->array['id']);
			//Don't add Content, only render it.
			Backend::add('obj_tags', $tags);
			echo Render::renderFile('tags_form.tpl.php');
		}
		return $object;
	}

	public static function hook_post_display($object) {
		if ($object instanceof DBObject && Controller::$area == 'content' && in_array(Controller::$action, array('display'))) {
			$tags = self::getTags($object->array['id']);
			//Don't add Content, only render it.
			Backend::add('obj_tags', $tags);
			Controller::addContent(Render::renderFile('tags.tpl.php'));
		}
		return $object;
	}

	public static function hook_post_create($data, $object) {
		$tags = array_key_exists('tags', $_POST) ? $_POST['tags'] : false;
		if (!empty($tags) && $object instanceof ContentObj) {
			$tags = array_filter(array_map('trim', explode(',', $tags)));
			self::addTags($object->array['id'], $tags);
		}
		return true;
	}

	public static function hook_post_update($data, $object) {
		$tags = array_key_exists('tags', $_POST) ? $_POST['tags'] : false;
		if (!empty($tags) && $object instanceof ContentObj) {
			$tags = array_filter(array_map('trim', explode(',', $tags)));
			self::addTags($object->array['id'], $tags);
		}
		return true;
	}
	
	public static function install(array $options = array()) {
		$toret = parent::install($options);

		$toret = Permission::add('anonymous', 'display', __CLASS__) && $toret;
		
		$toret = Hook::add('form',    'pre',  __CLASS__) && $toret;
		$toret = Hook::add('display', 'post', __CLASS__) && $toret;
		$toret = Hook::add('update',  'post', __CLASS__) && $toret;
		$toret = Hook::add('create',  'post', __CLASS__) && $toret;

		return $toret;
	}
}
