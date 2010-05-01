<?php
class BackendSearch extends TableCtl {
	public static function search(TableCtl $controller, $term) {
		$object = call_user_func(array(get_class($controller), 'getObject'));
		if ($object) {
			$table  = $object->getSource();
			$query  = new SelectQuery(__CLASS__);
			$query
				->filter('`table` = :table')
				->filter('`word` IN (\'' . trim(str_replace(' ', "','", $term)) . '\')')
				->order('`count` DESC, `sequence`');
			return $query->fetchAll(array(':table' => $table));
		}
		return false;
	}
	
	public static function doIndex(TableCtl $controller, $fields = array('content')) {
		if (!is_array($fields)) {
			$fields = array($fields);
		}
		$object = $controller->action_list('all', 0);
		if ($object->list) {
			foreach($fields as $field) {
				$first = current($object->list);
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
								if ($b_search->create($data)) {
									$result++;
								} else {
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
	}
	
	private static function filter($word, $count = null) {
		$word = preg_replace('/[^A-Za-z0-9_\-]/', '', $word);
		if (strlen($word) < Value::get('BackendSearch::MinimumWordLength', 4)) {
			return false;
		}
		return strtolower($word);
	}
}
