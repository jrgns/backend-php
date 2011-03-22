<?php
/**
 * The class file for OutgoingClick
 *
 * @copyright Copyright (c) 2011 Jade IT.
 * @author J Jurgens du Toit (Jade IT) - implementation
 * @package ControllerFiles
 * Contributors:
 * @author J Jurgens du Toit (Jade IT) - implementation
 */

/**
 * This is the controller for the table `jrgns_wuim`.`outgoing_clicks`.
 * It tracks outgoing links
 *
 * @package Controllers
 */
class OutgoingClick extends TableCtl {
	public function action_add($url) {
		$object = new OutgoingClickObj();
		$data   = array(
			'destination' => $url,
		);
		$object->create($data);
		Controller::redirect($url);
	}
	
	public static function checkParameters($parameters) {
		if (Controller::$action == 'add' && empty($parameters[0]) && array_key_exists('url', $_REQUEST)) {
			$parameters[0] = $_REQUEST['url'];
		}
		$parameters = parent::checkParameters($parameters);
		return $parameters;
	}
	
	/**
	 * Add the scripts to track outgoing links.
	 */
	public static function hook_output($to_print) {
		if (preg_match('/\sclass="[^"]*\s*outgoing_click\s*[^"]*"/i', $to_print)) {
			$to_print = preg_replace('/<\/body>/i', '<script src="' . SITE_LINK . '/scripts/jquery.js"></script><script src="' . SITE_LINK . '/scripts/outgoing_links.js"></script></body>', $to_print, 1);
		}
		return $to_print;
	}
	
	public static function install(array $options = array()) {
		$toret = parent::install($options);
		$toret = Hook::add('output', 'pre', get_called_class(), array('global' => 1)) && $toret;

		$toret = Permission::add('anonymous', 'add', get_called_class()) && $toret;
		$toret = Permission::add('authenticated', 'add', get_called_class()) && $toret;
		return $toret;
	}
}

