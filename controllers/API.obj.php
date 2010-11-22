<?php
class API extends AreaCtl {
	const INPUT_GET     = 'GET';
	const INPUT_POST    = 'POST';
	const INPUT_REQUEST = 'REQUEST';
	
	public function action_check_defines() {
		if (!Component::isActive('BackendError')) {
			return false;
		}
		$query = new SelectQuery('BackendError');
		$query
			->distinct()
			->field('query')
			->filter("`string` LIKE 'Undefined index: %'")
			->filter("`file` LIKE '%\\\Render.obj.php(%) : eval()\'d code'")
			->filter("`query` LIKE 'a_p_i/define/%'");
		return $query->fetchAll(array(), array('column' => 0));
	}
	
	private static function extractParameter($value, $options) {
		$original = $value;
		//TODO Add more filter options...
		if (is_array($value)) {
			$value = filter_var($value, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
		} else {
			$value = filter_var($value);
		}
		if ($value === false && $original !== false) {
			return null;
		}
		if ($value === '' && $options['type'] != 'string') {
			return null;
		}

		switch($options['type']) {
		case 'mixed':
			break;
		case 'numeric':
			settype($value, 'int');
			break;
		default:
			settype($value, $options['type']);
			break;
		}
		if (array_key_exists('range', $options) && !in_array($value, $options['range'])) {
			return null;
		}

		return $value;
	}

	private static function extractDefinition($type, $definition, $data, &$errors) {
		$parameters = array();
		foreach($definition as $name => $options) {
			$options['type'] = array_key_exists('type', $options) ? $options['type'] : 'string';
			$value = array_key_exists($name, $data) ? $data[$name] : null;
			if (is_null($value) && $type == 'required') {
				$errors[] = 'Missing required parameter: ' . $name;
				continue;
			} else {
				$value = self::extractParameter($value, $options);
			}
			if (is_null($value)) {
				if (array_key_exists('default', $options)) {
					$value = $options['default'];
				} else if (array_key_exists('range', $options)) {
					$errors[] = 'Incorrect value for parameter: ' . $name . '.';
					continue;
				} else if ($type == 'required') {
					$errors[] = 'Invalid required parameter: ' . $name;
					continue;
				}
			}
			$parameters[$name] = $value;
		}
		return $parameters;
	}
	
	public static function extract($definition, $data = self::INPUT_REQUEST) {
		$parameters = array();
		$errors     = array();
		if (is_array($data)) {
		} else {
			$data = Controller::getPayload();
		}
		if (!empty($definition['required'])) {
			$required   = self::extractDefinition('required', $definition['required'], $data, $errors);
			$parameters = array_merge($parameters, $required);
		}
		if (!count($errors) && !empty($definition['optional'])) {
			$optional   = self::extractDefinition('optional', $definition['optional'], $data, $errors);
			$parameters = array_merge($parameters, $optional);
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
				$temp = explode('_', $method, 2);
				if (count($temp) !== 2) {
					continue;
				}
				list($sub, $action) = $temp;
				if (!in_array($sub, array('post', 'get', 'put', 'delete', 'action'))) {
					continue;
				}
				if (array_key_exists($action, $results[$component['name']])) {
					continue;
				}
				if (!Permission::check($action, $component['name'])) {
					continue;
				}
				$define_method = 'define_' . $action;
				if (in_array($define_method, $methods)) {
					$results[$component['name']][$action] = call_user_func(array($component['name'], $define_method));
				} else if (array_key_exists('show_undocumented', $_REQUEST)) {
					$results[$component['name']][$action] = array(
						'description' => 'Undocumented',
					);
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