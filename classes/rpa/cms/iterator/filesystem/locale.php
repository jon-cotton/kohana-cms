<?php

class Rpa_Cms_Iterator_Filesystem_Locale extends FilterIterator
{
	public function __construct($path)
	{
		$dir_it = new RecursiveDirectoryIterator($path);
		$rescursive_it = new RecursiveIteratorIterator($dir_it, RecursiveIteratorIterator::SELF_FIRST);
		
		parent::__construct($rescursive_it);
	}		

	public function accept()
	{
		$locale_regex = '/^_[a-z]{2}-[a-z]{2}$/';
		
		// is the current item a directory and does the name of it match the locale regex pattern
		return (parent::current()->isDir() AND preg_match($locale_regex, parent::current()->getFilename()));
	}		
	
}