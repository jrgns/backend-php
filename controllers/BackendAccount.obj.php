<?php
/**
 * Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * @license http://www.eclipse.org/legal/epl-v10.html
 * @package Controllers
 *
 * Contributors:
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 */
class BackendAccount extends TableCtl {
	public static $error_msgs = array(
		1 => 'Please enable cookies. This site can\'t function properly without them',
		2 => 'Username and password does not match',
		3 => 'Please supply a username and password',
	);
	
	protected static $name = false;
	
	protected static $current_user = false;
	
	public static function getName() {
		if (empty(self::$name)) {
			self::$name = Value::get('BackendAccount', 'Account');
		}
		return self::$name;
	}
	
	public static function getCurrentUser() {
		return self::$current_user;
	}
	
	public static function getCurrentUserID() {
		if (self::$current_user) {
			return self::$current_user->id;
		}
		return false;
	}
	
	public static function authenticate_user($username, $password) {
		
	}
	
	public static function getQuery() {
		$query = new SelectQuery(BackendAccount::getName());
		$query
			->field(array('`users`.*', "GROUP_CONCAT(DISTINCT `roles`.`name` ORDER BY `roles`.`name` SEPARATOR ',') AS `roles`"))
			->leftJoin('Assignment', array("`assignments`.`access_type` = 'users'", '`users`.`id` = `assignments`.`access_id` OR `assignments`.`access_id` = 0'))
			->leftJoin('Role', array('`assignments`.`role_id` = `roles`.`id`'))
			->filter('`users`.`active` = 1')
			->filter('`users`.`confirmed` = 1')
			->group('`users`.`id`');
		return $query;
	}

	function action_login($username = false, $password = false) {
		$toret = false;
		if (!is_post()) {
			return false;
		}
		$toret = true;
		if (self::checkUser()) {
			return true;
		}
		$username = $username ? $username : (array_key_exists('username', $_REQUEST) ? $_REQUEST['username'] : false);
		$password = $password ? $password : (array_key_exists('password', $_REQUEST) ? $_REQUEST['password'] : false);
		if ($username && $password && !empty($_SESSION['cookie_is_working'])) {
			$User = self::getObject(self::getName());

			$query = self::getQuery();
			$query
				->filter('`users`.`Username` = :username OR `users`.`Mobile` = :username OR `users`.`Email` = :username')
				->filter('`users`.`password` = MD5(CONCAT(`users`.`salt`, :password, :salt))');
			$params = array(':username' => $username, ':password' => $password, ':salt' => Controller::$salt);

			$User->read(array('query' => $query, 'parameters' => $params, 'mode' => 'object'));
			if ($User->object) {
				session_regenerate_id();
				$User->object->roles = empty($User->object->roles) ? array() : explode(',', $User->object->roles);
				$toret = $User->object;
				$_SESSION['user'] = $User->object;
				if (Component::isActive('PersistUser')) {
					PersistUser::remember($User->object);
				}
				return $User;
			} else {
				return -2;
			}
		} else if (empty($_SESSION['cookie_is_working'])) {
			return -1;
		} else {
			return -3;
		}
		return $toret;
	}
	
	function html_login($result) {
		switch (true) {
		case $result instanceof DBObject:
			Backend::addSuccess('Welcome to ' . Backend::getConfig('application.Title') . '!');
			if (!empty($_SESSION['bookmark'])) {
				$bookmark = $_SESSION['bookmark'];
				unset($_SESSION['bookmark']);
			} else {
				$bookmark = 'previous';
			}
			Controller::redirect($bookmark);
			break;
		case $result === true:
			Controller::redirect('previous');
			break;
		case $result < 0:
			Backend::addError(BackendAccount::getError(0 - $result));
			break;
		default:
			break;
		}
		Backend::addContent(Render::renderFile('loginout.tpl.php'));
		
	}
	
	function action_logout() {
		if (is_post()) {
			self::$current_user = false;
			if (array_key_exists('user', $_SESSION)) {
				if (Component::isActive('PersistUser')) {
					PersistUser::forget($_SESSION['user']);
				}
				$_SESSION = array();
				if (isset($_COOKIE[session_name()])) {
					setcookie(session_name(), '', time() - 42000, '/');
				}			
				session_destroy();
			}
		}
		Controller::redirect(SITE_LINK);
		return true;
	}
	
