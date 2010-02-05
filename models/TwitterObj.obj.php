<?php
class TwitterObj extends DBObject {
	function __construct($meta = array()) {
		if (!is_array($meta) && is_numeric($meta)) {
			$meta = array('id' => $meta);
		}
		$meta['table'] = 'twitter';
		$meta['name'] = 'Twitter';
		$meta['fields'] = array(
			'id' => 'primarykey',
			'user_id' => 'current_user',
			'twitter_id' => 'large_integer',
			'screen_name' => 'string',
			'oauth_token' => 'string',
			'oauth_secret' => 'string',
			'active' => 'boolean',
			'modified' => 'lastmodified',
			'added' => 'dateadded',
		);
		return parent::__construct($meta);
	}
}
