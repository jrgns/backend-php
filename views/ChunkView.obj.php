<?php
/**
 * The file that defines the ChunkView class.
 *
 * @author J Jurgens du Toit (JadeIT cc) <jurgens.dutoit@gmail.com> - initial API and implementation
 * @copyright Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available {@link http://www.eclipse.org/legal/epl-v10.html here}
 * @license http://www.eclipse.org/legal/epl-v10.html Eclipse Public License v1.0
 * @package View
 */
 
/**
 * Default class to handle ChunkView specific functions
 *
 * @todo It's probable that some of HtmlView's functions are superflous, override and incapacitate...
 * @todo Maybe send information such as scripts and styles in the headers?
 */
class ChunkView extends HtmlView {
	public static function hook_output($to_print) {
		Backend::add('BackendErrors', Backend::getError());
		Backend::add('BackendSuccess', Backend::getSuccess());
		Backend::add('BackendNotices', Backend::getNotice());
		Backend::setError();
		Backend::setSuccess();
		Backend::setNotice();

		$to_print = Render::renderFile('styles.area.tpl.php');
		$to_print .= Render::renderFile('maincontent.tpl.php');
		$to_print .= Render::renderFile('scripts.tpl.php');

		$to_print = HtmlView::addLastContent($to_print);
		$to_print = HtmlView::replace($to_print);
		$to_print = HtmlView::rewriteLinks($to_print);
		$to_print = HtmlView::addLinks($to_print);
		$to_print = HtmlView::formsAcceptCharset($to_print);

		if (Value::get('admin_installed', false)) {
			$BEFilter = new BEFilterObj();
			$BEFilter->load();
			$filters = $BEFilter->list ? $BEFilter->list : array();
		
			foreach($filters as $row) {
				if (class_exists($row['class'], true) && is_callable(array($row['class'], $row['function']))) {
					$to_print = call_user_func(array($row['class'], $row['function']), $to_print);
				}
			}
		}

		return $to_print;
	}
	
	public static function hook_display($results, $controller) {
		return $results;
	}
	
	/**
	 * This function adds all styles and scripts to the HTML. It also retrieves primary and secondary links from the App
	 *
	 */
	public static function hook_post_display($data, $controller) {
		Backend::add('Styles', array_unique(array_filter(Backend::getStyles())));
		Backend::add('Scripts', array_unique(array_filter(Backend::getScripts())));
		return $data;
	}
}

