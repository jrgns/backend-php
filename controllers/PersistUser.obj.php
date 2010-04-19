<?php
/**
 * Persist user login for two weeks
 *
 * Idea from http://fishbowl.pastiche.org/2004/01/19/persistent_login_cookie_best_practice
 */
class PersistUser extends TableCtl {
	public static function remember($user) {
		//We need a user, but we won't remember the admin user.
		//if ($user && $user->id > 0 && !in_array('superadmin', $user->roles)) {
		if ($user && $user->id > 0) {
			$random = get_random('number');

			$persist = new PersistUserObj();
			$data = array(
				'user_id' => $user->id,
				'random'  => $random,
			);
			if ($persist->create($data)) {
				$query = new SelectQuery('PersistUser');
				$query
					->field('MD5(CONCAT(`id`, `user_id`, `random`))')
					->filter('`id`= :id');
				$hash = $query->fetchColumn(array(':id' => $persist->array['id']));
				if (setcookie('remembered', $hash, time() + 60 * 60 * 24 * 14, WEB_SUB_FOLDER)) {
					return true;
				} else {
					Backend::addError('Could not set cookie to remember login');
					$query = new DeleteQuery('PersistUser');
					$query
						->filter('`id` = :id')
						->limit(1);
					$query->execute(array(':id' => $id));
				}
			} else {
				Backend::addError('Could not remember login');
			}
		} else {
			print_stacktrace();
			var_dump($user); die;
			Backend::addError('Invalid user to remember');
		}
		return false;
	}
	
	public static function check() {
		if (!empty($_COOKIE['remembered'])) {
			$query = new SelectQuery('PersistUser');
			$persist = $query
				->filter('MD5(CONCAT(`id`, `user_id`, `random`)) = :hash')
				->fetchAssoc(array(':hash' => $_COOKIE['remembered']));
			if ($persist) {
				//Get User
				$class = BackendAccount::getName() . 'Obj';
				$query = BackendAccount::getQuery();
				$query->filter('`users`.`id` = :id');
				$params = array(':id' => $persist['user_id']);

				$User = new $class();
				$User->load(array('query' => $query, 'parameters' => $params, 'mode' => 'object'));
				
				if ($User) {
					$User->object->roles = empty($User->object->roles) ? array() : explode(',', $User->object->roles);
					$_SESSION['user'] = $User->object;
					//Remove, and reremember
					if (self::remember($User->object)) {
						$query = new DeleteQuery('PersistUser');
						$query
							->filter('`id` = :id')
							->limit(1);
						$query->execute(array(':id' => $persist['id']));
					} else {
						Backend::addError('Could not reremember');
					}
					return $User->object;
				} else {
					//Backend::addError('Invalid remembered user');
				}
			}
		}
		return false;
	}	
	
	public static function forget($user) {
		$query = new DeleteQuery('PersistUser');
		$query->filter('`user_id` = :id');
		$query->execute(array(':id' => $user->id));
	}

	public static function hook_start() {
		if (!BackendAccount::checkUser()) {
			PersistUser::check();
		}
	}

	public static function install(array $options = array()) {
		$toret = parent::install($options);
		$toret = Hook::add('start', 'pre', __CLASS__, array('global' => true, 'sequence' => 100)) && $toret;
		return $toret;
	}
}
