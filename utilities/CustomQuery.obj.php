<?php
class CustomQuery extends Query {
	function __construct($query, array $options = array()) {
		$this->setQuery($query, $options);
	}
}
