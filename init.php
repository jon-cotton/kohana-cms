<?php

// check that dependencies are installed
if(!class_exists('Yaml'))
{
	// this module requires the Kohana-Yaml module https://github.com/gevans/kohana-yaml
	throw new Kohana_Exception('The CMS module requires the Kohana-Yaml module to be installed and initialised first, please check your bootstrap file');
}

// set the default content path
Cms::$content_path = APPPATH.'content';

// route for the set-locale action for switching regions
Route::set('cms_set_locale', 'set-locale/<locale>',
	array(
		'locale' => '[a-z]{2}-[a-z]{2}'
	))->defaults(array(
		'controller' => 'cms',
		'action' => 'set_locale'
));