<?php
class GenericImporter {
	protected static $error_msg = false;

	public static function import($controller, $filename, $data, array $options = array()) {
		self::$error_msg = false;
		$fp = fopen($filename, 'r');
		if (!$fp)  {
			self::$error_msg = 'Could not read uploaded file';
			return false;
		}
		$obj_name = get_class($controller) . 'Obj';
		if (!class_exists($obj_name, true)) {
			self::$error_msg = 'The Object definition is missing';
			return false;
		}
		
		$Object = new $obj_name();
		$headers = array_key_exists('headers', $options) ? $options['headers'] : true;
		if ($headers === true) { //First line is the headers
			$names = array_filter(fgetcsv($fp));
		} else if (is_array($headers)) { //We were given the headers
			$names = $headers;
		} else {
			$names = array_keys($Object->getMeta('fields'));
		}
		if (is_array($data)) {
			$names = array_merge($names, array_keys($data));
		}
		$name_count = count($names);
		$count = 0;
		while(($line = fgetcsv($fp)) !== false) {
			if (is_array($data)) {
				$line = array_merge($line, $data);
			}
			if ($name_count == count($line)) {
				$line = array_combine($names, $line);
				//Return false from preLineImport if you dont want the line to be imported
				if (is_callable(array($controller, 'preLineImport'))) {
					$n_line = $controller->preLineImport($line);
				} else {
					$n_line = $line;
				}
				if (!$n_line) {
					continue;
				}
				$toret = $Object->create($n_line);
				if (!$toret) {
					break;
				}
				$count++;
				if (is_callable(array($controller, 'postLineImport'))) {
					//Return false from postLineImport if you want the import process to stop
					if ($controller->postLineImport($Object, $n_line) === false) {
						break;
					}
				}
			} else {
				self::$error_msg = 'Number of imported fields does not match defined fields';
				return false;
			}
		}
		return $count;
	}
	
	public static function getLastError() {
		return self::$error_msg;
	}
}
