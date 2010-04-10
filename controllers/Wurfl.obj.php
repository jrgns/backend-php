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
		$wurflManager    = WURFL_WURFLManagerProvider::getWURFLManager($wurflConfigFile);
		
		$requestingDevice = $wurflManager->getDeviceForHttpRequest($_SERVER);
		
		$content = '<ul>';
		$content .= '<li>ID: ' . $requestingDevice->id . '</li>';
		$content .= '<li>Brand Name: ' . $requestingDevice->getCapability("brand_name") . '</li>';
		$content .= '<li>Model Name: ' . $requestingDevice->getCapability("model_name") . '</li>';
		$content .= '<li>Xhtml Preferred Markup: ' . $requestingDevice->getCapability("preferred_markup") . '</li>';
		$content .= '<li>Resolution Width: ' . $requestingDevice->getCapability("resolution_width") . '</li>';
		$content .= '<li>Resolution Height: ' . $requestingDevice->getCapability("resolution_height") . '</li>';
		$content .= '</ul>';
		Backend::addContent($content);

		return false;
	}
}
