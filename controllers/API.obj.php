<?php
class API extends AreaCtl {
	const INPUT_GET     = 'GET';
	const INPUT_POST    = 'POST';
	const INPUT_REQUEST = 'REQUEST';

	private static function checkParam($name, $value, $options, &$errors) {
		$type  = array_key_exists('type', $options)  ? $options['type']  : 'string';
		$range = array_key_exists('range', $options) ? $options['range'] : false;

		//Add other filters / validators here
		switch($type) {
		case 'mixed':
			break;
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
	
	private static function extractDefinition($type, $definition, $data, &$errors) {
		$parameters = array();
		foreach($definition as $name => $options) {
			if (array_key_exists($name, $data)) {
				if (is_array($data[$name])) {
					$value = filter_var($data[$name], FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
				} else {
					$value = filter_var($data[$name]);
				}
				if ($value === false) {
					$errors[] = 'Missing required parameter: ' . $name;
					continue;
				}
			} else if ($type == 'required') {
				$errors[] = 'Invalid required parameter: ' . $name;
				continue;
			} else if (array_key_exists('default', $options)) {
				$value = $options['default'];
			} else {
				continue;
			}
			$parameters[$name] = self::checkParam($name, $value, $options, $errors);
		}
		return $parameters;
	}
	
	public static function extract($definition, $data = self::INPUT_REQUEST) {
		$parameters = array();
		$errors     = array();
		if (is_array($data)) {
		} else {
			switch ($data) {
			case self::INPUT_GET:
				$data = $_GET;
				break;
			case self::INPUT_POST:
				$data = $_POST;
				break;
			case self::INPUT_REQUEST:
				$data = $_REQUEST;
				break;
			}
		}
		if (!empty($definition['required'])) {
			$parameters = array_merge($parameters, self::extractDefinition('required', $definition['required'], $data, $errors));
		}
		if (!count($errors) && !empty($definition['optional'])) {
			$parameters = array_merge($parameters, self::extractDefinition('optional', $definition['optional'], $data, $errors));
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

		if (!empty($_SESSION['user'])) {
			if (is_object($_SESSION['user']) && property_exists($_SESSION['user'], 'roles')) {
				$user_roles = $_SESSION['user']->roles;
			} else {
				$user_roles = array();
			}
		} else {
			$user_roles = array('anonymous');
		}
		foreach(Component::getActive() as $component) {
			$methods = get_class_methods($component['name']);
			if (!$methods) {
				continue;
			}
			$results[$component['name']] = array();
			foreach($methods as $method) {
				if (substr($method, 0, 7) == 'action_' && Permission::check(substr($method, 7), $component['name'])) {
					$define_method = preg_replace('/^action_/', 'define_', $method);
					if (in_array($define_method, $methods)) {
						$results[$component['name']][$method] = call_user_func(array($component['name'], $define_method));
					} else if (array_key_exists('show_undocumented', $_REQUEST)) {
						$results[$component['name']][$method] = array(
							'description' => 'Undocumented',
						);
					}
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

	public static function install(array $options = array()) {
		$toret = parent::install($options);

		$toret = Permission::add('nobody', 'define', __CLASS__) && $toret;
		$toret = Permission::add('authenticated', 'define', __CLASS__) && $toret;
		$toret = Permission::add('bi', 'define', __CLASS__) && $toret;
		$toret = Permission::add('bisp', 'define', __CLASS__) && $toret;
		$toret = Permission::add('user', 'define', __CLASS__) && $toret;
		$toret = Permission::add('nobody', 'index', __CLASS__) && $toret;
		$toret = Permission::add('authenticated', 'index', __CLASS__) && $toret;
		$toret = Permission::add('bi', 'index', __CLASS__) && $toret;
		$toret = Permission::add('bisp', 'index', __CLASS__) && $toret;
		$toret = Permission::add('user', 'index', __CLASS__) && $toret;
		return $toret;
	}
}