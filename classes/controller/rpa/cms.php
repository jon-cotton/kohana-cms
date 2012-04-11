<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Controller_Static - controller actions for serving up static views, used when
 * a view doesn't require any 'backend' processing
 *
 * @author Jon Cotton <jon@rpacode.co.uk>
 * @copyright (c) 2011 RPA Code
 */
class Controller_Rpa_Cms extends Controller
{
	/**
	 * action_index - The default action, attempts to load the view at the path
	 * specified by the view parameter in the URL
	 *
	 * @throws HTTP_Exception_404
	 */
	public function action_index()
	{
		// get the parameters from the request
		$content_path = $this->request->param('content', NULL);
		
		// check if the view exists in the filesystem
		try
		{
			$contents = Cms_Content::find_all_by_uri($content_path);
		}
		catch(Cms_Exception_Notfound $e)
		{
			throw new HTTP_Exception_404;
		}
		
		// default view path is just the content path
		$view_path = $content_path;

		// see if the content contains a page object, if so try and get the view path from there
		$page = Arr::get($contents, 'page');
		if($page instanceOf Cms_Content_Page AND !empty($page->view))
		{
			$view_path = $page->view;
		}
		
		// check that the view exists
		if(Kohana::find_file('views', $view_path) === FALSE)
		{
			// if the path is a dir, assume an index view
			if(is_dir(APPPATH.'views'.DS.$view_path))
			{
				$view_path .= DS.'index';
			}
		}
		
		// set up the view
		$view = View::factory($view_path);
		
		// add each piece of content as a variable within the view
		foreach($contents as $key => &$value)
		{	
			$view->bind($key, $value);
		}
		
		// render
		echo $view->render();
	}
	
	public function action_set_locale()
	{
		$locale = $this->request->param('locale');
		
		$available_locales = Cms_Content::get_available_locales();
		if(!in_array('_'.$locale, $available_locales))
		{
			// unknown locale
			throw new HTTP_Exception_404;
		}		
		
		Cookie::set('locale', $locale);
		$this->request->redirect('/');
	}		
	
}