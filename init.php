<?php

// check that dependencies are installed
if(!class_exists('YAML'))
{
	// this module requires the kohana-yaml module (https://github.com/gevans/kohana-yaml)
	throw new Kohana_Exception('The CMS module requires the Kohana-Yaml module to be installed and initialised first.');
}

/**
 * set the content paths for non user editable content (default) and user
 * editable content (user), the user content path must be writeable by the web 
 * server (777 if you want to make it easy :)
 */
$cms_config = Kohana::$config->load('cms');
Cms_Content::$default_content_path = Arr::path($cms_config, 'content_paths.default');
Cms_Content::$user_content_path = Arr::path($cms_config, 'content_paths.user');

// set up the cache if specified in the config and the cache module is available
$cache_driver = Arr::get($cms_config, 'cache');
if($cache_driver !== NULL)
{	
	// (crudely) check that the cache module is installed
	if(!class_exists('Cache'))
	{
		// cache has been configured but the cache module is not available (https://github.com/kohana/cache)
		throw new Kohana_Exception('Caching has been configured for the CMS module but the Cache module is not installed/initialised.');
	}	
	
	// check for the existance of the cache driver
	if (isset(Cache::$instances[$cache_driver]))
	{
		// Gget the existing cache instance directly
		Cms_Content::$cache = Cache::$instances[$cache_driver];
	}
	else
	{
		// get a new cache driver instance
		Cms_Content::$cache = Cache::instance($cache_driver);
	}
}

// route for the set-locale action for switching regions
Route::set('cms_set_locale', 'set-locale/<locale>',
	array(
		'locale' => '[a-z]{2}-[a-z]{2}'
	))->defaults(array(
		'controller' => 'cms',
		'action' => 'set_locale'
));