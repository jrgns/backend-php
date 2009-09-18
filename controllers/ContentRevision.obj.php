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
	public static function hook_post_create($data, $object) {
		if ($object instanceof ContentObj && !$object->array['from_file']) {
			if (!self::createNewRevision(
					$object->array['id'], 
					$object->array['markdown'], 
					array_key_exists('revision_summary', $data) ? $data['revision_summary'] : false)
			) {
				Controller::addError('Could not add Content Revision');
				
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
				Controller::addError('Could not add Content Revision');
				
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

	public static function install() {
		$toret = true;
		$hook = new HookObj();
		$toret = $hook->replace(array(
				'name'        => 'ContentRevision Post Update',
				'description' => 'Create the following revision of a post after the content has been updated',
				'mode'        => '*',
				'type'        => 'post',
				'hook'        => 'update',
				'class'       => 'ContentRevision',
				'method'      => 'hook_post_update',
				'sequence'    => 0,
			)
		) && $toret;
		$toret = $hook->replace(array(
				'name'        => 'ContentRevision Post Create',
				'description' => 'Add the first revision of the content after the content has been created ',
				'mode'        => '*',
				'type'        => 'post',
				'hook'        => 'create',
				'class'       => 'ContentRevision',
				'method'      => 'hook_post_create',
				'sequence'    => 0,
			)
		) && $toret;
		return $toret;
	}
}
