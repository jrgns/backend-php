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
class Comment extends TableCtl {
	public static function addComments($table, $table_id, array $comments) {
		$toret = true;
		$Comment = new CommentObj();
		foreach($comments as $comment) {
			$data = array(
				'active'    => 1,
				'title'      => $comment['title'],
				'content'     => $comment['content'],
				'foreign_id'   => $table_id,
				'foreign_table' => table_name($table),
			);
			$toret = $Comment->create($data, array('ignore' => true)) && $toret;
		}
		return $toret;
	}
	
	public static function getComments($table = false, $table_id = false, $limit = false) {
		$query = new SelectQuery('Comment');
		$query
			->field(array('`comments`.*, `backend_users`.`username`, `backend_users`.`email`'))
			->leftJoin('BackendUser', '`comments`.`user_id` = `backend_users`.`id`')
			->filter('`comments`.`active` = 1')
			->order('IF(`comments`.`in_reply_to` = 0, `comments`.`id`, `comments`.`in_reply_to`) DESC');
		$params = array();
		if ($table) {
			$query->filter('`comments`.`foreign_table` = :table');
			$params[':table'] = $table;
		}
		if ($table_id) {
			$query->filter('`comments`.`foreign_id` = :table_id');
			$params[':table_id'] = $table_id;
		}
		if ($limit) {
			$query->limit($limit);
		}
		return $query->fetchAll($params);
	}
	
	public function action_create() {
		if (is_post()) {
			$parameters = get_previous_parameters();
			$object = new CommentObj();
			$object = $object->fromPost();
			$object['foreign_id']    = empty($object['foreign_id'])    ? reset($parameters)              : $object['foreign_id'];
			$object['foreign_table'] = empty($object['foreign_table']) ? table_name(get_previous_area()) : $object['foreign_table'];
			//If we don't have a logged in user, create a dummy account
			if (!BackendUser::check()) {
				$query = new SelectQuery('BackendUser');
				$query->filter('`email` = :email');
				if ($old_user = Controller::getVar('user')) {
					$existing_user = $query->fetchAssoc(array(':email' => $old_user['email']));
				}
				switch (true) {
				case $existing_user && $existing_user['confirmed'] && $existing_user['active']:
					//Attribute quote to user? Seems risque, actually, if I know a user's email address, I can just attribute to him. Auth first
					Backend::addError('Comment not added. Please login first');
					return false;
					break;
				case $existing_user && !$existing_user['confirmed'] && $existing_user['active']:
					//Unregistered user commented before
					$object['user_id'] = $existing_user['id'];
					break;
				default:
				case !$existing_user:
					$user_data = array(
						'name'      => $old_user['name'],
						'surname'   => '',
						'email'     => $old_user['email'],
						'website'   => $old_user['website'],
						//TODO username will be email address for a beginning, user can then change it. ONLY if username == email address
						'username'  => $old_user['email'],
						'password'  => get_random(),
						'confirmed' => 0,
						'active'    => 1,
					);
					
					$user = self::getObject('BackendUser');
					if ($user->create($user_data)) {
						$object['user_id'] = $user->array['id'];

						$url = SITE_LINK . '?q=backend_user/confirm/' . $user->array['salt'];
						$app_name = ConfigValue::get('Title');
						$message = <<< END
Hi {$user->array['name']}!

Thank you for your comment on $app_name. An account has automatically been created for you. To activate it, please click on the following link:

$url

Please note that you don't need to do this for your comments to show, but this account will be deleted if it isn't confirmed in a weeks time.

Regards
END;
						send_email($user->array['email'], 'Thank you for your comment.', $message);
					} else {
						Backend::addError('Could not create user to add Comment');
						return false;
					}
					break;
				}
			}
			$object = array_filter($object, create_function('$var', 'return !is_null($var);'));
			Controller::setVar('obj', $object);
		}
		return parent::action_create();
	}
	
	public function html_create($result) {
		switch (true) {
		case $result instanceof DBObject:
			Controller::redirect('?q=' . class_for_url($result->array['foreign_table']) . '/' . $result->array['foreign_id']);
			break;
		} 
		//Controller::redirect('previous');
		return parent::html_create($result);
	}
	
	public static function install(array $options = array()) {
		$toret = parent::install($options);		
		return $toret;
	}
}
