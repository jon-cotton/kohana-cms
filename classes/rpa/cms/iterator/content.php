<?php

class Rpa_Cms_Iterator_Content implements Iterator, Countable, ArrayAccess
{
	/**
	 * @var 	array 	$_data 	The array of content data
	 */
	protected $_data = array();

	/**
	 * @var 	string 	$_identifier 	The identifier of this collection of content (NULL == root iterator)
	 */
	protected $_identifier = NULL;

	/**
	 * @var 	string 	$_path 	The path to the content file that cotains this content collection
	 */
	protected $_path = NULL;

	/**
	 *
	 */
	protected $_position = 0;

	/**
	 * Intitalises the content iterator object and recursively inflates
	 * the content data array by cycling through each node and instantiating
	 * a content object of the correct type with the content data contained
	 * within that node
	 *
	 * @param 	array 	$data 		The content data that describes the content objects to place within this iterator
	 * @param 	string 	$path 		The path of the content contained within this iterator
	 * @param 	string 	$identifier The identifier (within the content file) of this iterator
	 * @return 	void
	 */
	public function __construct(array $data, $path, $identifier = NULL) 
	{
		$this->_data = $data;
		$this->_path = $path;
		$this->_identifier = $identifier;
		$this->_position = 0;
		$this->inflate();
	}

	/*
	 *
	 */
	protected function inflate()
	{
		foreach($this->_data as $identifier => &$item)
		{
			$type = Arr::get($item, 'type');
			if(empty($type))
			{
				throw new Cms_Exception('Type property not set for item :identifier at path :path', array(':identifier' => $this->get_full_identifier($identifier), ':path' => $this->_path));
			}	
			
			if($type == 'iterator')
			{	
				$content_object = new Cms_Iterator_Content($item, $this->_path, $this->get_full_identifier($identifier));
			}
			else
			{
				$locale = Arr::get($item, 'locale', Cms_Content::$locale);

				if($type == 'text')
				{
					$value = Arr::get($item, 'value');
					$item['text'] = Arr::get($item, 'text', $value);

					// check to see if the content is a uri reference to other content
					$matches = array();
					if(preg_match(Cms_Content::CONTENT_URI_REGEX, $item['text'], $matches))
					{
						$item = Cms_Content::find_by_uri($matches[1], $locale);
						continue;
					}
				}

				$content_object = Arr::path(Cms_Content::$_loaded_content, $locale.'.'.$this->_path.'.'.$this->get_full_identifier($identifier));

				if(!$content_object instanceOf Cms_Content)
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
					Cms_Content::$_loaded_content[$locale][$this->_path][$this->get_full_identifier($identifier)] = new $class_name($item);
					$content_object = Cms_Content::$_loaded_content[$locale][$this->_path][$this->get_full_identifier($identifier)];

					//$content_object = new $class_name($item);

					$content_object->set_path($this->_path);
					$content_object->set_identifier($this->get_full_identifier($identifier));	
				}
			}

			$item = $content_object;	
		}		
	}

	protected function get_full_identifier($identifier)
	{
		$full_identifier = $identifier;

		if($this->_identifier !== NULL)
		{
			$full_identifier = $this->_identifier.'.'.$full_identifier;
		}	

		return $full_identifier;
	}

	public function rewind()
	{
		$this->_position = 0;
	}

	public function current()
	{	
		return $this->_data[$this->key()];
	}

	public function key()
	{
		$keys = array_keys($this->_data);
		return $keys[$this->_position];
	}

	public function next()
	{
		$this->_position++;
	}

	public function valid()
	{
		$keys = array_keys($this->_data);
		return isset($keys[$this->_position]);
	}

	public function count()
	{
		return count($this->_data);
	}

	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->_data);
	}

	public function offsetGet($offset)
	{
		return $this->_data[$offset];
	}

	public function offsetSet($offset, $value)
	{
		$this->_data[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->_data[$offset]);
	}

}