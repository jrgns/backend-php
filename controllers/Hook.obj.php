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
class Hook extends TableCtl
{
    public static function add($hook, $type, $class, array $options = array()) {
        if (!Backend::getDB('default')) {
            return false;
        }

        $mode        = array_key_exists('mode', $options)        ? $options['mode'] : '*';
        $name        = array_key_exists('name', $options) ? $options['name']
                                        : ucwords($class . ' ' . $type . ' ' . $hook);
        $description = array_key_exists('description', $options) ? $options['description'] : '';
        $method      = array_key_exists('method', $options)      ? $options['method']
                                        : 'hook_' . ($type == 'post' ? 'post_' : '') . strtolower($hook);
        $global      = array_key_exists('global', $options)      ? $options['global'] : 0;
        $sequence    = array_key_exists('sequence', $options)    ? $options['sequence'] : 0;

        //Certain hooks should be global
        if (in_array($hook, array('init'))) {
            $global = 1;
        }

        $data = array(
            'class'       => $class,
            'hook'        => $hook,
            'type'        => $type,
            'mode'        => $mode,
            'name'        => $name,
            'description' => $description,
            'method'      => $method,
            'global'      => $global,
            'sequence'    => $sequence,
        );
        $hook = new HookObj();
        if ($hook->replace($data)) {
            Backend::addSuccess('Added hook ' . $name . '(' . $class . '::' . $method . ')');
            $toret = true;
        } else {
            Backend::addError('Could not add hook ' . $name . '(' . $class . '::' . $method . ')');
            $toret = false;
        }
        return $toret;
    }

    public static function get($hook, $type = 'pre') {
        if (!BACKEND_WITH_DATABASE) {
            return false;
        }

        $params = array(':type' => $type, ':hook' => $hook);
        $query = new SelectQuery('Hook');
        $query
            ->leftJoin('Component', array('`hooks`.`class` = `components`.`name`'))
            ->filter('`hooks`.`hook` = :hook')
            ->filter('`hooks`.`type` = :type')
            ->filter('`hooks`.`active` = 1')
            ->filter('`components`.`active` = 1');
        if (Controller::$area) {
            $query->filter('`global` = 1 OR `class` = :area');
            $params[':area'] = Controller::$area;
        }
        if (Controller::$view && Controller::$view->mode) {
            $query->filter('`mode` IN (:mode, \'*\')');
            $params[':mode'] = Controller::$view->mode;
        }
        $query->order('`sequence`');
        return $query->fetchAll($params);
    }

    public static function run($hookName, $type, array $parameters = array(), array $options = array()) {
        //Specify what should be returned if the result of the hook is NULL
        //This ensures that hooks return what they should, even if the hook doesn't run
        $result = null;
        if (count($parameters)) {
            $returnIndex = array_key_exists('return_index', $options) ? $options['return_index'] : null;
            if (is_null($returnIndex)) {
                //Default to the first parameter passed
                $result = reset($parameters);
            } else {
                $result = $parameters[$returnIndex];
            }
        }
        //If the result was specified, return that
        $result = array_key_exists('toret', $options) ? $options['toret'] : $result;
        if ($hooks = self::get($hookName, $type)) {
            foreach ($hooks as $hook) {
                //Check if the hook is active and callable
                if (Component::isActive($hook['class'])) {
                    if (is_callable(array($hook['class'], $hook['method']))) {
                        if (Controller::$debug >= 2) {
                            $notice = 'Running ' . $hook['class'] . '::' . $hook['method']
                                        . ' for hook ' . $hookName . '-' . $type;
                            Backend::addNotice($notice);
                        }
                        //Run it
                        $toret = call_user_func_array(array($hook['class'], $hook['method']), $parameters);
                        if (!is_null($toret)) {
                            $result = $toret;
                            if (count($parameters) && !is_null($returnIndex)) {
                                $parameters[$returnIndex] = $toret;
                            }
                        }
                    } else if (Controller::$debug) {
                        Backend::addNotice('Undefined Hook: ' . $hook['class'] . '::' . $hook['method']);
                    }
                }
            }
        }
        return $result;
    }

    public static function install(array $options = array()) {
        $options['drop_table']    = array_key_exists('drop_table', $options) ? $options['drop_table'] : true;
        $options['install_model'] = array_key_exists('install_model', $options) ? $options['install_model'] : false;
        $toret = parent::install($options);
        return $toret;
    }

    /**
     * Make sure the hooks table exists so that other components can add hooks with installation
     */
    public static function pre_install() {
        if (!Backend::getDB('default')) {
            return true;
        }

        $result = self::installModel(__CLASS__ . 'Obj');
        return $result;
    }
}
