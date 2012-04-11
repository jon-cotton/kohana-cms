<?php

class Rpa_Cms_Content_Image extends Cms_Content
{
	protected $_type = 'image';
	
	public function __toString()
	{
		return $this->url;
	}
	
	public function get_formo_driver()
	{
		return 'file';
	}
}