	public function action_update_old($id) {
		$toret = false;
		if (self::checkUser()) {
			$User = self::getObject(get_class($this), $_SESSION['user']->id);
			$data = $User->fromPost();
			if (is_post()) {
				if ($User->update($data)) {
					Backend::addSuccess('Your account details have been updated');
					$User->read(array('mode' => 'full_object'));
					$_SESSION['user'] = $User->object;
					self::$current_user = $User->object;
				} else {
					Backend::addError('We could not update your account details');
				}
			}
			$toret = true;
		} else {
			$User = self::getObject(get_class($this));
			$data = $User->fromPost();
			$toret = true;
		}
		Backend::add('obj_values', $data);
		return $toret ? $User : false;
	}
	
	public function action_list($start, $count, array $options = array()) {
		return parent::action_list($start, $count, $options);
	}
	
	public function html_display($result) {
		parent::html_display($result);
		if ($result instanceof DBObject) {
			if ($_SESSION['user']->id == $result->array['id']) {
				Backend::add('Sub Title', 'My Account');
				Backend::addContent(Render::renderFile('loginout.tpl.php'));
			} else {
				Backend::add('Sub Title', 'User: ' . $result->array['username']);
			}
		}
	}
	
	public function html_update($result) {
		parent::html_update($result);
		if ($result instanceof DBObject) {
			if ($_SESSION['user']->id == $result->array['id']) {
				Backend::add('Sub Title', 'Manage my Account');
			} else {
				Backend::add('Sub Title', 'Update User ' . $result->array['username']);
			}
		}
	}

	public function action_signup() {
		$toret = false;
		$object = self::getObject(get_class($this));
		$data = $object->fromPost();
		if (is_post()) {
			if ($object->create($data)) {
				if (!empty($_SESSION['just_installed']) && Backend::getConfig('backend.application.user.confirm')) {
					$data = array('confirmed' => 1);
					$object->update($data);
					unset($_SESSION['just_installed']);
				}
				Backend::addSuccess('Signed up!');
				$this->postSignup($object);
				$toret = $object;
			} else {
				Backend::addError('Could not sign you up. Please try again later!');
			}
		} else if (!empty($_SESSION['just_installed'])) {
			$toret = true;
			$data['username'] = 'admin';
		}
		
		Backend::add('obj_values', $data);
		return $toret;
	}
	
	public function html_signup($result) {
		Backend::add('Sub Title', 'Signup to ' . Backend::getConfig('application.Title'));
		switch (true) {
		case ($result instanceof DBObject):
			//Successful signup, redirect
			Controller::redirect(SITE_LINK);
			break;
		case ($result):
		default:
			Backend::add('Object', $result);
			Backend::addContent(Render::renderFile('signup.tpl.php'));
			break;
		}
	}

	/**
	 * Confirm a user account
	 *
	 * @TODO Find a way to disable an account permanently, in other words, prevent this code from working in some way
	 */
	public function action_confirm($salt) {
		$toret = false;
		$account = self::getObject(get_class($this));
		$query = new SelectQuery(BackendAccount::getName());
		$query
			->filter('`salt` = :salt')
			->filter('`confirmed` = 0')
			->filter('`active` = 1');
		$user = $query->fetchAssoc(array(':salt' => $salt));
		if (!$user) {
			Backend::addError('Could not confirm your account at the moment. Please try again later');
			return false;
		}
		send_email(
			Value::get('site_owner_email', Value::get('site_email', 'info@' . SITE_DOMAIN)),
			'New User: ' . $user['username'],
			var_export($user, true)
		);
		$data = array(
			'confirmed' => true,
		);
		$user = self::getObject(get_class($this), $user['id']);
		if ($user->update($data)) {
			Backend::addSuccess('Your user account has been confirmed. Please login.');
			return true;
		}

		Backend::addError('Could not confirm your account at the moment. Please try again later');
		return false;
	}
	
