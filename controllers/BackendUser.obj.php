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
class BackendUser extends TableCtl
{
    const ERR_DISABLED_COOKIES            = 1;
    const ERR_UNMATCHED_USERNAME_PASSWORD = 2;
    const ERR_MISSING_USERNAME_PASSWORD   = 3;
    public static $error_msgs = array(
        self::ERR_DISABLED_COOKIES => 'Please enable cookies. This site can\'t function properly without them',
        self::ERR_UNMATCHED_USERNAME_PASSWORD => 'Username and password does not match',
        self::ERR_MISSING_USERNAME_PASSWORD   => 'Please supply a username and password',
    );

    protected static $name = false;

    protected static $current_user = false;

    public static function getCurrentUser() {
        return self::$current_user;
    }

    public static function getCurrentUserID() {
        if (self::$current_user) {
            return self::$current_user->id;
        }
        return false;
    }

    public static function authenticate($username, $password, $returnQuery = false) {
        $query = self::getQuery();
        $query
            ->filter('`backend_users`.`username` = :username OR `backend_users`.`Mobile` = :username
OR `backend_users`.`Email` = :username')
            ->filter('`backend_users`.`password` = MD5(CONCAT(`backend_users`.`salt`, :password, :salt))')
            ->limit(1);
        $parameters = array(
            ':username' => $username,
            ':password' => $password,
            ':salt'     => Controller::$salt
        );
        return $returnQuery ? array($query, $parameters) : $query->fetchAssoc($parameters);
    }

    public static function getQuery() {
        $query = new SelectQuery('BackendUser');
        $query
            ->filter('`backend_users`.`active` = 1')
            ->filter('`backend_users`.`confirmed` = 1')
            ->order('`backend_users`.`username`');
        return $query;
    }

    function post_login($username, $password) {
        if (self::check()) {
            return true;
        }
        if ($username && $password) {
            list($query, $params) = self::authenticate($username, $password, true);

            $user = self::getObject();
            $user->read(array('query' => $query, 'parameters' => $params, 'mode' => 'object'));
            if ($user->object) {
                session_regenerate_id();
                $result = $user->object;
                $_SESSION['BackendUser'] = $user->object;
                if (Component::isActive('PersistUser')) {
                    PersistUser::remember($user->object);
                }
                return $user;
            } else {
                return 2;
            }
        } else if (empty($_SESSION['cookie_is_working'])) {
            return 1;
        } else {
            return 3;
        }
    }

    function html_login($result) {
        switch (true) {
        case $result instanceof DBObject:
            Backend::addSuccess('Welcome to ' . ConfigValue::get('Title') . '!');
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
        case is_numeric($result):
            Backend::addError(self::getError($result));
            break;
        default:
            break;
        }
        Backend::addContent(Render::file('loginout.tpl.php'));
    }

    public function post_logout() {
        self::$current_user = false;
        if (array_key_exists('BackendUser', $_SESSION)) {
            if (Component::isActive('PersistUser')) {
                PersistUser::forget($_SESSION['BackendUser']);
            }
            $_SESSION = array();
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 42000, '/');
            }
            session_destroy();
        }
        Controller::redirect('?q=');
        return true;
    }

    public function html_display($result) {
        parent::html_display($result);
        if ($result instanceof DBObject) {
            if ($_SESSION['BackendUser']->id == $result->array['id']) {
                Backend::add('Sub Title', 'My Account');
                Backend::addContent(Render::file('loginout.tpl.php'));
            } else {
                Backend::add('Sub Title', 'User: ' . $result->array['username']);
            }
        }
    }

    public function html_update($result) {
        parent::html_update($result);
        if ($result instanceof DBObject) {
            if ($_SESSION['BackendUser']->id == $result->array['id']) {
                Backend::add('Sub Title', 'Manage my Account');
            } else {
                Backend::add('Sub Title', 'Update User ' . $result->array['username']);
            }
        }
    }

    /**
     * @todo Refactor this so that an admin user can do backend_user/change_password/$username/$new_password
     */
    public function post_change_password() {
        $current  = Controller::getVar('current_password');
        $password = Controller::getVar('password');
        $confirm  = Controller::getVar('confirm_password');
        if ($confirm != $password) {
            Backend::addError('New password doesn\'t match');
            return false;
        }
        if (!($user = self::check())) {
            Backend::addError('Invalid User (Anonymous)');
            return false;
        }
        $userObj = self::getObject(get_class($this), $user->id);
        if (!$userObj->array) {
            Backend::addError('Invalid User');
            return false;
        }
        list($query, $params) = self::authenticate($user->username, $current, true);
        if (!$query->fetchAssoc($params)) {
            Backend::addError('Incorrect current password provided');
            return false;
        }
        if (!$userObj->update(array('password' => $password))) {
            Backend::addError('Could not update password');
            return false;
        }
        //Reread the user
        $userObj->read(array('query' => $query, 'parameters' => $params, 'mode' => 'object'));
        if ($userObj->object) {
            session_regenerate_id();
            $_SESSION['BackendUser'] = $userObj->object;
            if (Component::isActive('PersistUser')) {
                PersistUser::remember($userObj->object);
            }
        }
        return true;
    }

    public function html_change_password($result) {
        if (is_post() && $result) {
            Backend::addSuccess('Password updated');
            Controller::redirect('?q=backend_user/display');
        }
        Backend::addContent(Render::file('backend_user.change_password.tpl.php'));
        return $result;
    }

    public function get_roles($userId = false) {
        if ($userId) {
            $user = BackendUser::retrieve($userId);
        } else {
            $user = (array)BackendUser::check();
        }
        if (!$user) {
            return false;
        }
        Controller::$parameters[0] = $user['id'];
        return $user['roles'];
    }

    public function post_roles($userId) {
        $roles = Controller::getVar('roles');
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if (GateKeeper::assign($role, 'users', $userId)) {
                    Backend::addSuccess('Added User to ' . $role);
                } else {
                    Backend::addError('Could not add User to ' . $role);
                }
            }
        }
        Controller::redirect();
    }

    public function html_roles($userRoles) {
        Backend::add('Sub Title', 'User Roles');
        $vars = array(
            'user_id'      => Controller::$parameters[0],
            'user_roles'   => $userRoles,
            'system_roles' => Role::retrieve(false, 'list'),
        );
        Backend::addContent(Render::file('backend_user.roles.tpl.php', $vars));
    }

    public function get_super_signup() {
        //Check if a super user already exists
        if (self::hasSuperUser()) {
            Backend::addError('Super User already created');
            return true;
        }
        return false;
    }

    public function post_super_signup() {
        //Check if a super user already exists
        if (self::hasSuperUser()) {
            Backend::addError('Super User already created');
            return false;
        }
        $object = self::getObject(get_class($this));
        $data   = $object->fromRequest();
        if ($object->create($data)) {
            Backend::addSuccess('Super User signed up!');
            $this->postSignup($object);
            return $object;
        } else {
            Backend::addError('Could not sign up the Super User: ' . $object->error_msg);
        }
        return false;
    }

    public function html_super_signup($result) {
        if ($result instanceof DBObject) {
            //Give option after successful signup to edit details
            Backend::addNotice('You can edit the details of the super user <a href="?q=backend_user/edit/1">here</a>');
            Controller::redirect('?q=home');
        } else if (!$result) {
            Backend::addContent(Render::file('backend_user.super_signup.tpl.php'));
        } else {
            Controller::redirect('?q=home');
        }
    }

    public function get_signup() {
        $object = self::getObject(get_class($this));
        $data   = $object->fromRequest();
        Backend::add('obj_values', $data);
        return true;
    }

    public function post_signup() {
        $object = self::getObject(get_class($this));
        $data = $object->fromRequest();
        if ($object->create($data)) {
            Backend::addSuccess('Signed up!');
            $this->postSignup($object);
            return $object;
        } else {
            Backend::addError('Could not sign you up. Please try again later!');
        }
        Backend::add('obj_values', $data);
        return false;
    }

    public function html_signup($result) {
        Backend::add('Sub Title', 'Signup to ' . ConfigValue::get('Title'));
        switch (true) {
        case ($result instanceof DBObject):
            //Successful signup, redirect
            if (!empty($_SESSION['bookmark'])) {
                $bookmark = $_SESSION['bookmark'];
                unset($_SESSION['bookmark']);
            } else {
                //TODO Make this configurable
                $bookmark = '?q=';
            }
            Controller::redirect($bookmark);
            break;
        case ($result):
        default:
            Backend::add('Object', $result);
            Backend::addContent(Render::file('backend_user.signup.tpl.php'));
            break;
        }
        return $result;
    }

    /**
     * Confirm a user account
     *
     * @TODO Find a way to disable an account permanently, in other words, prevent this code from working in some way
     */
    public function get_confirm($salt) {
        return $this->post_confirm($salt);
    }

    public function post_confirm($salt) {
        $user = self::checkSalt($salt);
        $data = array(
            'confirmed' => true,
        );
        $data = Hook::run('user_confirm', 'pre', array($user, $data), array('toret' => $data));
        if (!$data) {
            return false;
        }

        if ($user->update($data) === false) {
            return false;
        }

        Hook::run('user_confirm', 'post', array($user), array('toret' => true));
        return true;
    }

    public static function checkSalt($salt) {
        $query = new SelectQuery('BackendUser');
        $query
            ->filter('MD5(CONCAT(:app_salt, `salt`)) = :salt')
            ->filter('`confirmed` = 0')
            ->filter('`active` = 1');
        $user = $query->fetchAssoc(array(
            ':salt'     => $salt,
            ':app_salt' => Controller::$salt
        ));
        if (!$user) {
            return false;
        }
        $user = self::getObject(get_called_class(), $user['id']);
        if (!$user->array) {
            return false;
        }
        return $user;
    }

    public function html_confirm($result) {
        if ($result) {
            Backend::addSuccess('Your user account has been confirmed. Please login.');
            Controller::redirect('?q=' . class_for_url(get_called_class()) . '/login');
        } else {
            Backend::addError('Could not confirm your account at the moment. Please try again later');
            Controller::redirect('?q=');
        }
        return $result;
    }

    public static function hook_post_user_confirm(BackendUserObj $user) {
        send_email(ConfigValue::get('author.Email', ConfigValue::get('application.Email', 'info@' . SITE_DOMAIN)),
            'New User: ' . $user['username'],
            var_export($user, true));
        return true;
    }

    public static function hook_post_init() {
        if (Controller::$mode == Controller::MODE_EXECUTE) {
            if ($user = self::checkExecuteUser()) {
                $_SESSION['BackendUser'] = $user;
                self::$current_user      = $user;
            }
        } else {
            //Check if the current user has permission to execute this action
            $id = count(Controller::$parameters) ? reset(Controller::$parameters) : 0;
            $permission = Permission::check(Controller::$action, Controller::$area, $id);
            //If not, and CheckHTTPAuth is true, send the HTTP headers
            if (
                !$permission
                && ConfigValue::get('CheckHTTPAuth', false)
                && ConfigValue::get('CheckHTTPAuthIn:' . Controller::$view->mode, true)
            ) {
                if ($user = self::processHTTPAuth()) {
                    session_regenerate_id();
                    $_SESSION['BackendUser']   = $user;
                    self::$current_user = $user;
                }
            }
        }
    }

    public static function hook_start() {
        $user = self::check();
        if ($user && in_array('superadmin', $user->roles) && !Backend::getConfig('application.NoSuperWarning')) {
            Backend::addInfo('You are the super user. Be carefull, careless clicking costs lives...');
        }
        self::$current_user = $user;
        Backend::add('BackendUser', $user);
    }

    public static function hook_post_finish() {
        $_SESSION['cookie_is_working'] = true;
    }

    public function postSignup($object, array $options = array()) {
        if (ConfigValue::get('application.confirmUser') && empty($object->array['confirmed'])) {
            $this->sendConfirmation($object);
        }
    }

    public static function getHTTPAuth() {
        $query = new SelectQuery('User');
        $query->field('password')->filter('`username` = ?');
        //It's tricky, because the password is not stored in plain text.
        //Use the hash as the password for HTTP Auth requests
        return DigestAuthentication::getInstance(array($query, 'fetchColumn'));
    }

    public static function processHTTPAuth() {
        $auth = self::getHTTPAuth();

        if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
            $auth->challenge();
        } else if ($username = $auth->process()) {
            $query = self::getQuery();
            $query->filter('`username` = :username');
            $user = TableCtl::getObject('BackendUser');
            $params = array(':username' => $username);
            $user->read(array('query' => $query, 'parameters' => $params, 'mode' => 'object'));
            if ($user->object) {
                return $user->object;
            }
        }
        return false;
    }

    public static function checkExecuteUser() {
        $username = Backend::get('ExecuteUser');
        $query    = self::getQuery();
        $query->filter('`username` = :username');

        $user   = TableCtl::getObject('BackendUser');
        $params = array(':username' => $username);
        $user->read(array('query' => $query, 'parameters' => $params, 'mode' => 'object'));
        if ($user->object) {
            return $user->object;
        }
        return false;
    }

    protected function sendConfirmation($object) {
        $url = SITE_LINK . '?q=' . class_for_url(get_called_class()) . '/confirm/'
                . md5(Controller::$salt . $object->array['salt']);
        $appName = ConfigValue::get('Title');
        $salutation = 'Hi';
        if (!empty($object->array['name'])) {
            $salutation .= ' ' . $object->array['name'];
        }
        $salutation .= '!';
        $message = <<< END
$salutation

Your signup to $appName was successful. Please click on the following link to activate the account:

$url

Please note that this account will be deleted if it isn't confirmed in a weeks time.

Regards
END;
        if (Controller::$debug) {
            var_dump('Confirm Link: ' . $url);
            var_dump('Confirm Email:');
            echo "<pre>$message</pre>";
        }
        $result = send_email($object->array['email'], 'Confirmation Email', $message);
        if ($result) {
            Backend::addSuccess('A confirmation email as been sent to your email address.
Please click on the link in the email to confirm your account');
        }
        return $result;
    }

    public static function setupAnonymous() {
        $_SESSION['BackendUser'] = new stdClass();
        $_SESSION['BackendUser']->id = 0;
        $_SESSION['BackendUser']->name = 'Anon';
        $_SESSION['BackendUser']->surname = 'Ymous';
        $_SESSION['BackendUser']->email = null;
        $_SESSION['BackendUser']->mobile = null;
        $_SESSION['BackendUser']->username = null;
        $_SESSION['BackendUser']->password = null;
        $_SESSION['BackendUser']->active = null;
        $_SESSION['BackendUser']->modified = null;
        $_SESSION['BackendUser']->added = null;
        $_SESSION['BackendUser']->roles = array('anonymous');
        return $_SESSION['BackendUser'];
    }

    public static function check() {
        if (!empty(self::$current_user) && is_object(self::$current_user)) {
            return self::$current_user;
        }
        if (
            !empty($_SESSION['BackendUser'])
            && is_object($_SESSION['BackendUser'])
            && $_SESSION['BackendUser']->id > 0
        ) {
            return $_SESSION['BackendUser'];
        }
        call_user_func(array(get_called_class(), 'setupAnonymous'));
        //Return false as the user is obviously anonymous
        return false;
    }

    public static function hasSuperUser() {
        if (!Backend::getDB('default')) {
            return false;
        }
        $query = new SelectQuery('BackendUser');
        $query
            ->filter('`id` = 1');
        return (bool)$query->fetchAssoc();
    }

    /**
     * Return all users within a specific role
     */
    public static function withRole($roles) {
        if (!is_array($roles)) {
            $roles = array($roles);
        }
        $roleObj = new RoleObj();
        $query = new SelectQuery('Role');
        $query->filter('`name` IN (' . implode(', ', array_pad(array(), count($roles), '?')) . ')');
        $roleObj->read(array('query' => $query, 'parameters' => $roles));
        if (!$roleObj->list) {
            return false;
        }
        $roleIds = array_flatten($roleObj->list, null, 'id');

        $query = self::getQuery();
        $query
            ->distinct()
            ->field('`' . self::getTable() . '`.*')
            ->leftJoin('Assignment', array('`access_type` = "users"', '`access_id` = `' . self::getTable() . '`.`id`'))
            ->filter('`role_id` IN (' . implode(', ', array_pad(array(), count($roleIds), '?')) . ')');
        return $query->fetchAll($roleIds);
    }

    public static function getGravatar($email, $size = 120, $default = false) {
        $default = $default ? $default : ConfigValue::get('DefaultGravatar', 'identicon');
        $result = 'http://www.gravatar.com/avatar.php?gravatar_id';
        $result .= md5(strtolower($email)) . '&size=' . $size . '&d=' . $default;
        return $result;
    }

    public function checkPermissions(array $options = array()) {
        $result = parent::checkPermissions($options);
        if (!$result && in_array(Controller::$action, array('update', 'display'))) {
            $result = Controller::$parameters[0] == $_SESSION['BackendUser']->id || Controller::$parameters[0] == 0;
            //TODO This should go into a permission denied hook
            if (!$result) {
                $redirect = '?q=' . class_for_url(get_called_class()) . '/'
                . Controller::$action . '/' . $_SESSION['BackendUser']->id;
                Controller::redirect($redirect);
            }
        }
        return $result;
    }

    public function daily(array $options = array()) {
        if (get_class($this) == 'BackendUser') {
            return self::purgeUnconfirmed();
        }
    }

    public function weekly(array $options = array()) {
        if (get_class($this) == 'BackendUser') {
            $result = self::userStats();
        }
        return true;
    }

    public static function purgeUnconfirmed() {
        $query = new DeleteQuery('BackendUser');
        $query
            ->filter('`confirmed` = 0')
            ->filter('`added` < DATE_SUB(DATE(NOW()), INTERVAL 1 WEEK)');
        $deleted = $query->execute();
        Backend::addSuccess($deleted . ' unconfirmed users deleted');
        if ($deleted) {
            send_email(ConfigValue::get('author.Email', ConfigValue::get('application.Email', 'info@' . SITE_DOMAIN)),
                'Unconfirmed Users purged: ' . $deleted,
            $deleted . ' users were deleted from the database.
They were unconfirmed, and more than a week old

Site Admin
');
        }
        return true;
    }

    public static function userStats() {
        $msg = array();
        $query = new SelectQuery('BackendUser');
        $query
            ->field('COUNT(*) AS `Total`, SUM(IF(TO_DAYS(NOW()) - TO_DAYS(`added`) < 7, 1, 0)) AS `New`')
            ->filter('`active` = 1')
            ->filter('`confirmed` = 1');
        if ($stats = $query->fetchAssoc()) {
            $msg[] = 'There are a total of ' . $stats['Total'] . ' **active** users,
of which ' . $stats['New'] . ' signed up in the last 7 days';
        }
        $query = new SelectQuery('BackendUser');
        $query
            ->field('COUNT(*) AS `Total`, SUM(IF(TO_DAYS(NOW()) - TO_DAYS(`added`) < 7, 1, 0)) AS `New`')
            ->filter('`active` = 1')
            ->filter('`confirmed` = 1');
        if ($stats = $query->fetchAssoc()) {
            $msg[] = 'There are a total of ' . $stats['Total'] . ' **unconfirmed** users,
of which ' . $stats['New'] . ' signed up in the last 7 days';
        }
        $msg = implode(PHP_EOL . PHP_EOL, $msg);
        send_email(ConfigValue::get('author.Email', ConfigValue::get('application.Email', 'info@' . SITE_DOMAIN)),
            'User stats for ' . Backend::get('Title'),
            $msg);
        return true;
    }

    public static function checkParameters($parameters) {
        $parameters = parent::checkParameters($parameters);
        switch(Controller::$action) {
        case 'login':
            if (empty($parameters[0])) {
                $parameters[0] = Controller::getVar('username');
            }
            if (empty($parameters[1])) {
                $parameters[1] = Controller::getVar('password');
            }
            break;
        case 'confirm':
            if (empty($parameters[0])) {
                $parameters[0] = Controller::getVar('salt');
            }
        case 'signup':
            if (array_key_exists('user', $_SESSION) && $_SESSION['BackendUser']->id > 0) {
                Controller::setAction('display');
            }
            break;
        case 'update':
        case 'display':
            if (array_key_exists('BackendUser', $_SESSION) && $_SESSION['BackendUser']->id > 0) {
                //If empty, set it to the current user
                if (empty($parameters['0'])) {
                    $parameters[0] = $_SESSION['BackendUser']->id;
                }
                //If not set to current user, and user doesn't have permissions, set to current user
                if (
                    $parameters[0] != $_SESSION['BackendUser']->id
                    && !Permission::check('manage', class_for_url(get_called_class()))
                    && Permission::check(Controller::$action, class_for_url(get_called_class()))
                ) {
                    $parameters[0] = $_SESSION['BackendUser']->id;
                }
            }
            break;
        }
        return $parameters;
    }

    public static function install(array $options = array()) {
        $options['install_model'] = array_key_exists('install_model', $options) ? $options['install_model'] : true;
        $result = parent::install($options);

        if (!Backend::getDB('default')) {
            return $result;
        }
        $result = Hook::add('init', 'post', get_called_class(), array('global' => true, 'sequence' => 0)) && $result;
        $result = Hook::add('start', 'pre', get_called_class(), array('global' => true)) && $result;
        $result = Hook::add('finish', 'post', get_called_class(), array('global' => true)) && $result;

        $result = Permission::add('anonymous', 'super_signup', get_called_class()) && $result;
        $result = Permission::add('anonymous', 'signup', get_called_class()) && $result;
        $result = Permission::add('anonymous', 'confirm', get_called_class()) && $result;
        $result = Permission::add('anonymous', 'login', get_called_class()) && $result;

        $result = Permission::add('authenticated', 'login', get_called_class()) && $result;
        $result = Permission::add('authenticated', 'logout', get_called_class()) && $result;
        $result = Permission::add('authenticated', 'display', get_called_class()) && $result;
        $result = Permission::add('authenticated', 'update', get_called_class()) && $result;
        $result = Permission::add('authenticated', 'change_password', get_called_class()) && $result;
        return $result;
    }
}
