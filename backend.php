#!/usr/bin/php
<?php
function show_usage() {
	echo 'Usage: backend.php GET|POST|PUT|DELETE path [mode] [-data data]' . PHP_EOL;
	die(1);
}

if ($_SERVER['argc'] <= 2) {
	show_usage();
}

//Check and define BACKEND_ and APP_FOLDER
define('SITE_STATE', 'local');
if (empty($_SERVER['BACKEND_FOLDER'])) {
	define('BACKEND_FOLDER', getcwd());
	define('APP_FOLDER', BACKEND_FOLDER . '/webapp');
} else {
	define('BACKEND_FOLDER', $_SERVER['BACKEND_FOLDER']);
	define('APP_FOLDER', getcwd());
}

if (!file_exists(BACKEND_FOLDER . '/classes/Backend.obj.php')) {
	echo 'Please set BACKEND_FOLDER to point to your backend-php installation' . PHP_EOL;
	die(1);
}

define('DEBUG', 1);

//Get the method and the path
$method = strtoupper($_SERVER['argv'][1]);
$path   = $_SERVER['argv'][2];

if (!in_array($method, array('GET', 'POST', 'PUT', 'DELETE'))) {
	show_usage();
}

//Get the mode and the data
$mode = 'json';
$data = false;
if ($_SERVER['argc'] >= 4) {
	if ($_SERVER['argv'][3] == '-data') {
		$data = $_SERVER['argv'][4];
		$mode = array_key_exists(5, $_SERVER['argv']) ? $_SERVER['argv'][5] : $mode;
	} else {
		$mode = $_SERVER['argv'][3];
		$data = array_key_exists(5, $_SERVER['argv']) ? $_SERVER['argv'][5] : $data;
	}
}
if ($data) {
	parse_str($data, $data);
}
if ($mode == 'html') {
	$mode = 'chunk';
}

//Build the complete path
$path = 'q=' . $path;
if ($method == 'get' && is_array($data)) {
	$path .= '&' . urldecode(http_build_query($data));
}
$path .= '&mode=' . $mode;
if (DEBUG > 1) {
	$path .= '&debug=' . (DEBUG - 1);
}
if (DEBUG) {
	echo 'Path: ' . $path . PHP_EOL;
}

//Execute the query
$start = microtime(true);
include(BACKEND_FOLDER . '/classes/Backend.obj.php');
\Backend::add('start', $start);
\Controller::serve($path, $method, $data, false);

echo PHP_EOL;
