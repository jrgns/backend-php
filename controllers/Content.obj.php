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
class Content extends TableCtl {
	function html_display($content) {
		$toret = false;
		if ($content instanceof DBObject) {
			Backend::add('Sub Title', $content->array['title']);
			if ($content->array['from_file']) {
				$filename = 'content/static/' . $content->array['name'] . '.html';
				$template = 'content/' . $content->array['name'] . '.tpl.php';
				if (Render::checkTemplateFile($template)) {
					Controller::addContent(Render::renderFile($template));
					$toret = $content;
				} else if (file_exists(APP_FOLDER . '/' . $filename)) {
					Controller::addContent(file_get_contents(APP_FOLDER . '/' . $filename));
					$toret = $content;
				} else if (file_exists(BACKEND_FOLDER . '/' . $filename)) {
					Controller::addContent(file_get_contents(BACKEND_FOLDER . '/' . $filename));
					$toret = $content;
				//SITE FOLDER too?
				}
			} else {
				Controller::addContent($content->array['body']);
				$toret = $content;
			}
		}
		if (!$toret) {
			if (Controller::$debug) {
				$filename = 'content/' . Controller::$id . '.tpl.php';
				if (Render::checkTemplateFile($filename)) {
					Controller::addNotice('File available for content');
				}
			}
		}
		return $toret;
	}

	function action_display() {
		$toret = false;
		$id = Controller::$id ? Controller::$id : 'home';
		if (is_numeric($id)) {
			$toret = self::action_read();
		} else {
			$conds = array('`name` = :name');
			$params = array(':name' => $id);

			$toret = new ContentObj();
			list($query, $params) = $toret->getSelectSQL(array('parameters' => $params, 'conditions' => $conds));
			$toret->load(array('mode' => 'array', 'query' => $query, 'parameters' => $params));
		}

		if ($toret && !empty($toret->array)) {
			if (!$this->checkPermissions(array('subject_id' => $toret->array['id'], 'subject' => 'content'))) {
				Controller::whoops(array('title' => 'Permission Denied', 'message' => 'You do not have permission to display ' . $toret->array['title']));
				$toret = false;
			}
		} else {
			Controller::whoops(array('title' => 'Unknown Content', 'message' => 'The page you requested could not be found.'));
			$toret = false;
		}
		return $toret;
	}
	
	public static function install() {
		$toret = true;

		$permission = new PermissionObj();
		$toret = $permission->replace(array(
				'role'       => 'anonymous',
				'control'    => '100',
				'action'     => 'display',
				'subject'    => 'content',
				'subject_id' => 0,
				'system'     => 0,
				'active'     => 1,
			)
		) && $toret;
		return $toret;
	}

	/*
	 * @todo This isn't entirely accurate. If you want to create a random action_something, it need's to be
	 * added to the array below... This isn't optimal. Either get the array dynamically (get_class_methods) or refactor.
	 */
	public static function checkTuple($tuple) {
		if ($tuple['action'] == 'toggle') {
			$tuple['field'] = $tuple['count'];
			unset($tuple['count']);
		} else if (!$tuple['id'] && !in_array($tuple['action'], array('create', 'read', 'update', 'delete', 'list', 'display', 'toggle'))) {
			$tuple['id']     = $tuple['action'];
			$tuple['action'] = 'display';
		}
		return $tuple;
	}
}