	public function html_confirm($result) {
		if ($result) {
			Controller::redirect('?q=account/login');
		}
		Controller::redirect('?q=');
	}
	
	public static function hook_init() {
		$user = self::checkUser();
		//Check if HTTP Digest Auth headers have been passed down
		if (!$user && Value::get('CheckHTTPAuth', false)) {
			$user = self::processHTTPAuth();
			if ($user) {
				$_SESSION['user']   = $user;
				self::$current_user = $user;
			}
		}
	}
	
	public static function hook_start() {
		$user = self::checkUser();
		if (!$user && empty($_SESSION['user'])) {
			self::setupAnonymous();
		} else {
			if ($user && in_array('superadmin', $user->roles)) {
				Backend::addNotice('You are the super user. Be carefull, careless clicking costs lives...');
			}
		}
		self::$current_user = $user;
		Backend::add('BackendAccount', $user);
	}
	
	public static function hook_post_finish() {
		$_SESSION['cookie_is_working'] = true;
	}
	
	public function postSignup($object, array $options = array()) {
		if (Backend::getConfig('backend.application.user.confirm') && empty($object->array['confirmed'])) {
			$this->confirmUser($object);
		}
	}
	
	public static function getHTTPAuth() {
		$query = new SelectQuery('User');
		$query->field('password')->filter('`username` = ?');
		//It's tricky, because the password is not stored in plain text. Use the hash as the password for HTTP Auth requests
		//jrgns: 98884858b06963be03b23f679aac9bf3
		return DigestAuthentication::getInstance(array($query, 'fetchColumn'));
	}
	
	public static function processHTTPAuth() {
		$auth = BackendAccount::getHTTPAuth();
		if ($username = $auth->process()) {
			$query = BackendAccount::getQuery();
			$query->filter('`username` = :username');
			$User = TableCtl::getObject(BackendAccount::getName());
			$params = array(':username' => $username);
			$User->read(array('query' => $query, 'parameters' => $params, 'mode' => 'object'));
			if ($User->object) {
				session_regenerate_id();
				$User->object->roles = empty($User->object->roles) ? array() : explode(',', $User->object->roles);
				$_SESSION['user']   = $User->object;
				self::$current_user = $User->object;
				return $User->object;
			}
		}
		return false;
	}
	
	protected function confirmUser($object) {
		$url = SITE_LINK . '?q=account/confirm/' . $object->array['salt'];
		$app_name = Backend::getConfig('application.Title');
		$message = <<< END
Hi {$object->array['name']}!

Your signup to $app_name was successful. Please click on the following link to activate the account:

$url

Please note that this account will be deleted if it isn't confirmed in a weeks time.

Regards
END;
		Backend::addSuccess('A confirmation email as been sent to your email address. Please click on the link in the email to confirm your account');
		send_email($object->array['email'], 'Confirmation Email', $message);
	}

	public static function setupAnonymous() {
		$_SESSION['user'] = new stdClass();
		$_SESSION['user']->id = 0;
		$_SESSION['user']->name = 'Anon';
		$_SESSION['user']->surname = 'Ymous';
		$_SESSION['user']->email = null;
		$_SESSION['user']->mobile = null;
		$_SESSION['user']->username = null;
		$_SESSION['user']->password = null;
		$_SESSION['user']->active = null;
		$_SESSION['user']->modified = null;
		$_SESSION['user']->added = null;
		$_SESSION['user']->roles = array('anonymous');
	}
	
	public static function checkUser($user = false) {
		if (!empty(self::$current_user)) {
			return self::$current_user;
		}
		if (!empty($_SESSION['user']) && is_object($_SESSION['user']) && $_SESSION['user']->id > 0) {
			return $_SESSION['user'];
		}
		return false;
	}
	
	public static function getGravatar($email, $size = 120) {
		return 'http://www.gravatar.com/avatar.php?gravatar_id=' . md5(strtolower($email)) . '&size=' . $size . '&d=identicon';
	}
	
