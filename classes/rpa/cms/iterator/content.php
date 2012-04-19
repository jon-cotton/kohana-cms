<?php

class Rpa_Cms_Iterator_Content implements RecursiveIterator, Countable
{

	$_data = array();

	$_identifier_prefix = NULL;

	$_path = NULL;

	public function __construct(array $data, $path, $identifier_prefix = NULL)
	{
		$this->_data = $data;
		$this->$_path = $path;
		$this->_identifier_prefix = $identifier_prefix;
		$this->inflate();
	}

	protected function inflate()
	{
		foreach($this->_data as $identifier => &$item)
		{
			if(!is_array($item))
			{
			}

			$type = Arr::get($item, 'type');
			if(empty($type) OR $type == 'iterator' OR $type == 'text')
			{
				if(is_array($item))
				{	
					$item = new Cms_Iterator_Content($item, $this->$_path, $this->get_full_identifier($identifier));
				}
				else
				{
					// check to see if the content is a uri reference to other content
					$matches = array();
					if(preg_match(Cms_Content::CONTENT_URI_REGEX, $item, $matches))
					{
						$item = Cms_Content::find_by_uri($matches[1], $locale);
					}
				}	
			}
			else
			{
				$locale = Arr::get($item, 'locale', Cms_Content::$locale);

				$item = Arr::path(Cms_Content::$_loaded_content, $locale.'.'.$uri.'.'.$this->get_full_identifier($identifier);

				if(!$item instanceOf Cms_Content)
				{	
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
					Cms_Content::$_loaded_content[$locale][$uri][$identifier] = new $class_name($content);
					$item = Cms_Content::$_loaded_content[$locale][$uri][$identifier];

					$item->set_path($this->_path);
					$item->set_identifier($this->get_full_identifier($identifier));
				}
			}	
		}		
	}

	protected function get_full_identifier($identifier)
	{
		$full_identifier = $identifier;

		if($this->_identifier_prefix !== NULL)
		{
			$full_identifier = $this->_identifier_prefix.'.'.$full_identifier;
		}	

		return $full_identifier;
	}

}