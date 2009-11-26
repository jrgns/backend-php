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
class Account extends TableCtl {
	static public $error_msgs = array(
		1 => 'Please enable cookies. This site can\'t function properly without them',
		2 => 'Username and password does not match',
	);

	function action_login($username = false, $password = false) {
		$toret = false;
		if (is_post()) {
			$toret = true;
			if (!self::checkUser()) {
				$username = $username ? $username : (array_key_exists('username', $_REQUEST) ? $_REQUEST['username'] : false);
				$password = $password ? $password : (array_key_exists('password', $_REQUEST) ? $_REQUEST['password'] : false);
				if ($username && $password && !empty($_SESSION['cookie_is_working'])) {
					$sql = '
						SELECT `users`.*, GROUP_CONCAT(DISTINCT `roles`.`name` ORDER BY `roles`.`name` SEPARATOR \',\') AS `roles` FROM `users` 
						LEFT JOIN `assignments` ON `assignments`.`access_type` = \'users\' AND (`users`.`id` = `assignments`.`access_id` OR `assignments`.`access_id` = 0)
						LEFT JOIN `roles` ON `assignments`.`role_id` = `roles`.`id`
						WHERE
							(`users`.`Username` = :username OR `users`.`Mobile` = :username OR `users`.`Email` = :username)
							AND `users`.`password` = MD5(CONCAT(`users`.`salt`, :password, :salt))
							AND `users`.`active` = 1
							AND `users`.`confirmed` = 1
						GROUP BY `users`.`id`
							';
					$User = new AccountObj();
					$params = array(':username' => $username, ':password' => $password, ':salt' => Controller::$salt);
					$User->load(array('query' => $sql, 'parameters' => $params, 'mode' => 'object'));
					if ($User->object) {
						session_regenerate_id();
						$User->object->roles = empty($User->object->roles) ? array() : explode(',', $User->object->roles);
						$toret = $User->object;
						$_SESSION['user'] = $User->object;
						Controller::addSuccess('Welcome to ' . Backend::getConfig('application.Title') . '!');
						$location = empty($_SESSION['previous_url']) ? SITE_LINK : $_SESSION['previous_url'];
						Controller::redirect($location);
					} else {
						Controller::addError(Account::getError(2));
						Controller::redirect();
						$toret = false;
					}
				} else if (empty($_SESSION['cookie_is_working'])) {
					Controller::addError(Account::getError(1));
					Controller::redirect();
					$toret = false;
				} else {
					Controller::addError('Please supply a username and password');
				}
			}
		} else {
			Controller::whoops();
		}
		if ($toret) {
			Controller::redirect();
		}
		return $toret;
	}
	
	function action_logout() {
		if (is_post()) {
			if (array_key_exists('user', $_SESSION)) {
				$_SESSION = array();
				if (isset($_COOKIE[session_name()])) {
					setcookie(session_name(), '', time() - 42000, '/');
				}			
				session_destroy();
				$location = empty($_SESSION['previous_url']) ? SITE_LINK : $_SESSION['previous_url'];
				Controller::redirect($location);
			}
		}
		Controller::redirect();
		return true;
	}
	
	public function action_update($id) {
		$toret = false;
		if (self::checkUser()) {
			$User = new AccountObj($_SESSION['user']->id);
			$data = $User->fromPost();
			if (is_post()) {
				if ($User->update($data)) {
					Controller::addSuccess('Your account details have been updated');
					$User->load(array('mode' => 'full_object'));
					$_SESSION['user'] = $User->object;
				} else {
					Controller::addError('We could not update your account details');
				}
			}
			$toret = true;
		} else {
			$User = new AccountObj();
			$data = $User->fromPost();
			$toret = true;
		}
		Backend::add('obj_values', $data);
		return $toret ? $User : false;
	}
	
	public function html_update($object) {
		Backend::add('Sub Title', 'Manage my Account');
		if (self::checkUser()) {
			$grav_url = 'http://www.gravatar.com/avatar.php?gravatar_id=' . md5( strtolower($_SESSION['user']->email) ) . '&size=120&d=identicon';
			Backend::add('gravatar', $grav_url);
			Backend::add('TabLinks', $this->getTabLinks('update'));
			Backend::add('obj', $_SESSION['user']);
			Controller::addContent(Render::renderFile('account_info.tpl.php'));
		}
		Controller::addContent(Render::renderFile('loginout.tpl.php'));
	}
	
	public function action_signup() {
		$toret = false;
		$object = new AccountObj();
		$data = $object->fromPost();
		if (is_post()) {
			$options = array('confirmed' => !empty($_SESSION['just_installed']));
			if ($object->create($data, $options)) {
				if (!empty($_SESSION['just_installed'])) {
					unset($_SESSION['just_installed']);
				}
				$toret = true;
				Controller::addSuccess('Signed up!');
				$this->postSignup($object, $options);
			} else {
				$error = $object->getLastError();
				if (!empty($error[1])) {
					switch ($error[1]) {
					/*case 1062: //Duplicate
						Controller::addError('Some of the credentials you used is already in use by another user. Please try other information!');
						break;*/
					default:
						Controller::addError('Could not sign you up. Please try again later!');
						break;
					}
				}
			}
		} else if (!empty($_SESSION['just_installed'])) {
			$data['username'] = 'admin';
		}
		
		Backend::add('obj_values', $data);
		return $toret ? $object : false;
	}
	
	public function html_signup($object) {
		Backend::add('Sub Title', 'Signup to ' . Backend::getConfig('application.Title'));
		Backend::add('Object', $object);
		Controller::addContent(Render::renderFile('signup.tpl.php'));
	}

	/**
	 * Confirm a user account
	 *
	 * @TODO Find a way to disable an account permanently, in other words, prevent this code from working in some way
	 */
	public function action_confirm($salt) {
		$toret = false;
		$account = new AccountObj();
		$db = Backend::getDB($account->getMeta('database'));
		if ($db instanceof PDO) {
			$query = 'SELECT * FROM `' . $account->getMeta('table') . '` WHERE `salt` = :salt AND `confirmed` = 0 AND `active` = 1';
			$stmt = $db->prepare($query);
			if ($stmt && $stmt->execute(array(':salt' => $salt))) {
				$user = $stmt->fetch(PDO::FETCH_ASSOC);
				if ($user) {
					$data = array(
						'confirmed' => true,
					);
					$user = new AccountObj($user['id']);
					if ($user->update($data)) {
						Controller::addSuccess('Your user account has been confirmed. You can proceed to the login.');
					} else {
						Controller::addError('Could not confirm your account at the moment. Please try again later');
					}
				} else {
					Controller::addError('Could not confirm your account at the moment. Please try again later');
				}
			} else {
				Controller::addError('Could not confirm your account at the moment. Please try again later');
				if (Controller::$debug) {
					var_dump($stmt->errorInfo());
				}
			}
		} else {
			Controller::addError('Could not confirm your account at the moment. Please try again later');
		}
		return $toret;
	}
	
	public function html_confirm($object) {
		Backend::add('Sub Title', 'Confirm Account');
		Controller::addContent(Render::renderFile('loginout.tpl.php'));
	}
	
	public static function hook_start() {
		$user = self::checkUser();
		if (!$user && empty($_SESSION['user'])) {
			self::setupAnonymous();
		} else {
			if ($user && in_array('superadmin', $user->roles)) {
				Controller::addNotice('You are the super user. Be carefull, careless clicking costs lives...');
			}
		}
		//@todo Maybe give this another name? user is too generic
		Backend::add('user', $user);
	}
	
	public function postSignup($object, array $options = array()) {
		if (Backend::getConfig('backend.application.user.confirm') && empty($options['confirmed'])) {
			$this->confirmUser($object);
		}
	}
	
	protected function confirmUser($object) {
		$url = SITE_LINK . '?q=account/confirm/' . $object->array['salt'];
		$app_name = Backend::getConfig('application.Title');
		$message = <<< END
Hi {$object->array['name']}!

Your signup to $app_name was successful. Please click on the following link to activate the account:

<a href="$url">$url</a>

If the link doesn't work, copy the following URL into your browser's location bar:
$url

Regards
END;
		Controller::addSuccess('A confirmation email as been sent to your email address. Please click on the link in the email to confirm your account');
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
	
	static function checkUser($user = false) {
		$toret = false;
		if (!empty($_SESSION) && array_key_exists('user', $_SESSION) && is_object($_SESSION['user']) && $_SESSION['user']->id > 0) {
			$toret = $_SESSION['user'];
		}
		return $toret;
	}
	
	public function checkPermissions(array $options = array()) {
		$toret = parent::checkPermissions();
		if (!$toret && in_array(Controller::$action, array('update', 'display'))) {
			$toret = Controller::$parameters[0] == $_SESSION['user']->id || Controller::$parameters[0] == 0;
		}
		return $toret;
	}

	public static function checkParameters($parameters) {
		if (array_key_exists('user', $_SESSION) && $_SESSION['user']->id > 0) {
			if (in_array(Controller::$action, array('update', 'display')) && !empty($parameters['0']) && $parameters[0] != $_SESSION['user']->id) {
				$parameters[0] = $_SESSION['user']->id;
			}
			return $parameters;
		}
		return array();
	}

	public static function install(array $options = array()) {
		$toret = parent::install($options);

		$toret = Hook::add('start', 'pre', __CLASS__, array('global' => true)) && $toret;
		
		$toret = Permission::add('anonymous', 'signup', 'account') && $toret;
		$toret = Permission::add('anonymous', 'confirm', 'account') && $toret;
		$toret = Permission::add('anonymous', 'login', 'account') && $toret;
		$toret = Permission::add('authenticated', 'logout', 'account') && $toret;
		return $toret;
	}
}

