<?php
/**
 * The class file for OutgoingClick
 *
 * @copyright Copyright (c) 2011 Jade IT.
 * @author J Jurgens du Toit (Jade IT) - implementation
 * @package ModelFiles
 * Contributors:
 * @author J Jurgens du Toit (Jade IT) - implementation
 */

/**
 * This is the model definition for `jrgns_wuim`.`outgoing_clicks`
 *
 * @package Models
 */
class OutgoingClickObj extends DBObject {
	function __construct($meta = array(), array $options = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		$meta['database'] = 'jrgns_wuim';
		$meta['table'] = 'outgoing_clicks';
		$meta['name'] = 'OutgoingClick';
		$meta['fields'] = array(
			'id'          => array('field' => 'id', 'type' => 'primarykey', 'null' => false, 'default' => NULL),
			'user_id'     => array('field' => 'user_id', 'type' => 'current_user', 'null' => false, 'default' => NULL),
			'destination' => array('field' => 'destination', 'type' => 'string', 'null' => false, 'default' => NULL, 'string_size' => 1024),
			'origin'      => array('field' => 'origin', 'type' => 'previous_query', 'null' => false, 'default' => NULL),
			'ip'          => array('field' => 'ip', 'type' => 'ip_address', 'null' => false, 'default' => NULL),
			'added'       => array('field' => 'added', 'type' => 'lastmodified', 'null' => false),
		);

		$meta['keys'] = array(
		);
		return parent::__construct($meta, $options);
	}
	
	function validate($data, $action, $options = array()) {
		$toret = true;
		$data = parent::validate($data, $action, $options);
		return $toret ? $data : false;
	}
}

