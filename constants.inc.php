<?php
define('REGEX_SQL_FUNCTION', '/(NOW|IFNULL|COUNT|AVG|SUM|MAX|MIN|CASE|IF|GROUP_CONCAT|TRIM)\(.*\)|NULL$/');
define('REGEX_LINKS', '/\b(?:(?:(https?|ftp|file):\/\/)|(www\.|ftp\.))([-A-Za-z0-9+&@#\/%=~_|$?!:,.]*[A-Za-z0-9+&@#\/%=~_|$])/');
//define('REGEX_MAKE_COUNT_QUERY', '/(SELECT\s+).*(\s+|\n)(FROM.*)(\s+|\n)(LIMIT\s.*)$/i');
define('REGEX_MAKE_COUNT_QUERY', '/(SELECT\s+).*?(\s+)(FROM.*)\s+(LIMIT.*)?$/is');

if (!defined('E_DEPRECATED')) {
	define('E_DEPRECATED', 8192);
}
if (!defined('E_USER_DEPRECATED')) {
	define('E_USER_DEPRECATED', 16384);
}
