<?php
/**
 * The file that defines the CsvView class.
 *
 * @author J Jurgens du Toit (JadeIT cc) <jurgens.dutoit@gmail.com> - initial API and implementation
 * @copyright Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available {@link http://www.eclipse.org/legal/epl-v10.html here}
 * @license http://www.eclipse.org/legal/epl-v10.html Eclipse Public License v1.0
 * @package View
 */
 
/**
 * Default class to handle CsvView specific functions
 */
class CsvView extends View {
	function __construct() {
		parent::__construct();
		$this->mode = 'csv';
		$this->mime_type = 'application/csv';
	}
	
	public static function hook_output($to_print) {
		$filename = Backend::get('CsvFilename', false);
		if (!$filename) {
			$filename = class_name(Controller::$area) . class_name(Controller::$action);
			if (Controller::$action == 'read' && !empty(Controller::$parameters[0])) {
				$filename .= Controller::$parameters[0];
			}
		}
		if (!Controller::$debug) {
			header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
			header('Pragma: no-cache');
			header('Expires: 0');
		}
		
		switch (true) {
		//Output SelectQuery as CSV
		case $to_print instanceof SelectQuery:
		case $to_print instanceof CustomQuery:
			return self::output_query($to_print);
			break;
		//Output Array of Arrays as CSV
		case is_array($to_print):
			if (!$fp = fopen('php://temp', 'r+')) {
				Backend::addError('Could not open output file for CSV output');
				return '';
			}
			$tmp = reset($to_print);
			$first = false;
			foreach($to_print as $row) {
				if (!is_array($row)) {
					$row = array($row);
				}
				set_time_limit(30);
				if (!$first) {
					//fputcsv($fp, array_keys($row));
					$first = true;
				}
				fputcsv($fp, $row);
			}
			//Get the file contents
			rewind($fp);
			ob_start();
			fpassthru($fp);
			fclose($fp);
			return ob_get_clean();
			break;
		//Output a specified file as CSV
		case is_string($to_print):
			if (is_readable($to_print)) {
				$fp = fopen($to_print, 'r');
				fpassthru($fp);
				break;
			}
		//Output a string as CSV
		default:
			return $to_print;
			break;
		}
	}
	
	private static function output_query($query) {
		if (!$fp = fopen('php://temp', 'r+')) {
			Backend::addError('Could not open temporary file for CSV output');
			return '';
		}
		$first = false;
		while($row = $query->fetchAssoc()) {
			set_time_limit(30);
			if (!$first) {
				fputcsv($fp, array_keys($row));
				$first = true;
			}
			fputcsv($fp, $row);
		}
		//Get the file contents
		rewind($fp);
		ob_start();
		fpassthru($fp);
		fclose($fp);
		return ob_get_clean();
	}

	public function output_list($data) {
		if (!$fp = fopen('php://temp', 'r+')) {
			Backend::addError('Could not open temporary file for CSV output');
			return '';
		}
		
		if (!is_array($data)) {
			Backend::addError('Invalid Result');
		}
		$data = array_filter($data, 'is_array');
		$first = reset($data);
		fputcsv($fp, array_keys($first));
		foreach($data as $line) {
			fputcsv($fp, $line);
		}
		rewind($fp);
		ob_start();
		fpassthru($fp);
		fclose($fp);
		return ob_get_clean();
	}

	public function output_read($data) {
		if (!$fp = fopen('php://temp', 'r+')) {
			Backend::addError('Could not open temporary file for CSV output');
			return '';
		}
		fputcsv($fp, $data);
		rewind($fp);
		ob_start();
		fpassthru($fp);
		fclose($fp);
		return ob_get_clean();
	}

	public static function install() {
		$toret = true;
		Hook::add('output', 'pre', __CLASS__, array('global' => 1, 'mode' => 'csv')) && $toret;
		return $toret;
	}
}

