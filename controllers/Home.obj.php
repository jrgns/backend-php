<?php
class Home extends AreaCtl {
	function html_index() {
		Backend::add('Sub Title', 'Welcome');
		Controller::addContent('Welcome to #Title#');
		return true;
	}
	
	public static function install(array $options = array()) {
		$toret = parent::install($options);
		
		$toret = Permission::add('anonymous', 'index', 'home') && $toret;
	}
}
