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
class Comment extends TableCtl {
	public static function addComments($content_id, array $comments) {
		$Comment = new CommentObj();
		foreach($comments as $comment) {
			$data = array(
				'title'   => $comment['title'],
				'content' => $comment['content'],
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
	
	public static function getComments($content_id) {
		$query = new CustomQuery('SELECT `comments`.* FROM `comments` LEFT JOIN `contents` ON FIND_IN_SET(`comments`.`id`, `contents`.`comments`) WHERE `contents`.`id` = :id');
		return $query->fetchAll(array(':id' => $content_id));
	}
	
	public static function hook_post_display($object) {
		if ($object instanceof DBObject && Controller::$area == 'content' && in_array(Controller::$action, array('display'))) {
			$comments = self::getComments($object->array['id']);
			Backend::add('comment_list', $comments);
			Controller::addContent(Render::renderFile('comments.tpl.php'));
		}
		return $object;
	}

	
	public static function install() {
		$toret = self::installModel(__CLASS__ . 'Obj');

		$hook = new HookObj();
		$toret = $hook->replace(array(
				'name'        => 'Comment Post Form',
				'description' => '',
				'mode'        => '*',
				'type'        => 'post',
				'hook'        => 'form',
				'class'       => 'Comment',
				'method'      => 'hook_post_form',
				'sequence'    => 0,
			)
		) && $toret;
		$toret = $hook->replace(array(
				'name'        => 'Comment Post Display',
				'description' => '',
				'mode'        => '*',
				'type'        => 'post',
				'hook'        => 'display',
				'class'       => 'Comment',
				'method'      => 'hook_post_display',
				'sequence'    => 0,
			)
		) && $toret;
		$toret = $hook->replace(array(
				'name'        => 'Comment Post Update',
				'description' => '',
				'mode'        => '*',
				'type'        => 'post',
				'hook'        => 'update',
				'class'       => 'Comment',
				'method'      => 'hook_post_update',
				'sequence'    => 0,
			)
		) && $toret;
		$toret = $hook->replace(array(
				'name'        => 'Comment Post Create',
				'description' => '',
				'mode'        => '*',
				'type'        => 'post',
				'hook'        => 'create',
				'class'       => 'Comment',
				'method'      => 'hook_post_create',
				'sequence'    => 0,
			)
		) && $toret;
		return $toret;
	}
}
