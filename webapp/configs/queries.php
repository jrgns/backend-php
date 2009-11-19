<?php
/**
 * Queries aliases can be defined in this file
 *
 * For example: $queries[product/:num] = 'product/display/$1/';
 * maps mysite.co.za/product/3 to mysite.co.za/product/display/3
 */
$queries = array();
//Edit from here:


//Stop editing here.
//Some system defined queries:
$queries[':table_ctl/:num'] = '$1/display/$2';
return $queries;
