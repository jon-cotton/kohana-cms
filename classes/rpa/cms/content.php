<?php

abstract class Rpa_Cms_Content
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

//==============================================================================	
	
	/**
	 * @var	string
	 */
	protected $_type = NULL;
	
	/**
	 * @var	string 
	 */
	protected $_uri = NULL;
	
	/**
	 * @var	string 
	 */
	protected $_locale = NULL;
	
	/**
	 * @var	array 
	 */
	protected $_data = array();
	
	/**
	 * @var boolean 
	 */
	protected $_editable = FALSE;

//==============================================================================	
	
	/**
	 *
	 * @param type $uri
	 * @param type $locale
	 * @return array
	 * @throws Cms_Exception 
	 */
	public static function find_all_by_uri($uri, $locale = NULL)
	{
		$content_objects = array();
		
		if($locale === NULL)
		{
			// no language has been supplied so get the current locale
			$locale = Cms_Content::$locale;
		}
		
		// get the content data from the uri as an array
		$content_data = Cms_Content::find_content_data_by_uri($uri, $locale);
		
		// instantiate the content objects from the content data
		foreach($content_data as $identifier => $content)
		{
			// wrap content in an array if it isn't an array already
			if(!is_array($content))
			{
				$content = array('text' => $content);
			}
			
			$default_type = Kohana::$config->load('cms.defaults.type');
			$type = Arr::get($content, 'type', $default_type);
			if($type === NULL)
			{
				// each piece of content data must have a type property so that we know how to instantiate it
				throw new Cms_Exception(
					'content at :uri::identifier does not have a type',
					array(':uri' => $uri, ':identifier' => $identifier)
				);
			}	
			
			// attempt to find the correct class based on the type property of the content
			$class_name = 'Cms_Content_'.str_replace(' ', '_', ucwords(str_replace('_', ' ', $type)));
			if(!class_exists($class_name))
			{
				// the class derived from the type property does not exist
				throw new Cms_Exception(
					'attempted to instantiate content of type :type but class :class_name does not exist',
					array(':type' => $type, ':class_name' => $class_name)
				);
			}	
			
			// instatiate a new content object
			$content_object = new $class_name($content);
			$content_object->set_uri($uri.':'.$identifier);
			$content_object->set_locale($locale);
			
			$content_objects[$identifier] = $content_object;
		}
		
		return $content_objects;
	}
	
	/**
	 *
	 * @param type $uri
	 * @return Cms_Content 
	 */
	public static function find_by_uri($uri)
	{
		// split the uri into path and identifier
		$uri_parts = explode(':', $uri);
		$path = Arr::get($uri_parts, 0);
		$identifier = Arr::get($uri_parts, 1);
		
		$all_content = Cms_Content::find_all_by_uri($path);
		
		if($identifier !== NULL)
		{
			// return the piece of content specified by the identifier part of the uri
			$content = Arr::get($all_content, $identifier);
		}
		else
		{
			// identifier isn't specified so just return the first piece of content 
			$content = reset($all_content);
		}
		
		return $content;
	}
	
	/**
	 *
	 * @param type $uri
	 * @param type $locale
	 * @param type $use_cache
	 * @return array
	 * @throws Cms_Exception
	 * @throws Cms_Exception_Unknownlocale
	 * @throws Cms_Exception_Notfound 
	 */
	public static function find_content_data_by_uri($uri, $locale, $use_cache = TRUE)
	{
		$content_data = array();
		
		if(Cms_Content::$default_content_path === NULL)
		{
			throw new Cms_Exception('Content path is not set');
		}

		// set the key to be used to add/retrieve this content to/from the cache
		$content_cache_key = 'rpa.cms.'.$locale.$uri;
			
		if(Kohana::$caching === TRUE AND $use_cache === TRUE AND Kohana::cache($content_cache_key) !== NULL)
		{
			// return the content from the cache
			$content_data = Kohana::cache($content_cache_key);
		}
		else
		{
			// no cache available
			// get the path to the locale
			$locale_path = Arr::get(Cms_Content::get_locale_paths(), '_'.$locale);
			if($locale_path === NULL)
			{
				// locale not known
				throw new Cms_Exception_Unknownlocale($locale);
			}	

			// get the cascading content paths that make up this content
			$default_locale_path = Cms_Content::$default_content_path.DIRECTORY_SEPARATOR.$locale_path;
			$content_paths = Cms_Content::find_content_paths($default_locale_path, $uri, Cms_Content::$default_content_path);

			// if the user content path is defined, check for user managed content
			$user_content_paths = array();
			if(Cms_Content::$user_content_path !== NULL)
			{
				$user_locale_path = Cms_Content::$user_content_path.DIRECTORY_SEPARATOR.$locale_path;
				$user_content_paths = Cms_Content::find_content_paths($user_locale_path, $uri, Cms_Content::$user_content_path);

				$content_paths = Arr::merge($content_paths, $user_content_paths);
			}

			if(count($content_paths) < 1)
			{
				// content not found
				throw new Cms_Exception_Notfound($locale, $uri);
			}

			// now we have the content paths, load the content from the yml files
			$yaml = Yaml::instance();
			foreach($content_paths as $content_path)
			{	
				$content_data = Arr::merge($yaml->parse_file($content_path), $content_data);
			}

			if(Kohana::$caching === TRUE AND $use_cache === TRUE)
			{
				// cache the content
				Kohana::cache($content_cache_key, $content_data);
			}
		}
		
		return $content_data;
	}		
	
	/**
	 *
	 * @return type 
	 */
	private static function get_locale_paths()
	{
		if(empty(Cms_Content::$_locale_paths))
		{
			if(Kohana::$caching === TRUE AND Kohana::cache('rpa.cms.locale_paths') !== NULL)
			{
				// get the locale paths from cache
				Cms_Content::$_locale_paths = Kohana::cache('rpa.cms.locale_paths');
			}
			else
			{
				// build the locale paths array from the filesystem (expensive)
				$locale_paths = self::find_locale_paths(Cms_Content::$default_content_path);
				
				if(Kohana::$caching === TRUE)
				{
					// cache the locale paths
					Kohana::cache('rpa.cms.locale_paths', $locale_paths);
				}
				
				Cms_Content::$_locale_paths = $locale_paths;
			}
		}
		
		return Cms_Content::$_locale_paths;
	}
	
	/**
	 *
	 * @return type 
	 */
	public static function get_available_locales()
	{
		return array_keys(Cms_Content::get_locale_paths());
	}		
	
	/**
	 *
	 * @param type $path
	 * @return type 
	 */
	private static function find_locale_paths($path)
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
				$sub_locale_paths = Cms_Content::find_locale_paths($entry_path);
				
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
	private static function find_content_paths($path, $uri, $root_path)
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
			$content_paths[] = Cms_Content::find_content_paths($parent_path, $uri, $root_path);
		}
			
		return Arr::flatten($content_paths);
	}

