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

	function __construct() {
		parent::__construct();
		$this->mode = 'gchart';
		$this->mime_type = 'image/*';
	}
	
	private static function simple_line($output, &$params) {
		$data = $output['data'];
		$min  = min(array_merge(array(0), $data));
		$max  = max($data);
		$params['cht']  = 'lc';
		$params['chd']  = 't:' . implode(',', $data);
		$params['chds'] = $min . ',' . $max;
		$params['chxr'] = '1,' . $min . ',' . $max;
		$params['chxt'] = 'x,y';
		$params['chs']  = array_key_exists('size', $output) ? $output['size'] : '400x250';
		if (array_key_exists('legend', $output)) {
			$legend = $output['legend'];
			if (is_array($legend)) {
				$legend = '0:|' . implode('|', $legend);
			}
		} else {
			$legend = '0:|' . implode('|', array_keys($data));
		}
		if ($legend) {
			$params['chxl'] = $legend;
		}
		
		return self::$url . '?' . http_build_query($params);
	}
	
	public static function hook_output($output) {
		if (!is_array($output)) {
			BackendError::add('Google Chart Error', 'Invalid Output');
			return false;
		}
		$type = Backend::get('ChartType', 'simple_line');
		if (!method_exists('GChartView', $type)) {
			BackendError::add('Google Chart Error', 'Invalid Chart Type');
			return false;
		}

		if (!array_key_exists('data', $output)) {
			$output = array('data' => $output);
		}

		$params = array();
		if ($title = Backend::get('ChartTitle', false)) {
			$params['chtt'] = $title;
		}

		$url = self::$type($output, $params);

		if (Controller::$debug) {
			echo '<img src="' . $url . '">';
			var_dump($params);
			var_dump($output); die;
		}
		$image = curl_request($url, array(), array('cache' => 60 * 60));
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
		if (!Controller::$debug) {
			header('Content-disposition: inline; filename="' . $filename . '.png"');
		}
		return $image;
	}
	
	public static function install() {
		$toret = true;
		return $toret;
	}
}

