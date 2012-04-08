<?php

class Rpa_Cms_Exception_Unknownlocale extends Kohana_Exception
{
	
	public function __construct($locale)
	{
		parent::__construct('Locale :locale is not known', array(':locale' => $locale));
	}
	
}