//==============================================================================	
	
	/**
	 * @param	string $uri
	 * @param	string $locale 
	 */
	public function __construct(array $data = NULL)
	{
		if($this->_type === NULL)
		{
			// the type property must always be set by child classes
			throw new Cms_Exception('Type property must be set');
		}
		
		if($data !== NULL)
		{
			$this->inflate($data);
		}
	}

	/**
	 * @param	array $data 
	 */
	public function inflate(array $data)
	{
		// load the defaults from the config and merge them with the incoming data
		$defaults = Kohana::$config->load('cms.defaults');
		$data = Arr::merge($defaults, $data);
		
		// check the type of the incoming data
		$type = Arr::get($data, 'type');
		if($this->_type != $type)
		{
			// types don't match
			throw new Cms_Exception('Attempted to load content of type :type when :selftype was expected',
				array(':type' => $type, ':selftype' => $this->_type)
			);
		}
		unset($data['type']);
		
		// iterate through the data elements and add the data to the relevant property
		foreach($data as $property => $value)
		{
			switch($property)
			{
				// editable flag
				case 'editable':
					$this->_editable = (bool)$value;
					break;
				
				// catch all, anything not specified above is added to the data
				default:
					$this->{$property} = $value;
					break;
			}
		}	
	}
	
	/**
	 *
	 * @param type $uri
	 * @param type $locale
	 * @throws Cms_Exception 
	 */
	public function save($uri = NULL, $locale = NULL)
	{	
		// make sure a user content path is defined
		if(Cms_Content::$user_content_path === NULL)
		{
			// can't save without a user content path
			throw new Cms_Exception('Cms_Content::$user_content_path must be defined in order to save content');
		}	
		
		if($uri === NULL)
		{
			// no uri supplied so see if one already exists for this object
			$uri = $this->get_uri();
			if($uri === NULL)
			{
				// still no uri, can't save without a uri
				throw new Cms_Exception('Attempted to save content object without specifying a URI');
			}
		}
		
		if($locale === NULL)
		{
			// no locale supplied so see if one already exists for this object
			$locale = $this->get_locale();
			if($locale === NULL)
			{
				// still no locale, can't save without a locale
				throw new Cms_Exception('Attempted to save content object without specifying a locale');
			}
		}	
		
		// split the uri into path and identifier
		$uri_parts = explode(':', $uri);
		$path = Arr::get($uri_parts, 0);
		$identifier = Arr::get($uri_parts, 1);
		
		if($identifier === NULL)
		{
			// malformed uri, can't save without an identifier
			throw new Cms_Exception('Attempted to save content object without specifying an identifier');
		}
		
		// get the content data as an array
		$content_data = Cms_Content::find_content_data_by_uri($path, NULL, FALSE);
		
		// add/overwrite the loaded data with the data from this object
		$content_data[$identifier] = $this->as_array();

		// get the correct locale path
		$locale_path = Arr::get(Cms_Content::get_locale_paths(), '_'.$locale);
		if($locale_path === NULL)
		{
			// locale not known
			throw new Cms_Exception_Unknownlocale($locale);
		}
		
		// build the full path to the content file
		$content_path = Cms_Content::$user_content_path.DIRECTORY_SEPARATOR.$locale_path.DIRECTORY_SEPARATOR.$path.'.yml';
		
		// persist to the correct yml file
		$yaml = Yaml::instance();
		$yaml->dump_file($content_path, $content_data);
		
		if(Kohana::$caching === TRUE)
		{
			// finally, overwrite any relevent cache files
			Kohana::cache('rpa.cms.'.$locale.$path, $content_data);
		}
	}

	/**
	 * @return	type 
	 */
	public function as_array()
	{
		return $this->_data;
	}
	
	/**
	 * @param	string	$key
	 * @return	mixed 
	 */
	public function __get($key)
	{
		return Arr::get($this->_data, $key);
	}
	
	/**
	 * @param	string	$key
	 * @param	mixed	$value 
	 */
	public function __set($key, $value)
	{
		$this->_data[$key] = $value;
	}
	
	/**
	 * @return boolean 
	 */
	public function is_editable()
	{
		return $this->_editable;
	}

	/**
	 *
	 * @return string 
	 */
	public function get_uri()
	{
		return $this->_uri;
	}

	/**
	 *
	 * @param string $uri 
	 */
	public function set_uri($uri)
	{
		$this->_uri = $uri;
	}

	/**
	 *
	 * @return string 
	 */
	public function get_locale()
	{
		return $this->_locale;
	}

	/**
	 *
	 * @param string $locale 
	 */
	public function set_locale($locale)
	{
		$this->_locale = $locale;
	}

	/**
	 * the toString must always be implemented so that you can directly
	 * print/echo out the value of a content object
	 * 
	 * @return string
	 */
	abstract public function __toString();
	
	/**
	 * @return string 
	 */
	abstract public function get_formo_driver();
	
}