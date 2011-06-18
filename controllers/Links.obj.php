<?php
class Links extends AreaCtl {
	private static $links = array();

	public static function get($name = 'primary') {
		if (!array_key_exists($name, self::$links)) {
			self::$links[$name] = array();
		}
		return self::$links[$name];
	}
	
	private static function set($name, $links) {
		self::$links[$name] = $links;
	}
	
	public static function add($text, $href, $options = array()) {
		if (is_string($options)) {
			$options = array('name' => $options);
		}
		$name = array_key_exists('name', $options) ? $options['name'] : 'primary';
		return self::append(array('text' => $text, 'href' => $href), $name);
	}

	public static function append($link, $name = 'primary') {
		$existing_links = self::get($name);
		//Multiple Links
		if (is_array(current($link))) {
			$existing_links += $link;
		} else {
			$existing_links[] = $link;
		}
		self::set($name, $existing_links);
		return self::get($name);
	}
	
	public static function render($link) {
		if (empty($link['href'])) {
			echo $link['text'];
		} else {
			echo '<a href="', $link['href'], '">', $link['text'], '</a>';
		}
	}

	function html_index() {
		Backend::add('Sub Title', 'Links');
		return true;
	}
}
