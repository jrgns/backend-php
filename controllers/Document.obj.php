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
class Document extends File {
	public function html_list($document) {
		Backend::add('Sub Title', $document->getMeta('name'));
		Backend::add('TabLinks', $this->getTabLinks(Controller::$action));
		Backend::add('Object', $document);
		Backend::addContent(Render::renderFile('document_list.tpl.php'));
	}	
}