	public function checkPermissions(array $options = array()) {
		$toret = parent::checkPermissions();
		if (!$toret && in_array(Controller::$action, array('update', 'display'))) {
			$toret = Controller::$parameters[0] == $_SESSION['user']->id || Controller::$parameters[0] == 0;
		}
		return $toret;
	}

	public static function checkParameters($parameters) {
		$parameters = parent::checkParameters($parameters);
		if (!Permission::check('manage', 'account')) {
			if (array_key_exists('user', $_SESSION) && $_SESSION['user']->id > 0) {
				if (Controller::$action == 'signup') {
					Controller::setAction('display');
				}
				if (in_array(Controller::$action, array('update', 'display')) && (empty($parameters['0']) || $parameters[0] != $_SESSION['user']->id)) {
					$parameters[0] = $_SESSION['user']->id;
				}
			}
		}
		return $parameters;
	}
	
	public function daily(array $options = array()) {
		if (get_class($this) == BackendAccount::getName()) {
			return self::purgeUnconfirmed();
		}
	}
	
	public function weekly(array $options = array()) {
		if (get_class($this) == BackendAccount::getName()) {
			return self::userStats();
		}
	}
	
	public static function purgeUnconfirmed() {
		$query = new DeleteQuery(BackendAccount::getName());
		$query
			->filter('`confirmed` = 0')
			->filter('`added` < DATE_SUB(DATE(NOW()), INTERVAL 1 WEEK)');
		$deleted = $query->execute();
		Backend::addSuccess($deleted . ' unconfirmed users deleted');
		send_email(
			Value::get('site_owner_email', Value::get('site_email', 'info@' . SITE_DOMAIN)),
			'Unconfirmed Users purged: ' . $deleted,
			$deleted . ' users were deleted from the database.
They were unconfirmed, and more than a week old

Site Admin
'
		);
		return true;
	}
	
	public static function userStats() {
		$msg = array();
		$query = new SelectQuery(BackendAccount::getName());
		$query
			->field('COUNT(*) AS `Total`, SUM(IF(TO_DAYS(NOW()) - TO_DAYS(`added`) < 7, 1, 0)) AS `New`')
			->filter('`active` = 1')
			->filter('`confirmed` = 1');
		if ($stats = $query->fetchAssoc()) {
			$msg[] = 'There are a total of ' . $stats['Total'] . ' **active** users, of which ' . $stats['New'] . ' signed up in the last 7 days';
		}
		$query = new SelectQuery(BackendAccount::getName());
		$query
			->field('COUNT(*) AS `Total`, SUM(IF(TO_DAYS(NOW()) - TO_DAYS(`added`) < 7, 1, 0)) AS `New`')
			->filter('`active` = 1')
			->filter('`confirmed` = 1');
		if ($stats = $query->fetchAssoc()) {
			$msg[] = 'There are a total of ' . $stats['Total'] . ' **unconfirmed** users, of which ' . $stats['New'] . ' signed up in the last 7 days';
		}
		$msg = implode(PHP_EOL . PHP_EOL, $msg);
		send_email(
			Value::get('site_owner_email', Value::get('site_email', 'info@' . SITE_DOMAIN)),
			'User stats for ' . Backend::get('Title'),
			$msg
		);
		return true;
	}

	public static function install(array $options = array()) {
		$options['install_model'] = array_key_exists('install_model', $options) ? $options['install_model'] : true;
		$toret = parent::install($options);

		$toret = Hook::add('init', 'pre', self::getName(), array('global' => true, 'sequence' => -10)) && $toret;
		$toret = Hook::add('start', 'pre', self::getName(), array('global' => true)) && $toret;
		$toret = Hook::add('finish', 'post', self::getName(), array('global' => true)) && $toret;
		
		$toret = Permission::add('anonymous', 'signup', self::getName()) && $toret;
		$toret = Permission::add('anonymous', 'confirm', self::getName()) && $toret;
		$toret = Permission::add('anonymous', 'login', self::getName()) && $toret;
		$toret = Permission::add('authenticated', 'logout', self::getName()) && $toret;
		$toret = Permission::add('authenticated', 'display', self::getName()) && $toret;
		return $toret;
	}
}

