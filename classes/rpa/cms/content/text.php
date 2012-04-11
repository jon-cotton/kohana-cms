<?php

class Rpa_Cms_Content_Text extends Cms_Content
{
	
	protected $_type = 'text';
	
	/**
	 * @var int 
	 */
	protected $max_chars = 100;
	
	public function __toString()
	{
		return $this->text;
	}
	
	public function get_formo_driver()
	{
		return 'text';
	}
		
}