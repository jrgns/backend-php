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
	
	public static function getComments($table, $table_id) {
		$query = new SelectQuery('`comments`');
		$query
			->field(array('`comments`.*, `users`.`username`, `users`.`email`'))
			->leftJoin('`users`', '`comments`.`user_id` = `users`.`id`')
			->filter('`comments`.`foreign_id` = :id')
			->filter('`comments`.`foreign_table` = :table')
			->filter('`comments`.`active` = 1')
			->order('IF(`comments`.`in_reply_to` = 0, `comments`.`id`, `comments`.`in_reply_to`) DESC');
		return $query->fetchAll(array(':table' => $table, ':id' => $table_id));
	}
	
	public function action_create() {
		if (is_post() && !empty($_POST['obj'])) {
			$parameters = get_previous_parameters();
			$_POST['obj']['foreign_id']    = empty($_POST['obj']['foreign_id'])    ? reset($parameters)              : $_POST['obj']['foreign_id'];
			$_POST['obj']['foreign_table'] = empty($_POST['obj']['foreign_table']) ? table_name(get_previous_area()) : $_POST['obj']['foreign_table'];
			//If we don't have a logged in user, create a dummy account
			if (!BackendAccount::checkUser()) {
				$query = new SelectQuery('users');
				$query->filter('`email` = :email');
				$old_user = $query->fetchAssoc(array(':email' => $_POST['user']['email']));
				switch (true) {
				case $old_user && $old_user['confirmed'] && $old_user['active']:
					//Attribute quote to user? Seems risque, actually, if I know a user's email address, I can just attribute to him. Auth first
					Backend::addError('Comment not added. Please login first');
					return false;
					break;
				case $old_user && !$old_user['confirmed'] && $old_user['active']:
					//Unregistered user commented before
					$_POST['obj']['user_id'] = $old_user['id'];
					break;
				default:
				case !$old_user:
					$user_data = array(
						'name'      => $_POST['user']['name'],
						'surname'   => '',
						'email'     => $_POST['user']['email'],
						'website'   => $_POST['user']['website'],
						//TODO username will be email address for a beginning, user can then change it. ONLY if username == email address
						'username'  => $_POST['user']['email'],
						'password'  => get_random(),
						'confirmed' => 0,
						'active'    => 1,
					);
					$user = new BackendAccountObj();
					if ($user->create($user_data)) {
						$_POST['obj']['user_id'] = $user->array['id'];

						$url = SITE_LINK . '?q=account/confirm/' . $object->array['salt'];
						$app_name = Backend::getConfig('application.Title');
						$message = <<< END
Hi {$user->array['name']}!

Thank you for your comment on $app_name. An account has automatically been created for you. To activate it, please click on the following link:

<a href="$url">$url</a>

If the link doesn't work, copy the following URL into your browser's location bar:
$url

Please note that this account will be deleted if it isn't confirmed in a weeks time.

Regards
END;
						send_email($user->array['email'], 'Thank you for your comment.', $message);
					}
					break;
				}
			}
		}
		return parent::action_create();
	}
	
	public function html_create($result) {
		switch (true) {
		case $result instanceof DBObject:
			Controller::redirect('?q=' . class_for_url($result->array['foreign_table']) . '/' . $result->array['foreign_id']);
			break;
		} 
		Controller::redirect('previous');
		return $result;
	}
	
	public static function hook_form($result) {
		if (in_array(Controller::$action, array('create', 'update'))) {
			$comments = self::getComments($result, Controller::$parameters[0]);
			//Don't add Content, only render it.
			Backend::add('obj_comments', $comments);
			echo Render::renderFile('comment_form.tpl.php');
		}
		return $object;
	}

	public static function hook_post_display($object) {
		if ($object instanceof DBObject && in_array(Controller::$action, array('display'))) {
			$comments = self::getComments(table_name($object), $object->array['id']);
			Backend::addContent(Render::renderFile('comments.tpl.php', array('comment_list' => $comments)));
		}
		return $object;
	}

	
	public static function install(array $options = array()) {
		$toret = parent::install($options);
		
		$toret = Hook::add('form',    'post', __CLASS__, array('global' => true)) && $toret;
		$toret = Hook::add('display', 'post', __CLASS__, array('global' => true)) && $toret;
		$toret = Hook::add('update',  'post', __CLASS__, array('global' => true)) && $toret;
		$toret = Hook::add('create',  'post', __CLASS__, array('global' => true)) && $toret;
		return $toret;
	}
}
