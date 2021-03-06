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
class ContentRevision extends TableCtl {
	public function action_display($id) {
		$query = new SelectQuery('ContentRevision');
		$query
			->filter('`content_id` = :id')
			->order('`added` DESC');
		$revisions = $query->fetchAll(array(':id' => $id));
		$content = new ContentObj($id);
		if ($content->object) {
			$content->object->revisions = $revisions;
		} else {
			$content = false;
		}
		return $content;
	}

	public function html_display($content) {
		Backend::add('Sub Title', 'Revisions for ' . $content->array['name']);
		Backend::add('content', $content);
		Backend::add('revisions', $content->object->revisions);
		Backend::addContent(Render::renderFile('content_revision.display.tpl.php'));
		return true;
	}

	public static function hook_post_create($data, $object) {
		if ($object instanceof ContentObj && !$object->array['from_file']) {
			if (!self::createNewRevision(
					$object->array['id'], 
					$object->array['markdown'], 
					array_key_exists('revision_summary', $data) ? $data['revision_summary'] : false)
			) {
				Backend::addError('Could not add Content Revision');
				
			}
		}
		return true;
	}

	public static function hook_post_update($data, $object) {
		if ($object instanceof ContentObj && !$object->array['from_file']) {
			if (!self::createNewRevision(
					$object->array['id'], 
					$object->array['markdown'], 
					array_key_exists('revision_summary', $data) ? $data['revision_summary'] : false)
			) {
				Backend::addError('Could not add Content Revision');
				
			}
		}
		return true;
	}
	
	private static function createNewRevision($content_id, $body, $summary = false) {
		$summary = $summary ? $summary : 'New Revision created ' . date('Y-m-d H:i:s');
		$revision = new ContentRevisionObj();
		$data = array (
			'content_id' => $content_id,
			'markdown'   => $body,
			'summary'    => $summary,
		);
		return $revision->create($data);
	}

	public static function install(array $options = array()) {
		$toret = parent::install($options);
		
		$toret = Hook::add('update', 'post', __CLASS__,
							array('global' => true, 'description' => 'Create the following revision of a post after the content has been updated.')
						) && $toret;
		$toret = Hook::add('create', 'post', __CLASS__,
							array('global' => true, 'description' => 'Add the first revision of the content after the content has been created.')
						) && $toret;
		return $toret;
	}
}
