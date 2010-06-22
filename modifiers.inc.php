<?php
/**
 * Computer readable isn't always human readable. This file defines functions used to make things more understandable to humans.
 *
 * Copyright (c) 2009 JadeIT cc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * @license http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors:
 * @author J Jurgens du Toit (JadeIT cc) - initial API and implementation
 */

/**
* Returns the plural form of a word.
* Code from http://www.eval.ca/articles/php-pluralize
* Code from http://kuwamoto.org/2007/12/17/improved-pluralizing-in-php-actionscript-and-ror/
* @param string The singular form of a word.
* @return string The plural form of the word.
*/
function pluralize($string) {
	$plural = array(
				array( '/(quiz)$/i',               "$1zes"   ),
				array( '/^(ox)$/i',                "$1en"    ),
				array( '/([m|l])ouse$/i',          "$1ice"   ),
				array( '/(matr|vert|ind)ix|ex$/i', "$1ices"  ),
				array( '/(x|ch|ss|sh)$/i',         "$1es"    ),
				array( '/([^aeiouy]|qu)y$/i',      "$1ies"   ),
				array( '/([^aeiouy]|qu)ies$/i',    "$1y"     ),
				array( '/(hive)$/i',               "$1s"     ),
				array( '/(?:([^f])fe|([lr])f)$/i', "$1$2ves" ),
				array( '/(shea|lea|loa|thie)f$/i', "$1ves"   ),
				array( '/sis$/i',                  "ses"     ),
				array( '/([ti])um$/i',             "$1a"     ),
				array( '/(buffal|tomat|potat|ech|her|vet)o$/i', '$1oes'),
				array( '/(bu)s$/i',                "$1ses"   ),
				array( '/(alias|status)$/i',       "$1es"    ),
				array( '/(octop|vir)us$/i',        "$1i"     ),
				array( '/(ax|test)is$/i',          "$1es"    ),
				array( '/s$/i',                    "s"       ),
				array( '/$/',                      "s"       )
			);

	$irregular = array(
					array( 'move',   'moves'    ),
					array( 'sex',    'sexes'    ),
					array( 'child',  'children' ),
					array( 'man',    'men'      ),
					array( 'person', 'people'   )
	);

	$uncountable = array( 
					'sheep', 
					'fish',
					'series',
					'species',
					'money',
					'rice',
					'information',
					'equipment',
					'data',
					'capital',
					'access',
	);

	// save some time in the case that singular and plural are the same
	if ( in_array( strtolower( $string ), $uncountable ) )
		return $string;

	// check for irregular singular forms
	foreach ( $irregular as $noun ) {
		if ( strtolower( $string ) == $noun[0] )
			return $noun[1];
	}

	// check for matches using regular expressions
	foreach ( $plural as $pattern ) {
		if ( preg_match( $pattern[0], $string ) )
			return preg_replace( $pattern[0], $pattern[1], $string );
	}
	
	return $string;
}

/**
* Returns the singular form of a word.
* Code from http://www.eval.ca/articles/php-pluralize
* Code from http://kuwamoto.org/2007/12/17/improved-pluralizing-in-php-actionscript-and-ror/
* @todo Get a way to avoid the duplication between singularize and pluralize
* @param string The plural form of a word.
* @return string The singular form of the word.
*/
function singularize($string) {
	$singular = array(
					array( '/(quiz)(zes)?$/i'          , "$1" ),
					array( '/(matr)ices$/i'            , "$1ix" ),
					array( '/(vert|ind)ices$/i'        , "$1ex" ),
					array( '/^(ox)(en)?$/i'            , "$1" ),
					array( '/(alias)(es)?$/i'          , "$1" ),
					array( '/(octop|vir)i$/i'          , "$1us" ),
					array( '/(cris|ax|test)es$/i'      , "$1is" ),
					array( '/(shoe)(s)?$/i'            , "$1" ),
					array( '/(o)(es)?$/i'              , "$1" ),
					array( '/(bus)(es)?$/i'            , "$1" ),
					array( '/([m|l])ice$/i'            , "$1ouse" ),
					array( '/(x|ch|ss|sh)(es)?$/i'     , "$1" ),
					array( '/(m)(ovies)?$/i'           , "$1ovie" ),
					array( '/(s)eries$/i'              , "$1eries" ),
					array( '/([^aeiouy]|qu)ies$/i'     , "$1y" ),
					array( '/([lr])ves$/i'             , "$1f" ),
					array( '/(tive)(s)?$/i'            , "$1" ),
					array( '/(hive)(s)?$/i'            , "$1" ),
					array( '/(li|wi|kni)ves$/i'        , "$1fe" ),
					array( '/(shea|loa|lea|thie)ves$/i', "$1f" ),
					array( '/(^analy)ses$/i'           , "$1sis" ),
					array( '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i'  , "$1$2sis" ),
					array( '/([ti])a$/i'               , "$1um" ),
					array( '/(n)ews$/i'                , "$1ews" ),
					array( '/(h|bl)ouses$/i'           , "$1ouse" ),
					array( '/(corpse)(s)?$/i'             , "$1" ),
					array( '/(us)(es)?$/i'                , "$1" ),
					array( '/s$/i'                     , "" )
				);

	$irregular = array(
					array( 'move',   'moves'    ),
					array( 'sex',    'sexes'    ),
					array( 'child',  'children' ),
					array( 'man',    'men'      ),
					array( 'person', 'people'   )
	);

	$uncountable = array( 
					'sheep', 
					'fish',
					'series',
					'species',
					'money',
					'rice',
					'information',
					'equipment',
					'data',
					'capital',
					'access',
	);

	// save some time in the case that singular and plural are the same
	if ( in_array( strtolower( $string ), $uncountable ) )
		return $string;

	// check for irregular singular forms
	foreach ( $irregular as $noun ) {
		if ( strtolower( $string ) == $noun[1] )
			return $noun[0];
	}

	// check for matches using regular expressions
	foreach ( $singular as $key => $pattern ) {
		if ( preg_match( $pattern[0], $string ) ) {
			return preg_replace( $pattern[0], $pattern[1], $string );
		}
	}
	
	return $string;
}

