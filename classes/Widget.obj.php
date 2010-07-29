<?php
class Widget {
	protected $name = 'Widget';
	
	function __construct($name = false) {
		$this->name = $name ? $name : get_class($this);
	}
	
	function getName() {
		return $this->name;
	}
	
	function __toString() {
		echo 'Implement __toString of ' . $this->name;
	}
}
