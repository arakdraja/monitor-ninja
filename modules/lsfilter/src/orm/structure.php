<?php

$tables = array (
	'object' =>
	array(
		/* Special case, root objects */
		'class' => 'Object',
		'source' => 'Root',
		'structure' => array()
	),
	'saved_filters' =>
	array(
		'class' => 'SavedFilter',
		'source' => 'MySQL',
		'table' => 'ninja_saved_filters',
		'writable' => true,
		'key' => array('id'),
		'default_sort' => array('filter_name asc'),
		'structure' => array(
			'id' => 'int',
			'username' => 'string',
			'filter_name' => 'string',
			'filter_table' => 'string',
			'filter' => 'string',
			'filter_description' => 'string'
		),
	),
	'settings' =>
	array(
		'class' => 'Setting',
		'source' => 'MySQL',
		'table' => 'ninja_settings',
		'writable' => true,
		'key' => array('id'),
		'default_sort' => array('type asc'),
		'structure' => array(
			'id' => 'int',
			'username' => 'string',
			'type' => 'string',
			'page' => 'string',
			'setting' => 'string',
			'widget_id' => 'int'
		),
	)
);
