<?php

class Rpa_Cms_Content_Text extends Cms_Content
{
	
	protected $_type = 'text';
	
	public function __toString()
	{
		echo $this->text;
	}
	
	public function get_formo_driver()
	{
		return 'text';
	}
}