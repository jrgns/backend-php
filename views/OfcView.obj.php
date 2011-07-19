<?php
/**
 * Default class to handle OfcView specific functions
 */
class OfcView extends TextView {
	function __construct() {
		parent::__construct();
		$this->mode     = 'ofc';
	}

	public static function hook_output($to_print) {
		if (empty($to_print['values']) || empty($to_print['options'])){
			Backend::addError('Values and options are required');
			return false;
		} else {
			$to_print = self::make_bandwidth_chart($to_print['values'], $to_print['options']);
		}
		return $to_print;
	}

	private static function make_bandwidth_chart($values, $options = array()) {
		if (!$values || !count($values)) {
			return false;
		}
		$title = !empty($options['title']) ? $options['title'] : 'Chart';//array_check_value($options, 'title', 'BI Chart');
		$step = !empty($options['step']) ? $options['step'] : 0.75;//array_check_value($options, 'step', 0.75);
		$max = !empty($options['max']) ? $options['max'] : 0;//array_check_value($options, 'max', 0);
		$value_mod = !empty($options['value_modifier']) ? $options['value_modifier'] : 1;//array_check_value($options, 'value_modifier', 1);
		$input_title = !empty($options['input_title']) ? $options['input_title'] : 'Downloads for :key: #val#';//array_check_value($options, 'input_title', 'Downloads for :key: #val#');
		$output_title = !empty($options['output_title']) ? $options['output_title'] : 'Uploads for :key: #val#';//array_check_value($options, 'output_title', 'Uploads for :key: #val#');
		$total_title = !empty($options['total_title']) ? $options['total_title'] : 'Total for :key: #val#';//array_check_value($options, 'total_title', 'Total for :key: #val#');
		$on_click = !empty($options['on_click']) ? $options['on_click'] : false;//array_check_value($options, 'on_click', false);
		$x_labels = !empty($options['x_labels']) ? $options['x_labels'] : array_keys($values);//array_check_value($options, 'x_labels', array_keys($values));

		include_once(APP_FOLDER . '/libraries/php-ofc-library/ofc_title.php');
		include_once(APP_FOLDER . '/libraries/php-ofc-library/open-flash-chart.php');
		//include(APP_FOLDER . '/libraries/php-ofc-library/ofc_bar_glass_value.php');
		include_once(APP_FOLDER . '/libraries/php-ofc-library/ofc_line_dot.php');
		include_once(APP_FOLDER . '/libraries/php-ofc-library/ofc_line_base.php');
		include_once(APP_FOLDER . '/libraries/php-ofc-library/ofc_x_axis.php');

		$title = new title($title);
		$chart = new open_flash_chart();
		$chart->set_title($title);
		$input = array();
		$ouput = array();
		$total = array();
		$clicks = array();
		foreach($values as $key => $value) {
			$this_value = (float)number_format(($value['input'] / $value_mod), 2, '.', '');
			$tmp = new bar_glass_value($this_value);
			$tmp->set_tooltip(preg_replace('/:key/', $key, $input_title));
			$input[] = $tmp;

			$this_value = (float)number_format(($value['output'] / $value_mod), 2, '.', '');
			$tmp = new bar_glass_value($this_value);
			$tmp->set_tooltip(preg_replace('/:key/', $key, $output_title));
			$output[] = $tmp;

			$this_value = (float)number_format((($value['input'] + $value['output']) / $value_mod), 2, '.', '');
			$tmp = new dot_value($this_value, '#000066');
			$tmp->set_tooltip(preg_replace('/:key/', $key, $total_title));
			$total[] = $tmp;

			if ($on_click) {
				$tmp = (float)number_format(($value['input'] + $value['output']) / $value_mod, 2, '.', '');
				$clicks[] = $tmp;
			}
			$max = ceil(max(($value['input'] + $value['output']) / $value_mod, $max));
		}
		$x_axis = new x_axis();
		$x_axis->set_labels_from_array( $x_labels );
		$x_axis->set_3d( 5 );
		$x_axis->colour = '#909090';

		if ($max > 0) {
			//Don't know why we sometimes get a division by zero error
			@$max = $max + ($step - ($max % $step));
		}
		if ($max / $step > 5) {
			$step = floor($max / 5);
		} else if ($max / $step <= 1) {
			$step = floor($max / 2);
		}

		$y_axis = new y_axis();
		$y_axis->set_range(0, $max, $step);

		$input_bar = new bar_glass();
		$input_bar->set_values($input);
		$input_bar->colour = '#D54C78';

		$output_bar = new bar_glass();
		$output_bar->set_values($output);
		$output_bar->colour = '#78D54C';

		$total_graph = new line_hollow();
		$total_graph->set_colour('#9999FF');
		$total_graph->set_values($total);

		if ($on_click) {
			$click_graph = new line();
			$click_graph->set_values($clicks);
			$click_graph->set_on_click($on_click);
			$chart->add_element($click_graph);
		}

		$chart->set_x_axis($x_axis);
		$chart->set_y_axis($y_axis);
		$chart->add_element($input_bar);
		$chart->add_element($output_bar);
		$chart->add_element($total_graph);

		//$decoded = json_decode($chart->toString());
		return $chart->toString();
	}

	public static function install() {
		$result = true;
		return $result;
	}
}
