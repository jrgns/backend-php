<?php
class CustomQuery extends Query {
	function __construct($query, array $options = array()) {
		$this->setQuery($query, $options);
	}
	
	function getCount($parameters) {
		$this->buildTable();
		$count_query = clone($this);
		$count_query
			->setOrder(array())
			->setGroup(array());
		$count_query = new CustomQuery(preg_replace(REGEX_MAKE_COUNT_QUERY, '$1 COUNT(*) $3', $count_query));
		return $count_query->fetchColumn($parameters);
	}
}
