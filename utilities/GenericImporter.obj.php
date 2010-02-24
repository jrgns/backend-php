<?php
class GenericImporter {
	public static $last_error = false;

	public static function import($controller, $filename, $data) {
		self::$last_error = false;
		$fp = fopen($filename, 'r');
		if (!$fp)  {
			self::$last_error = 'Could not read uploaded file';
			return false;
		}
		$obj_name = get_class($controller) . 'Obj';
		if (!class_exists($obj_name, true)) {
			self::$last_error = 'The Object definition is missing';
			return false;
		}

		$Object = new $obj_name();
		$names = array_keys($Object->getMeta('fields'));
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
				$toret = $Object->create($line);
				if (!$toret) {
					break;
				}
				$count++;
			} else {
				self::$last_error = 'Number of imported fields does not match defined fields';
				return false;
			}
		}
		return $count;
	}
	
	public static function getLastError() {
		return self::$last_error;
	}
}
