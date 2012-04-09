<?php

class Rpa_Cms
{
	/**
	 * @var type 
	 */
	public static $default_content_path = NULL;
	
	/**
	 * @var type 
	 */
	public static $user_content_path = NULL;
	
	/**
	 * @var type 
	 */
	public static $locale = 'en-us';
	
	/**
	 * @var  array   Language paths that are used to find content
	 */
	protected static $_locale_paths = array();
	
	/**
	 *
	 * @param type $uri
	 * @param type $locale
	 * @return type
	 * 
	 * @throws Kohana_Exception
	 * @throws Cms_Exception_Unknownlocale
	 * @throws Cms_Exception_Notfound 
	 */
	public static function find_content_for_uri($uri, $locale = NULL)
	{	
		if(Cms::$default_content_path === NULL)
		{
			throw new Kohana_Exception('Content path is not set');
		}
		
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
		
		// get the path to the locale
		$locale_path = Arr::get(Cms::get_locale_paths(), '_'.$locale);
		if($locale_path === NULL)
		{
			// locale not known
			throw new Cms_Exception_Unknownlocale($locale);
		}	
		
		// get the cascading content paths that make up this content
		$default_locale_path = Cms::$default_content_path.DIRECTORY_SEPARATOR.$locale_path;
		$default_content_paths = Cms::find_content_paths($default_locale_path, $uri, Cms::$default_content_path);

		// if the user content path is defined, check for user managed content
		$user_content_paths = array();
		if(Cms::$user_content_path !== NULL)
		{
			$user_locale_path = Cms::$user_content_path.DIRECTORY_SEPARATOR.$locale_path;
			$user_content_paths = Cms::find_content_paths($user_locale_path, $uri, Cms::$user_content_path);
			
			$content_paths = Arr::merge($default_content_paths, $user_content_paths);
		}
		
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
	
	/**
	 *
	 * @return type 
	 */
	public static function get_locale_paths()
	{
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
				$locale_paths = self::find_locale_paths(Cms::$default_content_path);
				
				if(Kohana::$caching === TRUE)
				{
					// cache the locale paths
					Kohana::cache('rpa.cms.locale_paths', $locale_paths);
				}
				
				Cms::$_locale_paths = $locale_paths;
			}
		}
		
		return Cms::$_locale_paths;
	}
	
	/**
	 *
	 * @return type 
	 */
	public static function get_available_locales()
	{
		return array_keys(self::get_locale_paths());
	}		
	
	/**
	 *
	 * @param type $path
	 * @return type 
	 */
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
				// this entry is a locale so add it to the array
				$locale_paths[$entry] = $entry;

				// recursively check all locale dirs to see if they contain any locales themselves
				$sub_locale_paths = Cms::find_locale_paths($entry_path);
				
				// prefix all paths with the current entry so they have the correct path from the content root
				foreach($sub_locale_paths as $sub_locale => $sub_locale_path)
				{
					$sub_locale_paths[$sub_locale] = $entry.DIRECTORY_SEPARATOR.$sub_locale_path;
				}	
				
				$locale_paths = Arr::merge($locale_paths, $sub_locale_paths);
			}
		}
		
		return $locale_paths;
	}		
	
	/**
	 *
	 * @param type $path
	 * @param type $uri
	 * @return type 
	 */
	public static function find_content_paths($path, $uri, $root_path)
	{
		$content_paths = array();
		$content_file_path = $path.DIRECTORY_SEPARATOR.$uri;
		$locale = basename($path);
		
		if(file_exists($content_file_path.'.yml'))
		{
			$content_paths[$locale] = $content_file_path.'.yml';
		}
		elseif(is_dir($content_file_path) AND file_exists($content_file_path.DIRECTORY_SEPARATOR.'index.yml'))
		{	
			// path is a dir so serve up the index file
			$content_paths[$locale] = $content_file_path.DIRECTORY_SEPARATOR.'index.yml';
		}

		// check the parent locale
		$parent_path = dirname($path);
		if($parent_path != $root_path)
		{	
			// the parent isn't the root content dir so check for content in the parent
			$content_paths[] = Cms::find_content_paths($parent_path, $uri, $root_path);
		}
			
		return Arr::flatten($content_paths);
	}		
}
