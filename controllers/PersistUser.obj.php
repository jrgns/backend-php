<?php
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
				if (setcookie('remembered', $hash, time() + 60 * 60 * 24 * 14, SITE_SUB_FOLDER)) {
					return true;
				} else {
					Backend::addError('Could not set cookie to remember login');
					$query = new CustomQuery('DELETE FROM `persist_users` WHERE `id` = :id LIMIT 1');
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
				$user = new $class($persist['user_id'], array('load_mode' => 'object'));
				if ($user) {
					$_SESSION['user'] = $user->object;
					//Remove, and reremember
					if (self::remember($user->object)) {
						$query = new CustomQuery('DELETE FROM `persist_users` WHERE `id` = ' . $persist['id'] . ' LIMIT 1');
						$query->execute();
					} else {
						Backend::addError('Could not reremember');
					}
					return true;
				} else {
					//Backend::addError('Invalid remembered user');
				}
			}
		}
		return false;
	}	
}
