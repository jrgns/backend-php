<?php
class BackendSitemap extends AreaCtl {
	protected $areas = array();

	public function action_generate() {
		$sitemaps = array();
		foreach($this->areas as $area => $options) {
			if ($filename = $this->generateSitemap($area, $options)) {
				$sitemaps[] = array('location' => $filename);
			}
		}
		if (count($sitemaps)) {
			$fp = fopen(SITE_FOLDER . '/sitemap_index.xml', 'w');
			if (!$fp) {
				Backend::addError('Could not generate sitemap index: Could not open file');
				return false;
			}
			fwrite($fp, Render::renderFile('sitemap_index.tpl.php', array('sitemaps' => $sitemaps)));
		}
		return true;
	}
	
	private function generateSitemap($area, $options) {
		$class = class_name($area) . 'Obj';
		if (!(class_exists($class, true) && Component::isActive(class_name($area)))) {
			Backend::addError('Could not generate sitemap: Component missing or inactive. (' . $area . ')');
			return false;
		}
		$object = new $class();
		if (!($object instanceof DBObject)) {
			Backend::addError('Could not generate sitemap: Invalid Area. (' . $area . ')');
			return false;
		}
		$object->loadList();
		if (!$object->list) {
			Backend::addError('Could not generate sitemap: Could not generate list. (' . $area . ')');
			return false;
		}
		$filename = SITE_FOLDER . '/sitemap_' . $area . '.xml';
		$fp = fopen($filename, 'w');
		if (!$fp) {
			Backend::addError('Could not generate sitemap: Could not open sitemap file. (' . $area . ')');
			return false;
		}
		ob_start();
		fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL);
		foreach($object->list as $row) {
			$url = SITE_LINK;
			if (Value::get('clean_urls', false)) {
				$url .= '' . class_for_url($object) . '/' . $row['id'];
			} else {
				$url .= '?q=' . class_for_url($object) . '/' . $row['id'];
			}
			$row['url'] = $url;
			fwrite($fp, Render::renderFile('sitemap_link.tpl.php', array('link' => $row)));
		}
		fwrite($fp, '</urlset>' . PHP_EOL);
		return $filename;
	}
}
