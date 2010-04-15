<?php
class API extends AreaCtl {
	private static function checkParam($name, $value, $options, &$errors) {
		$type  = array_key_exists('type', $options)  ? $options['type']  : 'string';
		$range = array_key_exists('range', $options) ? $options['range'] : false;

		//Add other filters / validateors here
		switch($type) {
		case 'numeric':
			settype($value, 'int');
			break;
		default:
			settype($value, $type);
			break;
		}

		if ($range && !in_array($value, $range)) {
			$errors[] = 'Incorrect value for parameter: ' . $name . '. ' . $value . ' given.';
			unset($data[$name]);
		}
		return $value;
	}

	public static function extract($definition, $data) {
		$parameters = array();
		$errors     = array();
		if (!empty($definition['required'])) {
			foreach($definition['required'] as $name => $options) {
				if (!array_key_exists($name, $data)) {
					$errors[] = 'Missing required parameter: ' . $name;
					continue;
				}
				$parameters[$name] = self::checkParam($name, $data[$name], $options, $errors);
			}
		}
		if (!count($errors) && !empty($definition['optional'])) {
			foreach($definition['optional'] as $name => $options) {
				if (array_key_exists($name, $data)) {
					$parameters[$name] = self::checkParam($name, $data[$name], $options, $errors);
				} else {
					if (!array_key_exists('default', $options)) {
						continue;
					}
					$parameters[$name] = $options['default'];
				}
			}
		}

		if (!count($errors)) {
			return $parameters;
		}
		Backend::addError($errors);
		return false;
	}
	
	public function action_index() {
		$components = Component::getActive();
		if (!$components) {
			return false;
		}
		$results = array();
		foreach(Component::getActive() as $component) {
			$methods = get_class_methods($component['name']);
			if (!$methods) {
				continue;
			}
			$results[$component['name']] = array();
			foreach($methods as $method) {
				if (substr($method, 0, 7) == 'define_') {
					$results[$component['name']][$method] = call_user_func(array($component['name'], $method));
				}
			}
			if (count($results[$component['name']])) {
				ksort($results[$component['name']]);
			} else {
				unset($results[$component['name']]);
			}
		}
		ksort($results);
		return count($results) ? $results : false;
	}
	
	public function html_index($result) {
		Backend::add('actions', $result);
		Backend::addContent(Render::renderFile('api.index.tpl.php'));
	}

	public function action_define($class, $function = false) {
		if ($function) {
			if (!is_callable(array($class, 'define_' . $function))) {
				Backend::addError('Unknown function: ' . $class . '::' . $function);
				return false;
			}
			$definition = call_user_func(array($class, 'define_' . $function));
			if (!$definition) {
				return false;
			}
		} else {
			$methods = get_class_methods($class);
			if (!$methods) {
				return false;
			}
			$definition = array();
			foreach($methods as $method) {
				if (substr($method, 0, 7) == 'define_') {
					$definition[$method] = call_user_func(array($class, $method));
				}
			}
			if (count($definition)) {
				ksort($definition);
			} else {
				$definition = false;
			}
		}

		return array(
			'class'      => $class,
			'function'   => $function,
			'definition' => $definition,
		);
	}
	
	public function html_define($values) {
		if ($values) {
			if ($values['function']) {
				Backend::add('Sub Title', $values['class'] . '::' . $values['function']);
				Backend::addContent(Render::renderFile('api_function.tpl.php', $values));
			} else {
				Backend::add('Sub Title', $values['class']);
				Backend::addContent(Render::renderFile('api_class.tpl.php', $values));
			}
		}
		return true;
	}
}