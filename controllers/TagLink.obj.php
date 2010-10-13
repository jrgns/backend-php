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
class TagLink extends TableCtl {
	/**
	 * Create a link between a tag id and a foreign id
	 */
	public static function add($tag_id, $foreign_id) {
		if ($foreign_id instanceof DBObject) {
			$foreign_id = $foreign_id->getMeta('id');
		}
		if (empty($foreign_id)) {
			return false;
		}
		$data = array(
			'tag_id'     => $tag_id,
			'foreign_id' => $foreign_id,
		);
		$tag_link = new TagLinkObj();
		return $tag_link->replace($data);
	}
}