/**
*
*/
function humanize($string) {
	$string = str_replace('_', ' ', $string);
	$string = ucwords($string);
	return $string;
}

/**
 * Return computer safe strings. 
*
* Can't find the original site. New implementation from http://blog.charlvn.za.net/2007/11/php-camelcase-explode-20.html
*/
function computerize($string, $separator = '_') {
	$array = preg_split('/([A-Z][^A-Z]*)/', $string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
	if (is_array($array)) {
		$array = array_map('trim', array_map('strtolower', $array));
		$string = implode($separator, $array);
	}
	return $string;
}

function class_name($string) {
	if (is_object($string)) {
		$string = get_class($string);
	}
	$string = humanize(singularize($string));
	$string = str_replace(' ', '', $string);
	$string = preg_replace('/Obj$/', '', $string);
	return $string;
}

function table_name($string) {
	if (is_object($string)) {
		if ($string instanceof DBObject) {
			return $string->getMeta('table');
		}
		$string = get_class($string);
	}
	$string = preg_replace('/Obj$/', '', $string);
	$string = pluralize(computerize($string));
	return $string;
}

function class_for_url($string) {
	if (is_object($string)) {
		$string = get_class($string);
	}
	return computerize(class_name($string));
}
/**
 * Return the string as a plain text string, no HTML allowed
 */
function plain($string) {
	$string = trim(filter_var($string, FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH & FILTER_FLAG_ENCODE_AMP));
	return $string;
}

/**
 * Return the string with only simple HTML allowed
 */
function simple($string) {
	$string = preg_replace(
		REGEX_LINKS,
		'<a href="http://$2$3">$2$3</a>',
		$string
	);
	$string = trim(strip_tags($string, '<p><a><img><b><i><strong><em><ul><ol><li><dl><dt><dd><code><pre><h1><h2><h3><h4><h5><h6><del><strike><table><tr><th><td><tbody><thead>'));
	
	//TODO $string = strip_attributes($string);
	return $string;
}

function time_elapsed($time) {
	$toret = $time;
	$time = strtotime($time);
	$now  = time();
	$diff = $now - $time;
	switch (true) {
	case $diff < 0:
		$toret = 'some time in the future';
		break;
	case $diff < 15:
		$toret = 'a few seconds ago';
		break;
	case $diff < 60:
		$break = $diff . ' seconds ago';
		break;
	case $diff < 60 * 2:
		$toret = 'a few minutes ago';
		break;
	case $diff < 60 * 60:
		$toret = round($diff / 60) . ' minutes ago';
		break;
	case $diff < 60 * 60 * 6:
		$toret = round($diff / 60 / 60) . ' hours ago';
		break;
	case date('Ymd', $time) == date('Ymd', $now):
		$toret = 'today at ' . date('H:i', $time);
		break;
	case date('Ymd', $time) == date('Ymd', strtotime('yesterday')):
		$toret = 'yesterday at ' . date('H:i', $time);
		break;
	default:
		$toret = 'on ' . date('j F Y \a\t H:i', $time);
		break;
	}
	return $toret;
}

function proper_case_keys($array) {
	$toret = array();
	foreach($array as $key => $value) {
		$toret[ucwords(strtolower($key))] = $value;
	}
	return $toret;
}

