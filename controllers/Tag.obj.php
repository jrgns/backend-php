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
		return true;
	}

	public static function hook_post_display($object) {
		if (Controller::$area == 'content' && in_array(Controller::$action, array('display'))) {
			$tags = self::getTags($object->array['id']);
			//Don't add Content, only render it.
			Backend::add('obj_tags', $tags);
			Controller::addContent(Render::renderFile('tags.tpl.php'));
		}
		return true;
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
	
	public static function install() {
		$toret = true;
		$hook = new HookObj();
		$toret = $hook->replace(array(
				'name'        => 'Tag Pre Form',
				'description' => '',
				'mode'        => '*',
				'type'        => 'pre',
				'hook'        => 'form',
				'class'       => 'Tag',
				'method'      => 'hook_form',
				'sequence'    => 0,
			)
		) && $toret;
		$toret = $hook->replace(array(
				'name'        => 'Tag Post Action Display',
				'description' => '',
				'mode'        => '*',
				'type'        => 'post',
				'hook'        => 'display',
				'class'       => 'Tag',
				'method'      => 'hook_post_display',
				'sequence'    => 0,
			)
		) && $toret;
		$toret = $hook->replace(array(
				'name'        => 'Tag Post Update',
				'description' => '',
				'mode'        => '*',
				'type'        => 'post',
				'hook'        => 'update',
				'class'       => 'Tag',
				'method'      => 'hook_post_update',
				'sequence'    => 0,
			)
		) && $toret;
		$toret = $hook->replace(array(
				'name'        => 'Tag Post Create',
				'description' => '',
				'mode'        => '*',
				'type'        => 'post',
				'hook'        => 'create',
				'class'       => 'Tag',
				'method'      => 'hook_post_create',
				'sequence'    => 0,
			)
		) && $toret;
		return $toret;
	}
}
