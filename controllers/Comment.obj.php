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
class Comment extends AreaCtl {
	function html_display($content) {
		$toret = false;
		if ($content instanceof DBObject) {
			Backend::add('Sub Title', $content->array['title']);
			if ($content->array['from_file']) {
				$filename = 'content/static/' . $content->array['name'] . '.html';
				$template = 'templates/content/' . $content->array['name'] . '.tpl.php';
				if (Render::checkTemplateFile($template)) {
					Controller::addContent(Render::renderFile($template));
					$toret = true;
				} else if (file_exists($filename)) {
					die('Content::Finish this');
					Controller::addContent(Render::renderFile($template));
					$toret = true;
				}
			} else {
				Controller::addContent($content->array['body']);
				$toret = true;
			}
		}
		if (!$toret) {
			if (Controller::$debug) {
				$filename = 'templates/content/' . Controller::$id . '.tpl.php';
				if (Render::checkTemplateFile($filename)) {
					Controller::addNotice('File available for content');
				}
			}
		}
	}

	function action_display() {
		$toret = false;
		$id = Controller::$id ? Controller::$id : 'home';
		if (is_numeric($id)) {
			$toret = new ContentObj(Controller::$id);
			$toret->load(array('mode' => 'array'));
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
}
