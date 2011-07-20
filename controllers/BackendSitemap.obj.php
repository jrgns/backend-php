<?php
class BackendSitemap extends AreaCtl {
	protected $areas = array();

	public function action_index() {
		return $this->action_generate();
	}

	public function action_generate() {
		$sitemaps = array();
		$components = Component::getActive();
		foreach($components as $component) {
			if ($filename = $this->generateSitemap($component['name'])) {
				$sitemaps[] = array('location' => SITE_LINK . basename($filename));
			}
		}
		if (count($sitemaps)) {
			$fp = fopen(WEB_FOLDER . '/sitemap_index.xml', 'w');
			if (!$fp) {
				Backend::addError('Could not generate sitemap index: Could not open file');
				return false;
			}
			fwrite($fp, Render::file('backend_sitemap/index.tpl.php', array('sitemaps' => $sitemaps)));
			return WEB_FOLDER . '/sitemap_index.xml';
		}
		return false;
	}

	public function notifyGoogle($url) {
		$data = array('sitemap' => $url);
		return curl_request('www.google.com/webmasters/tools/ping', $data);
	}

	private function generateSitemap($component) {
		if (!method_exists($component, 'getSitemap')) {
			return false;
		}
		if (!Component::isActive($component)) {
			Backend::addError('Could not generate sitemap: Component inactive. (' . $component . ')');
			return false;
		}
		$controller = new $component();
		if (!($controller instanceof TableCtl)) {
			Backend::addError('Could not generate sitemap: Invalid Area. (' . $component . ')');
			return false;
		}
		$filename = WEB_FOLDER . '/sitemap_' . $component . '.xml';
		if (file_exists($filename) && !is_writable($filename)) {
			Backend::addError('Could not generate sitemap: Cannot open sitemap file. (' . $filename . ')');
			return false;
		}
		$fp = fopen($filename, 'w');
		if (!$fp) {
			Backend::addError('Could not generate sitemap: Could not open sitemap file. (' . $component . ')');
			return false;
		}
		$sitemap = $controller->getSitemap();
		if (count($sitemap) == 2 && array_key_exists('list', $sitemap) && array_key_exists('options', $sitemap)) {
			$list    = $sitemap['list'];
			$options = $sitemap['options'];
		} else {
			$list    = $sitemap;
			$options = array();
		}
		if (!$list) {
			Backend::addError('Could not generate sitemap: Could not generate list. (' . $component . ')');
			return false;
		}

		if (Controller::$debug) {
			Backend::addNotice('Generating sitemap for ' . $component . ' at ' . WEB_FOLDER . '/sitemap_' . $component . '.xml found at ' . SITE_LINK . basename($filename));
		}

		$last_date = 0;
		$links = array();
		//Compile Links
		foreach($list as $row) {
			$last_date = strtotime($row['modified']) > $last_date ? strtotime($row['modified']) : $last_date;
			$id        = array_key_exists('name', $row) ? $row['name'] : $row[$$object->getMeta('id')];
			if (Value::get('CleanURLs', false)) {
				$url = SITE_LINK . class_for_url($component) . '/' . $id;
			} else {
				$url = SITE_LINK . '?q=' . class_for_url($component) . '/' . $id;
			}
			$row['url'] = $url;
			$row = array_merge($row, $options);
			$links[] = $row;
		}
		//Add link to area
		//TODO Make this configurable
		if (Value::get('CleanURLs', false)) {
			$url = SITE_LINK . class_for_url($component);
		} else {
			$url = SITE_LINK . '?q=' . class_for_url($component);
		}
		$link = array('url' => $url, 'modified' => date('Y-m-d H:i:s', $last_date));
		$link['priority']  = array_key_exists('area_priority', $options) ? $options['area_priority'] : 0.8;
		$link['frequency'] = array_key_exists('frequency', $options)     ? $options['frequency']     : 'daily';
		$links[] = $link;

		fwrite($fp, Render::file('backend_sitemap/sitemap.tpl.php', array('links' => $links)));
		return $filename;
	}

	public function weekly(array $options = array()) {
		$url = $this->action_generate();
		if ($url && SITE_STATE == 'production') {
			$this->notifyGoogle($url);
		}
		return true;
	}
}
