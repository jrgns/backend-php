<?php
if (!defined('WURFL_DIR')) {
	define('WURFL_DIR', Backend::getConfig('application.wurfl.dir'));
}
if (!defined('RESOURCES_DIR')) {
	define('RESOURCES_DIR', Backend::getConfig('application.wurfl.resources'));
}

require_once WURFL_DIR. 'WURFLManagerProvider.php';
class Wurfl extends AreaCtl {
	public function action_test() {
		$wurflConfigFile = RESOURCES_DIR . 'wurfl-config.xml';
		$wurflManager = WURFL_WURFLManagerProvider::getWURFLManager($wurflConfigFile);
		return false;
	}
}
