<?php

class Rpa_Cms_Exception_Notfound extends Cms_Exception
{
	
	public function __construct($locale, $uri)
	{
		parent::__construct('Content not found for :locale at path :uri', array(':locale' => $locale, ':uri' => $uri));
	}
	
}