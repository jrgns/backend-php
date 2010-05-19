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
define('WEB_FOLDER', BACKEND_FOLDER . '/public');
//define('SITE_FOLDER', APP_FOLDER . '/sites/liveserver.com');

switch ($_SERVER['HTTP_HOST']) {
case 'www.liveserver.com':
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
	require(BACKEND_FOLDER . '/classes/Backend.obj.php');
	Backend::init();
	Backend::add('start', $start);
	Controller::serve();
}
