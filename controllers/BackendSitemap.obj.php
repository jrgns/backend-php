<?php
class BackendSitemap extends AreaCtl {
	protected $areas = array();
	
	public function action_index() {
		return $this->action_generate();
	}

	public function action_generate() {
		$sitemaps = array();
		foreach($this->areas as $area => $options) {
			if ($filename = $this->generateSitemap($area, $options)) {
				$sitemaps[] = array('location' => SITE_LINK . basename($filename));
			}
		}
		if (count($sitemaps)) {
			$fp = fopen(WEB_FOLDER . '/sitemap_index.xml', 'w');
			if (!$fp) {
				Backend::addError('Could not generate sitemap index: Could not open file');
				return false;
			}
			fwrite($fp, Render::renderFile('sitemap_index.tpl.php', array('sitemaps' => $sitemaps)));
			return WEB_FOLDER . '/sitemap_index.xml';
		}
		return false;
	}
	
	public function notifyGoogle($url) {
		$data = array('sitemap' => $url);
		return curl_request('www.google.com/webmasters/tools/ping', $data);
	}
	
	private function generateSitemap($area, $options) {
		$class = class_name($area);
		if (!Component::isActive(class_name($area))) {
			Backend::addError('Could not generate sitemap: Component inactive. (' . $area . ')');
			return false;
		}
		$controller = new $class();
		if (!($controller instanceof TableCtl)) {
			Backend::addError('Could not generate sitemap: Invalid Area. (' . $area . ')');
			return false;
		}
		$object = $controller->action_list('all', false);
		if (!$object->list) {
			Backend::addError('Could not generate sitemap: Could not generate list. (' . $area . ')');
			return false;
		}

		$filename = WEB_FOLDER . '/sitemap_' . $area . '.xml';
		$fp = fopen($filename, 'w');
		if (!$fp) {
			Backend::addError('Could not generate sitemap: Could not open sitemap file. (' . $area . ')');
			return false;
		}

		if (Controller::$debug) {
			Backend::addNotice('Generating sitemap for ' . $area . ' at ' . WEB_FOLDER . '/sitemap_' . $area . '.xml found at ' . SITE_LINK . basename($filename));
		}

		$last_date = 0;
		//TODO Why is this here, and where's its ending ob_ call?
		ob_start();
		fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL);
		foreach($object->list as $row) {
			$last_date = strtotime($row['modified']) > $last_date ? strtotime($row['modified']) : $last_date;
			if (Value::get('clean_urls', false)) {
				$url = SITE_LINK . class_for_url($object) . '/' . $row['id'];
			} else {
				$url = SITE_LINK . '?q=' . class_for_url($object) . '/' . $row['id'];
			}
			$row['url'] = $url;
			fwrite($fp, Render::renderFile('sitemap_link.tpl.php', array('link' => $row)));
		}
		if (Value::get('clean_urls', false)) {
			$url = SITE_LINK . class_for_url($object) . '/';
		} else {
			$url = SITE_LINK . '?q=' . class_for_url($object) . '/';
		}
		$link = array('url' => $url, 'modified' => date('Y-m-d H:i:s', $last_date), 'priority' => 0.8, 'frequency' => 'daily');
		fwrite($fp, Render::renderFile('sitemap_link.tpl.php', array('link' => $link)));
		fwrite($fp, '</urlset>' . PHP_EOL);
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
