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
			$content = Cms_Content::find_all_by_uri($content_path);
		}
		catch(Cms_Exception_Notfound $e)
		{
			throw new HTTP_Exception_404;
		}
		
		print_r($content);
		exit;
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