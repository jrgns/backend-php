<?php
class BackendSearch extends TableCtl {
	public static function search(TableCtl $controller, $term, $filter = false) {
		$object = call_user_func(array(get_class($controller), 'getObject'));
		if ($object) {
			$terms  = preg_split('/[ ,]/', $term);
			$params = array_merge(array($object->getSource()), $terms);
			$query  = new SelectQuery(__CLASS__);
			$query
				->field('DISTINCT `' . $object->getMeta('table') . '`.*')
				->leftJoin(get_class($controller), '`' . $object->getMeta('table') . '`.`' . $object->getMeta('id_field') . '` = `table_id`')
				->filter('`table` = ?')
				->filter('`word` IN (' . implode(', ', array_fill(0, count($terms), '?')) . ')')
				->order('`count` DESC, `sequence`');
			if ($filter) {
				if (is_array($filter)) {
					foreach($filter as $one_fil) {
						$query->filter($one_fil);
					}
				} else {
					$query->filter($filter);
				}
			}
			$result = $query->fetchAll($params);
			return $result;
		}
		return false;
	}

	public static function doIndex(TableCtl $controller, $fields = array('content')) {
		if (!is_array($fields)) {
			$fields = array($fields);
		}
		$object = $controller->get_list('all', 0);
		$total = false;
		if ($object->list) {
			$total = 0;
			$first = current($object->list);
			foreach($fields as $field) {
				if (array_key_exists($field, $first)) {
					$result = 0;
					foreach($object->list as $row) {
						$info     = str_word_count($row[$field], 1);
						$counts   = array_count_values($info);
						$sequence = 0;
						foreach($counts as $word => $count) {
							unset($counts[$word]);
							if ($word = self::filter($word, $count)) {
								$data = array(
									'table'    => $object->getSource(),
									'table_id' => $row[$object->getMeta('id_field')],
									'word'     => $word,
									'count'    => $count,
									'sequence' => $sequence++,

								);
								$b_search = new BackendSearchObj();
								if ($b_search->replace($data)) {
									$result++;
									$total++;
								} else if ($b_search->error_msg != 'Error executing statement(1062)') {
									Backend::addError('Could not add ' . $word . ' to ' . get_class($controller) . '::' . $field . ' index');
								}
							}
						}
					}
					if ($result > 0) {
						Backend::addSuccess($result . ' words were indexed for ' . get_class($controller) . '::' . $field);
					}
				} else {
					Backend::addError($field . ' does not exist in ' . get_class($object));
				}
			}
		}
		return $total;
	}

	private static function filter($word, $count = null) {
		$word = preg_replace('/[^A-Za-z0-9_\-]/', '', $word);
		if (strlen($word) < ConfigValue::get('backend_search.MinimumWordLength', 4)) {
			return false;
		}
		return strtolower($word);
	}
}
