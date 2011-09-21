<?php
/**
 * The file that defines the JsonView class.
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
 * Default class to handle XmlView specific functions
 *
 * We use the PEAR XML_Serializer class to do this.
 */
class XmlView extends TextView {
	private static $ob_level = 0;

	function __construct() {
		require('XML/Serializer.php');

		parent::__construct();
		$this->mode      = 'xml';
		$this->mime_type = 'application/xml';
		$this->charset   = 'utf-8';
		self::$ob_level  = ob_get_level();
		ob_start();
	}

	public static function hook_output($to_print) {
		//Construct the object to output
		$object = new StdClass();
		$object->result  = $to_print;
		$object->error   = Backend::getError();
		$object->notice  = Backend::getNotice();
		$object->success = Backend::getSuccess();
		$object->content = Backend::getContent();
		$last = '';
		while (ob_get_level() > self::$ob_level) {
			//Ending the ob_start from __construct
			$last .= ob_get_clean();
		}
		$object->output  = $last;

		//Clean up
		Backend::setError();
		Backend::setNotice();
		Backend::setSuccess();

		//Return the XML
		$options = array(
			XML_SERIALIZER_OPTION_INDENT           => "\t",
			XML_SERIALIZER_OPTION_RETURN_RESULT    => true,
			XML_SERIALIZER_OPTION_DEFAULT_TAG      => 'item',
			XML_SERIALIZER_OPTION_XML_DECL_ENABLED => true,
			//This is a very awkward way of saying $this
			XML_SERIALIZER_OPTION_XML_ENCODING     => Controller::$view->charset,
			XML_SERIALIZER_OPTION_ROOT_NAME        => 'XmlResult',
			XML_SERIALIZER_OPTION_TYPEHINTS        => true,
		);
		$serializer = new XML_Serializer($options);
		if ($result = @$serializer->serialize($object)) {
			return $result;
		} else {
			return null;
		}
	}

	public static function install() {
		$toret = true;
		return $toret;
	}
}
