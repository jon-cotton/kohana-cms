<?php

// check that dependencies are installed
if(!class_exists('Yaml'))
{
	// this module requires the Kohana-Yaml module https://github.com/gevans/kohana-yaml
	throw new Kohana_Exception('The CMS module requires the Kohana-Yaml module to be installed and initialised first, please check your bootstrap file');
}

/**
 * set the content paths for non user editable content (default) and user
 * editable content (user), the user content path must be writeable by the web 
 * server (777 if you want to make it easy :)
 */
$cms_config = Kohana::$config->load('cms');
Cms::$default_content_path = Arr::path($cms_config, 'content_paths.default');
Cms::$user_content_path = Arr::path($cms_config, 'content_paths.user');

// route for the set-locale action for switching regions
Route::set('cms_set_locale', 'set-locale/<locale>',
	array(
		'locale' => '[a-z]{2}-[a-z]{2}'
	))->defaults(array(
		'controller' => 'cms',
		'action' => 'set_locale'
));