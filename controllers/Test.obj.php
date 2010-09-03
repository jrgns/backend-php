<?php
class Test extends AreaCtl {
	public function action_execute() {
		$return_boolean = empty($_REQUEST['return_boolean']) ? false : true;
		$components = Component::getActive();
		if (!$components) {
			return false;
		}

		$end_result = true;
		$results = array();
		foreach(Component::getActive() as $component) {
			$methods = get_class_methods($component['name']);
			if (!$methods) {
				continue;
			}
			$results[$component['name']] = array();
			$component_obj = new $component['name']();
			foreach($methods as $method) {
				if (substr($method, 0, 7) == 'action_') {
					$test_method = preg_replace('/^action_/', 'test_', $method);
					if (in_array($test_method, $methods)) {
						if ($result = $component_obj->$test_method()) {
						} else {
							Backend::addError($component['name'] . '::' . $method . ' Failed');
							$end_result = false;
						}
						$results[$component['name']][$method] = $result;
					}
				}
			}
			if (!count($results[$component['name']])) {
				unset($results[$component['name']]);
			}
		}
		ksort($results);
		return $return_boolean ? $end_result : $results;
	}
}