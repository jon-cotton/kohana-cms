<?php

class Rpa_Cms_Content_List extends Cms_Content implements IteratorAggregate
{
	
	/**
	 * The content type
	 * @var string 
	 */
	protected $_type = 'list';
	
	/**
	 * The items of the list
	 * @var array 
	 */
	protected $items = array();
	
	/**
	 * @var int 
	 */
	protected $max_chars = 100;
	
	public function __toString()
	{
		return implode(',', $this->items);
	}
	
	public function get_formo_driver()
	{
		return NULL;
	}
	
	public function as_array()
	{
		return $this->items;
	}		
	
	public function getIterator()
	{
		return new ArrayIterator($this->items);
	}	
	
	public function inflate(array $data)
	{
		parent::inflate($data);
		
		foreach($this->items as &$item)
		{
			$matches = array();
			if(preg_match(Cms_Content::CONTENT_URI_REGEX, $item, $matches))
			{
				$item = Cms_Content::find_by_uri($matches[1], $this->locale);
			}
		}	
	}		
}