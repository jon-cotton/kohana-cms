<?php

class Rpa_Cms_Content_Text extends Cms_Content
{
	/**
	 * The content type
	 * @var string 
	 */
	protected $_type = 'text';
	
	/**
	 * The text/value
	 * @var type 
	 */
	protected $text = NULL;
	
	/**
	 * @var int 
	 */
	protected $max_chars = 100;
	
	public function __toString()
	{
		return (string)$this->text;
	}
	
	public function get_formo_driver()
	{
		return 'text';
	}
	
	public function as_array()
	{
		return array('text' => $this->text);
	}
		
}