<?php
class Home extends AreaCtl {
	function html_index() {
		if (Render::checkTemplateFile('home.index.tpl.php')) {
			Backend::addContent(Render::renderFile('home.index.tpl.php', $results));
		} else {
			Backend::add('Sub Title', 'Welcome');
			Backend::addContent('Welcome to #Title#');
		}
		return true;
	}
	
	public static function install(array $options = array()) {
		$toret = parent::install($options);
		
		$toret = Permission::add('anonymous', 'index', 'home') && $toret;
		return $toret;
	}
}
