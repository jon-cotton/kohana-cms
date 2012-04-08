<?php

class Rpa_Cms
{
	public static $content_path = 'content';
	
	public static $locale = 'en-us';
	
	/**
	 * @var  array   Language paths that are used to find content
	 */
	protected static $_locale_paths = array();
	
	public static function find_content_for_uri($uri, $locale = NULL)
	{	
		// TODO: move this to init
		Cms::$content_path = APPPATH.Cms::$content_path;
		
		if($locale === NULL)
		{
			// no language has been supplied so get the current locale
			$locale = Cms::$locale;
		}
		
		// set the key to be used to add/retrieve this content to/from the cache
		$content_cache_key = 'rpa.cms.'.$locale.$uri;
			
		if(Kohana::$caching === TRUE AND Kohana::cache($content_cache_key) !== NULL)
		{
			// return the content from the cache
			return Kohana::cache($content_cache_key);
		}
		
		// no cache available so check that we have the locale path structure
		if(empty(Cms::$_locale_paths))
		{
			if(Kohana::$caching === TRUE AND Kohana::cache('rpa.cms.locale_paths') !== NULL)
			{
				// get the locale paths from cache
				Cms::$_locale_paths = Kohana::cache('rpa.cms.locale_paths');
			}
			else
			{
				// build the locale paths array from the filesystem (expensive)
				$locale_paths = self::find_locale_paths(Cms::$content_path);
				
				if(Kohana::$caching === TRUE)
				{
					// cache the locale paths
					Kohana::cache('rpa.cms.locale_paths', $locale_paths);
				}
				
				Cms::$_locale_paths = $locale_paths;
			}
		}
		
		$locale_path = Arr::get(Cms::$_locale_paths, '_'.$locale);
		if($locale_path === NULL)
		{
			// locale not known
			throw new Cms_Exception_Unknownlocale($locale);
		}	
		
		$content_paths = Cms::find_content_paths($locale_path, $uri);
		if(count($content_paths) < 1)
		{
			// content not found
			throw new Cms_Exception_Notfound($locale, $uri);
		}
		
		// now we have the content paths, load the content from the yml files
		$yaml = Yaml::instance();
		$content = array();
		foreach($content_paths as $content_path)
		{	
			$content = Arr::merge($yaml->parse_file($content_path), $content);
		}
		
		if(Kohana::$caching === TRUE)
		{
			// cache the content
			Kohana::cache($content_cache_key, $content);
		}
		
		return $content;
	}		
	
	public static function find_locale_paths($path)
	{
		$locale_regex = '/^_[a-z]{2}-[a-z]{2}$/';
		$handle = opendir($path);

		$locale_paths = array();

		while(($entry = readdir($handle)) !== FALSE)
		{
			$entry_path = $path.DIRECTORY_SEPARATOR.$entry;
			if(is_dir($entry_path) AND preg_match($locale_regex, $entry))
			{
				$locale_paths[$entry] = $entry_path;
				
				// recursively check all locale dirs to see if they contain any locales themselves
				$sub_locale_paths = Cms::find_locale_paths($entry_path);
				$locale_paths = Arr::merge($locale_paths, $sub_locale_paths);
			}
		}

		return $locale_paths;
	}		
	
	public static function find_content_paths($path, $uri)
	{
		$content_paths = array();
		$content_file_path = $path.DIRECTORY_SEPARATOR.$uri;
		$locale = basename($path);
		
		if(file_exists($content_file_path.'.yml'))
		{
			$content_paths[$locale] = $content_file_path.'.yml';
		}
		elseif(is_dir($content_file_path) AND file_exists($content_file_path.DIRECTORY_SEPERATOR.'index.yml'))
		{
			// path is a dir so serve up the index file
			$content_paths[$locale] = $content_file_path.DIRECTORY_SEPERATOR.'index.yml';
		}	
	
		// check the parent locale
		$parent_path = dirname($path);
		if($parent_path != Cms::$content_path)
		{	
			// the parent isn't the root content dir so check for content in the parent
			$content_paths[] = Cms::find_content_paths($parent_path, $uri);
		}

		return Arr::flatten($content_paths);
	}		
}
