<?php
class Home extends AreaCtl {
	function html_index() {
		if (Render::checkTemplateFile('home.index.tpl.php')) {
			Backend::addContent(Render::renderFile('home.index.tpl.php', $results));
		} else {
			Backend::add('Sub Title', 'Welcome');
			Backend::addContent('<h3>Welcome to #Title#</h3><p>The code for this URL is in the Home Controller</p>');
		}
		return true;
	}
	
	public function html_error() {
		Backend::addContent('Something Went Wrong');
	}
	
	public static function install(array $options = array()) {
		$result = parent::install($options);
		if (!Backend::getDB('default')) {
			return $result;
		}
		
		$result = Permission::add('anonymous', 'index', 'home') && $result;
		$result = Permission::add('anonymous', 'error', 'home') && $result;

		//TODO Keep this here until we have role hierarchies
		$result = Permission::add('authenticated', 'index', 'home') && $result;
		$result = Permission::add('authenticated', 'error', 'home') && $result;
		return $result;
	}
}
