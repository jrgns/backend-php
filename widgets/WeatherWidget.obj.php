<?php
class WeatherWidget extends Widget {
	private $location = false;
	private $current  = array();
	private $forecast = array();

	function __construct($location) {
		parent::__construct('WeatherWidget');
		$this->location = $location;
		
		$weather = curl_request('http://www.google.co.za/ig/api', array('weather' => $this->location, 'hl' => 'af', 'C' => 1), array('cache' => 60 * 60));
		$weather = simplexml_load_string($weather);
		if (!empty($weather->weather->current_conditions)) {
			foreach($weather->weather->current_conditions->children() as $condition => $value) {
				$this->current[$condition] = (string)$value['data'];
			}
		}
		if (!empty($weather->weather->forecast_conditions)) {
			foreach($weather->weather->forecast_conditions as $forecast) {
				$this->forecast[(string)$forecast->day_of_week['data']] = array(
					'low'       => (int)$forecast->low['data'],
					'high'      => (int)$forecast->high['data'],
					'icon'      => (string)$forecast->icon['data'],
					'condition' => (string)$forecast->condition['data'],
				);
			}
		}
	}
	
	function __toString() {
		echo '<div id="weather_widget">';
		echo '<h3>Weather</h3>';
		echo $this->renderDay('Current', $this->current);
		foreach($this->forecast as $day => $forecast) {
			echo $this->renderDay($day, $forecast);
		}
		echo '</div>';
	}
	
	function renderDay($name, $day) {
		$output  = '<div class="span-4">';
		$output .= '<h3>' . $name . ' - ' . $day['condition'] . '</h3>';
		$output .= '<p><img src="http://www.google.co.za' . $day['icon'] . '" style="float: right; padding: 3px;">';
		if (array_key_exists('low', $day) && array_key_exists('high', $day)) {
			$output .= 'Low: ' . $day['low'] . '&deg;<br>High: ' . $day['high'] . '&deg;';
		} else if (array_key_exists('temp_c', $day)) {
			$output .= 'Temp: ' . $day['temp_c'] . '&deg;<br>';
		}
		if (array_key_exists('humidity', $day)) {
			$output .= $day['humidity'] . '<br>';
		}
		if (array_key_exists('wind_condition', $day)) {
			$output .= $day['wind_condition'] . '<br>';
		}
		$output .= '</p></div>';
		return $output;
	}
}
