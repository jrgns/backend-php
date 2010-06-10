<?php
if (!defined('WURFL_DIR')) {
	define('WURFL_DIR', Backend::getConfig('application.wurfl.dir'));
}
if (!defined('RESOURCES_DIR')) {
	define('RESOURCES_DIR', Backend::getConfig('application.wurfl.resources'));
}
if (WURFL_DIR) {
	require_once WURFL_DIR. 'WURFLManagerProvider.php';
}

class Wurfl extends AreaCtl {
	private static $device = false;
	public function action_test() {
		$requestingDevice = self::getDevice();
		if ($requestingDevice) {
			$content = '<ul>';
			$content .= '<li>ID: ' . $requestingDevice->id . '</li>';
			$content .= '<li>Brand Name: ' . $requestingDevice->getCapability("brand_name") . '</li>';
			$content .= '<li>Model Name: ' . $requestingDevice->getCapability("model_name") . '</li>';
			$content .= '<li>Xhtml Preferred Markup: ' . $requestingDevice->getCapability("preferred_markup") . '</li>';
			$content .= '<li>Resolution Width: ' . $requestingDevice->getCapability("resolution_width") . '</li>';
			$content .= '<li>Resolution Height: ' . $requestingDevice->getCapability("resolution_height") . '</li>';
			$content .= '</ul>';
		} else {
			$content = '<p>Could not get device information</p>';
		}
		Backend::addContent($content);

		return false;
	}
	
	public static function getDevice() {
		if (self::$device) {
			return self::$device;
		}
		if (RESOURCES_DIR && class_exists('WURFL_WURFLManagerProvider')) {
			$wurflConfigFile = RESOURCES_DIR . 'wurfl-config.xml';
			try {
				$wurflManager    = WURFL_WURFLManagerProvider::getWURFLManager($wurflConfigFile);
				self::$device    = $wurflManager->getDeviceForHttpRequest($_SERVER);
			} catch (Exception $e) {
				if (Controller::$debug) {
					Backend::addError('Wurfl Error: ' . $e->getMessage());
				}
			}
			return self::$device;
		} else {
			Backend::addError('Could not find WURFL resources');
			return false;
		}
	}
	
	public static function hook_init() {
		/*$device = self::getDevice();
		if ($device) {
			var_dump($device->getCapability('mobile_browser')); die;
		}*/
	}
	
	public static function install(array $options = array()) {
		$toret = parent::install($options);
		//Hook::add('init', 'pre', __CLASS__, array('global' => 1)) && $toret;
		return $toret;
	}
}
