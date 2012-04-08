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
	 * @static STATIC_VIEW_PATH - the directory within the application's views
	 * directory where the static views are located
	 */
	const STATIC_VIEW_PATH = 'static';
	
	const DEFAULT_PAGE_TITLE	= 'Welcome';
	const TITLE_PREFIX			= '';
	
	private $page_titles_map = array(
		//''				=> 'Home',
		//'path/to/page'	=> 'Page Title'
	);

	/**
	 * action_index - The default action, attempts to load the view at the path
	 * specified by the view parameter in the URL
	 *
	 * @throws HTTP_Exception_404
	 */
	public function action_index()
	{
		// get the parameters from the request
		$view_path = $this->request->param('view', NULL);

		// check if the view exists in the filesystem
		try
		{
			$content = Cms::find_content_for_uri($view_path);
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
		
		Cookie::set('locale', $locale);
		
		$this->request->redirect('/');
	}		
	
	private function get_page_title()
	{
		$title = Arr::get($this->page_titles_map, $this->request->uri(), FALSE);
		
		if($title)
		{
			$title = self::TITLE_PREFIX.$title;
		}
		else
		{
			$title = self::DEFAULT_PAGE_TITLE;
		}
		
		return $title;
	}	
}