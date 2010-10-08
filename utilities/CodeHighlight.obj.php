<?php
/**
 * Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * @license http://www.eclipse.org/legal/epl-v10.html
 * @package Utilities
 *
 * Contributors:
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 */
class CodeHighlight {
	public static function execute($content) {
		if (preg_match_all('/<code>(.*?)<\/code>/s', $content, $matches)) {
			foreach($matches[1] as $key => $match) {
				if (substr($match, 0, strlen('&lt;?php')) == '&lt;?php') {
					$code = highlight_string(html_entity_decode($match), true);
					$content = str_replace($matches[0][$key], $code, $content);
				}
			}
		}
		return $content;
	}
}

