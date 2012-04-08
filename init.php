<?php

Route::set('cms_set_locale', 'set-locale/<locale>',
	array(
		'locale' => '[a-z]{2}-[a-z]{2}'
	)
)->defaults(
	array(
		'controller' => 'cms',
		'action' => 'set_locale'
	)
);