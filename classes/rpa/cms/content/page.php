<?php

class Rpa_Cms_Content_Page extends Cms_Content
{
	
	protected $_type = 'page';
	
	/**
	 * @var int 
	 */
	protected $view = NULL;
	
	public function __toString()
	{
		return $this->view;
	}
	
	public function get_formo_driver()
	{
		return NULL;
	}
		
}