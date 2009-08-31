<?php
/**
 * Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * @license http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors:
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 */

define('BACKEND_FOLDER', '/var/www/Jrgn5/backend');
define('APP_FOLDER', BACKEND_FOLDER . '/webapp');
define('SITE_FOLDER', BACKEND_FOLDER . '/public');

switch ($_SERVER['HTTP_HOST']) {
case 'jrgns.net':
	define('SITE_STATE', 'production');
	break;
case 'localhost':
default:
	define('SITE_STATE', 'local');
	break;
}

main();

function main() {
	$start = microtime(true);
	ob_start('ob_gzhandler');
	require(BACKEND_FOLDER . '/classes/Backend.obj.php');
	Backend::init();
	Backend::add('start', $start);
	Controller::serve();
}
