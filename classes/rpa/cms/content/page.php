<?php

class Rpa_Cms_Content_Page extends Cms_Content
{
	
	protected $_type = 'page';
	
	/**
	 * @var string 
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
	
	public function get_view()
	{
		return $this->view;
	}

	public function set_view($view)
	{
		$this->view = $view;
	}

}