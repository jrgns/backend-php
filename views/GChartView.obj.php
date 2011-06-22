<?php
/**
 * The file that defines the GChartView class.
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
 * Default class to handle GChartView specific functions
 */
class GChartView extends View {
	public static $url = 'https://chart.googleapis.com/chart';
	public static $colors = array('FF0000', '00FF00', '0000FF');

	function __construct() {
		$this->mode = 'gchart';
		$this->mime_type = 'image/png';
	}
	
	private static function simple_line($output, &$params) {
		$data = $output['data'];
		if (array_depth($data) == 1) {
			$min  = min(array_merge(array(0), $data));
			$max  = max($data);
		} else {
			$min  = min(array_merge(array(0), reset($data)));
			$max  = count(reset($data)) ? max(reset($data)) : 0;
		}
		$params['cht'] = 'lc';
		if (array_depth($data) == 1) {
			$params['chd'] = 't:' . implode(',', $data);
		} else {
			$value = array();
			foreach($data as $one_data) {
				$value[] = implode(',', $one_data);
			}
			$params['chd'] = 't:' . implode('|', $value);
		}
		$params['chds'] = $min . ',' . $max;
		$params['chxr'] = '1,' . $min . ',' . $max;
		$params['chxt'] = 'x,y';
		$params['chs']  = array_key_exists('size', $output) ? $output['size'] : '400x250';
		if (array_key_exists('legend', $output)) {
			$legend = $output['legend'];
			if (is_array($legend)) {
				$legend = '0:|' . implode('|', $legend);
			}
		} else if (array_depth($data) == 1) {
			//use this instead of array_keys to keep the order
			$values = array();
			foreach($data as $key => $value) {
				$values[] = $key;
			}
			$legend = '0:|' . implode('|', $values);
		} else {
			//use this instead of array_keys to keep the order
			$values = array();
			foreach(reset($data) as $key => $value) {
				$values[] = $key;
			}
			$legend = '0:|' . implode('|', $values);
		}
		if ($legend) {
			$params['chxl'] = $legend;
		}
		if (array_depth($data) > 1) {
			if (array_key_exists('colors', $output)) {
				$params['chco'] = implode(',', $output['colors']);
			} else {
				$params['chco'] = implode(',', self::$colors);
			}
			if (array_key_exists('series', $output)) {
				$params['chdl'] = implode('|', $output['series']);
			}
		}
		
		return self::$url . '?' . http_build_query($params);
	}
	
	public static function hook_output($output) {
		//TODO Attach HTTP Error codes and descriptions to these errors
		if (!is_array($output)) {
			BackendError::add('Google Chart Error', 'Invalid Output');
			return false;
		}
		$type = array_key_exists('type', $output) ? $output['type'] : Backend::get('ChartType', 'simple_line');
		if (!method_exists('GChartView', $type)) {
			BackendError::add('Google Chart Error', 'Invalid Chart Type');
			return false;
		}

		if (!array_key_exists('data', $output)) {
			$output = array('data' => $output);
		}
		if (!is_array($output['data']) || !count($output['data'])) {
			BackendError::add('Google Chart Error', 'Invalid Output Data');
			return false;
		}

		$params = array();
		$title  = array_key_exists('title', $output) ? $output['title'] : Backend::get('ChartTitle', false);
		if ($title) {
			$params['chtt'] = $title;
		}

		$url = self::$type($output, $params);

		if (Controller::$debug) {
			echo '<img src="' . $url . '">';
			var_dump($params);
			var_dump($output);
			$dont_kill = Controller::getVar('dont_kill');
			if (empty($dont_kill)) {
				die;
			}
		}
		$recache = Controller::getVar('recache') ? true : false;
		$image = curl_request($url, array(), array('cache' => $recache ? 1 : 60 * 60, 'bypass_ssl' => 1));
		if (Controller::$debug) {
			var_dump('Image:', $image);
		}
		if (!$image) {
			BackendError::add('Google Chart Error', 'Could not get image');
			return false;
		}
		$filename = Backend::get('ChartFilename', false);
		if (!$filename) {
			$filename = class_name(Controller::$area) . class_name(Controller::$action);
			if (Controller::$action == 'read' && !empty(Controller::$parameters[0])) {
				$filename .= Controller::$parameters[0];
			}
		}
		if (Controller::$debug) {
			var_dump('Filename:', $filename);
		}
		header('Content-Disposition: inline; filename="' . $filename . '.png"');
		return $image;
	}
	
	public static function install() {
		$toret = true;
		return $toret;
	}
}

