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
	public static function hook_form() {
		if (Controller::$area == 'content' && in_array(Controller::$action, array('create', 'update'))) {
			//Don't add Content, only render it.
			echo Render::renderFile('tags.tpl.php');
		}
		return true;
	}

	public static function hook_post_create($data, $object) {
		$tags = array_key_exists('tags', $_POST) ? $_POST['tags'] : false;
		if (!empty($tags) && $object instanceof ContentObj) {
			$tags = array_filter(array_map('trim', explode(',', $tags)));
			$Tag = new TagObj();
			foreach($tags as $tag) {
				$data = array(
					'name'   => $tag,
					'active' => 1,
					'weight' => 0,
				);
				$Tag->replace($data);
			}
		}
		return true;
	}

	public static function hook_post_update($data, $object) {
		$tags = array_key_exists('tags', $_POST) ? $_POST['tags'] : false;
		if (!empty($tags) && $object instanceof ContentObj) {
			$tags = array_filter(array_map('trim', explode(',', $tags)));
			$Tag = new TagObj();
			foreach($tags as $tag) {
				$data = array(
					'name'   => $tag,
					'active' => 1,
					'weight' => 0,
				);
				$Tag->replace($data);
			}
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
