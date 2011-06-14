public static function define_example() {
	return array(
		'description' => 'The description of the function',
		//All required parameters defined in the function signature
		'parameters'  => array(
			'first_param' => array(
				'description' => 'This is the first paramater',
				'type'        => 'string',
				'default'     => 'The default string. It can be omitted',
			),
		),
		'example'     => '<code><?php echo SITE_LINK ?>?q=area/example/Something</code>',
		//Extra parameters. Can not be omitted
		'required' => array(
			'req_param' => array(
				'description' => 'A required parameter.',
				'type'        => 'boolean',
			),
		),
		//All optional parameters. Can be omitted without causing errors
		'optional' => array(
			'opt_param' => array(
				'description' => 'An optional parameter.',
				'type'        => 'numeric',
				'default'     => 15,
			),
			'range_param' => array(
				'description' => 'An optional parameter which should fall within a range.',
				'type'        => 'string',
				'default'     => 'jannie',
				'range'       => array('jannie', 'sannie', 'pietie'),
			),
		),
		//The return value
		'return' => array(
			'description' => 'What will be returned.',
			'type'        => 'boolean',
		),
	);
}

public function action_example($first_param) {
	$options = API::extract(self::define_example());
	//$options['opt_param'] == 15 by default
	//$options['range_param'] == 'jannie' by default
	return true;
}
