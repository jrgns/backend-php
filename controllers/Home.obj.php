<?php
class Home extends AreaCtl {
	function html_index() {
		Backend::add('Sub Title', 'Welcome');
		Controller::addContent('Welcome to the Backend');
		return true;
	}
}
