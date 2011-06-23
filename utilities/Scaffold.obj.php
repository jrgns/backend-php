<?php
class Scaffold {
	private static $variables;
	private static $connection;
	private static $destination;

	public static function generate(array $variables, $connection, $destination) {
		self::$variables   = $variables;
		self::$connection  = $connection;
		self::$destination = $destination;
		if (!self::controller()) {
			return false;
		}
		if (!self::model()) {
			return false;
		}
		if (!self::views()) {
			return false;
		}
		return true;
	}

	public static function controller() {
		$year = date('Y');
		extract(self::$variables);
		if (empty($company)) {
			$copyright_owner = $author;
		} else {
			$copyright_owner = $company;
			$company = ' (' . $company . ')';
		}
		$file = <<< END
<?php
/**
 * The class file for $class_name
 *
 * @copyright Copyright (c) $year $copyright_owner.
 * @author $author$company - initial implementation
 * @package ControllerFiles
 * Contributors:
 * @author $author$company - initial implementation
 */

/**
 * This is the controller for the table `$db_name`.`$table_name`.
 *
 * @package Controllers
 */
class $class_name extends TableCtl {
	public static function install(array \$options = array()) {
		\$result = parent::install(\$options);
		return \$result;
	}
}

END;
		return file_put_contents(self::$destination . '/controllers/' . $class_name . '.obj.php', $file);
	}

	public static function model() {
		$year = date('Y');
		extract(self::$variables);
		if (empty($company)) {
			$copyright_owner = $author;
		} else {
			$copyright_owner = $company;
			$company = ' (' . $company . ')';
		}
		$file = <<< END
<?php
/**
 * The class file for {$class_name}Obj
 *
 * @copyright Copyright (c) $year $copyright_owner.
 * @author $author$company - initial implementation
 * @package ModelFiles
 * Contributors:
 * @author $author$company - initial implementation
 */

/**
 * This is the model definition for `$db_name`.`$table_name`
 *
 * @package Models
 */
class {$class_name}Obj extends DBObject {
	function __construct(\$meta = array(), array \$options = array()) {
		if (!is_array(\$meta) && is_numeric(\$meta)) {
			\$meta = array('id' => \$meta);
		}
		\$meta['database'] = '$db_name';
		\$meta['table'] = '$table_name';
		\$meta['name'] = '$class_name';
		\$meta['fields'] = array(

END;
		$query = new CustomQuery("SHOW COLUMNS FROM `$db_name`.`$table_name`");
		$name_length = 0;
		$fields = array();
		while($row = $query->fetchAssoc()) {
			$definition = array_change_key_case($row);
			$definition['null'] = strtolower($row['Null']) == 'yes';
			$definition['type'] = 'string';
			$name_length = max($name_length, strlen($definition['field']));
			unset($definition['key'], $definition['extra']);
			switch(true) {
			case strtolower($row['Key']) == 'pri':
				$definition['type'] = 'primarykey';
				break;
			case strtolower(substr($row['Type'], 0, 7)) == 'varchar':
				$length = sscanf($row['Type'], 'varchar(%d)');
				if ($length && count($length)) {
					$length = reset($length);
					$definition['string_size'] = $length;
				}
				break;
			case strtolower(substr($row['Type'], 0, 4)) == 'char':
				$length = sscanf($row['Type'], 'char(%d)');
				if ($length && count($length)) {
					$length = reset($length);
					$definition['string_size'] = $length;
				}
				break;
			case strtolower(substr($row['Type'], -4)) == 'text':
				$definition['type'] = 'text';
				break;
			case strtolower($row['Type']) == 'tinyint(1)':
				$definition['type'] = 'boolean';
				break;
			case strtolower(substr($row['Type'], 0, 3)) == 'int':
			case strtolower(substr($row['Type'], 0, 7)) == 'tinyint':
			case strtolower(substr($row['Type'], 0, 8)) == 'smallint':
			case strtolower(substr($row['Type'], 0, 6)) == 'bigint':
			case strtolower(substr($row['Type'], 0, 5)) == 'float':
			case strtolower(substr($row['Type'], 0, 7)) == 'decimal':
				$definition['type'] = 'number';
				break;
			case strtolower($row['Type']) == 'timestamp' && strtolower($row['Default']) == 'current_timestamp':
				$definition['type'] = 'lastmodified';
				unset($definition['default']);
				break;
			case strtolower($row['Type']) == 'datetime' && in_array(strtolower($row['Field']), array('added', 'dateadded', 'datetimeadded')):
				$definition['type'] = 'dateadded';
				break;
			case strtolower($row['Type']) == 'date':
			case strtolower($row['Type']) == 'datetime':
			case strtolower($row['Type']) == 'time':
				$definition['type'] = strtolower($row['Type']);
				break;
			default:
				var_dump($row); die;
				break;
			}

			if (!$definition['null']) {
				if (!in_array($definition['type'], array('primarykey', 'dateadded', 'lastmodified'))) {
					$definition['required'] = true;
				}
				if (is_null($definition['default'])) {
					unset($definition['default']);
				}
			}

			$fields[$row['Field']] = $definition;
		}
		foreach($fields as $name => $definition) {
			$tmp = array();
			foreach($definition as $key => $value) {
				$tmp[] = "'$key' => " . var_export($value, true);
			}
			$definition = 'array(' . implode(', ', $tmp) . ')';

			$padding = str_repeat(' ', $name_length - strlen($name));
			$file .= "			'$name'$padding => $definition," . PHP_EOL;
		}
		$file .= <<< END
		);

		\$meta['keys'] = array(

END;
//The field already picks up the primary key
		$query = new CustomQuery("SHOW KEYS FROM `$db_name`.`$table_name` WHERE `Key_name` != 'PRIMARY'");
		$keys = array();
		while($row = $query->fetchAssoc()) {
			if (!array_key_exists($row['Key_name'], $keys)) {
				$keys[$row['Key_name']] = array('fields' => array(), 'type' => '');
			}
			$type = empty($row['Non_unique']) ? 'unique' : 'index';
			switch(true) {
			default:
				$keys[$row['Key_name']]['fields'][] = $row['Column_name'];
				$keys[$row['Key_name']]['type']     = $type;
				break;
			}
		}
		foreach($keys as $name => $key) {
			$file .= "			'$name' => array(" . PHP_EOL;
			$file .= "				'type'   => '{$key['type']}'," . PHP_EOL;
			$file .= "				'fields' => array('" . implode("', '", $key['fields']) . "')," . PHP_EOL;
			$file .= "			)," . PHP_EOL;
		}
		$file .= <<< END
		);
		return parent::__construct(\$meta, \$options);
	}

	function validate(\$data, \$action, \$options = array()) {
		\$result = true;
		\$data = parent::validate(\$data, \$action, \$options);
		return \$result ? \$data : false;
	}
}
END;
		return file_put_contents(self::$destination . '/models/' . $class_name . 'Obj.obj.php', $file);
	}

	public static function views() {
		return true;
	}
}
