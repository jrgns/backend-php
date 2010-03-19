<?php
class API extends AreaCtl {
	public function action_define($class, $function) {
		if (!is_callable(array($class, 'define_' . $function))) {
			Backend::addError('Unknown function: ' . $class . ':' . $function);
			return false;
		}
		$definition = call_user_func(array($class, 'define_' . $function));
		if (!$definition) {
			return false;
		}

		return array(
			'class'      => $class,
			'function'   => $function,
			'definition' => $definition,
		);
	}
	
	public function html_define($values) {
		if ($values) {
			Backend::addContent(Render::renderFile('api_function.tpl.php', $values));
		}
		return true;
	}
}