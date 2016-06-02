<?php
/**
 * Fabrik Component HTML Helper
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

jimport('joomla.filesystem.file');

if (!defined('COM_FABRIK_FRONTEND'))
{
	throw new RuntimeException(FText::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'), 400);
}

/**
 * Fabrik Component HTML Helper
 *
 * @static
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @since       1.5
 */
class FabrikHelperHTML
{
	/**
	 * Is the Fabrik JavaScript framework loaded
	 *
	 * @var  bool
	 */
	protected static $framework = null;

	/**
	 * Is the MCL JavaScript library loaded
	 *
	 * @var  bool
	 */
	protected static $mcl = null;

	/**
	 * Array of loaded modal window states
	 *
	 * @var array
	 */
	protected static $modals = array();

	/**
	 * Array of loaded tip states
	 *
	 * @var  array
	 */
	protected static $tips = array();

	/**
	 * Previously loaded js scripts
	 *
	 * @var  array
	 */
	protected static $scripts = array();

	/**
	 * Array of rendered jLayouts for use in JS code.
	 *
	 * @var array
	 */
	protected static $jLayoutsJs = array();

	/**
	 * CSS files loaded via AJAX
	 *
	 * @var  array
	 */
	protected static $ajaxCssFiles = array();

	/**
	 * Has the debug JavaScript been loaded
	 *
	 * @var  bool
	 */
	protected static $debug = null;

	/**
	 * Has the Facebook API JavaScript file been loaded
	 *
	 * @var  bool
	 */
	protected static $facebookgraphapi = null;

	/**
	 * Has the at who js file been loaded
	 *
	 * @var array
	 */
	protected static $atWho = array();
	/**
	 * Folders to search for media
	 *
	 * @var  array
	 */
	protected static $helperpaths = array();

	/**
	 * Load the modal JavaScript files once
	 *
	 * @var  bool
	 */
	protected static $modal = null;

	/**
	 * Form email link URL
	 *
	 * @var string
	 */
	protected static $emailURL = null;

	/**
	 * Form print link URL
	 *
	 * @var  string
	 */
	protected static $printURL = null;

	protected static $requireJS = array();

	/**
	 * Array containing information for loaded files
	 *
	 * @var    array
	 * @since  2.5
	 */
	protected static $loaded = array();

	/**
	 * Array of browser request headers.  Starts as null.
	 *
	 * @var array
	 */
	protected static $requestHeaders = null;

	/**
	 * Usually gets set to COM_FABRIK_LIVESITE, but can be overridden by a global option
	 *
	 * @var string
	 */
	protected static $baseJSAssetURI = null;

	/**
	 * Load up window code - should be run in ajax loaded pages as well (10/07/2012 but not json views)
	 * might be an issue in that we may be re-observing some links when loading in - need to check
	 *
	 * @param   string $selector Element select to auto create windows for  - was default = a.modal
	 * @param   array  $params   Window parameters
	 *
	 * @deprecated use windows() instead
	 *
	 * @return  void
	 */
	public static function mocha($selector = '', $params = array())
	{
		self::windows($selector, $params);
	}

	/**
	 * Build a data-toggling dropdown
	 *
	 * @param   array  $lis   Array of links to create dropdown from
	 * @param   string $align Should the drop down be left or right aligned - If right then the dropdown content's end
	 *                        is right aligned to the button
	 *
	 * @return  string
	 */
	public static function bootStrapDropDown($lis, $align = 'left')
	{
		$class = 'btn-group fabrik_action';

		if ($align == 'right')
		{
			$class .= ' pull-right';
		}

		return '<div class="' . $class . '"><a class="dropdown-toggle btn btn-mini" data-toggle="dropdown" href="#">
				<span class="caret"></span>
				</a>
				<ul class="dropdown-menu"><li>' . implode('</li>' . "\n" . '<li>', $lis) . '</li></ul></div>';
	}

	/**
	 * Wrap buttons in bootstrap btn-group div
	 *
	 * @param   array $items Items
	 *
	 * @return string
	 */
	public static function bootStrapButtonGroup($items)
	{
		return '<div class="btn-group">' . implode(' ', $items) . '</div>';
	}

	/**
	 * Build an array of the request headers by hand.  Replacement for using
	 * apache_request_headers(), which only works in certain configurations.
	 * This solution gets them from the $_SERVER array, and re-munges them back
	 * from HTTP_FOO_BAR format to Foo-Bar format.  Stolen from:
	 * http://stackoverflow.com/questions/541430/how-do-i-read-any-request-header-in-php
	 *
	 * @return   array  request headers assoc
	 */
	public static function parseRequestHeaders()
	{
		if (isset(self::$requestHeaders))
		{
			return self::$requestHeaders;
		}

		self::$requestHeaders = array();

		foreach ($_SERVER as $key => $value)
		{
			if (substr($key, 0, 5) <> 'HTTP_')
			{
				continue;
			}

			$header                        = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
			self::$requestHeaders[$header] = $value;
		}

		return self::$requestHeaders;
	}

	/**
	 * Load up window code - should be run in ajax loaded pages as well (10/07/2012 but not json views)
	 * might be an issue in that we may be re-observing some links when loading in - need to check
	 *
	 * @param   string $selector Element select to auto create windows for  - was default = a.modal
	 * @param   array  $params   Window parameters
	 *
	 * @return  void
	 */
	public static function windows($selector = '', $params = array())
	{
		$app    = JFactory::getApplication();
		$input  = $app->input;
		$script = '';

		// Don't include in an Request.JSON call - for auto-fill form plugin
		$headers = self::parseRequestHeaders();

		if (FArrayHelper::getValue($headers, 'X-Request') === 'JSON')
		{
			return;
		}

		if ($input->get('format') == 'json')
		{
			return;
		}

		$sig = md5(serialize(array($selector, $params)));

		if (isset(self::$modals[$sig]) && (self::$modals[$sig]))
		{
			return;
		}

		$script .= "window.addEvent('fabrik.loaded', function() {";

		if ($selector == '')
		{
			return;
		}

		// Setup options object
		$opt['ajaxOptions'] = (isset($params['ajaxOptions']) && (is_array($params['ajaxOptions']))) ? $params['ajaxOptions'] : null;
		$opt['size']        = (isset($params['size']) && (is_array($params['size']))) ? $params['size'] : null;
		$opt['onOpen']      = (isset($params['onOpen'])) ? $params['onOpen'] : null;
		$opt['onClose']     = (isset($params['onClose'])) ? $params['onClose'] : null;
		$opt['onUpdate']    = (isset($params['onUpdate'])) ? $params['onUpdate'] : null;
		$opt['onResize']    = (isset($params['onResize'])) ? $params['onResize'] : null;
		$opt['onMove']      = (isset($params['onMove'])) ? $params['onMove'] : null;
		$opt['onShow']      = (isset($params['onShow'])) ? $params['onShow'] : null;
		$opt['onHide']      = (isset($params['onHide'])) ? $params['onHide'] : null;

		// Attach modal behavior to document
		// Set default values which can be overwritten in <a>'s rel attribute

		$opts              = new stdClass;
		$opts->id          = 'fabwin';
		$opts->title       = FText::_('COM_FABRIK_ADVANCED_SEARCH');
		$opts->loadMethod  = 'xhr';
		$opts->minimizable = false;
		$opts->collapsible = true;
		$opts->width       = 500;
		$opts->height      = 150;
		$opts              = json_encode($opts);

		$script .= <<<EOD

  $$('$selector').each(function(el, i) {
    el.addEvent('click', function(e) {
    	var opts = $opts;
    	e.stop();
      opts2 = JSON.decode(el.get('rel'));
      opts = Object.merge(opts, opts2 || {});
      opts.contentURL = el.href;
      if (opts.id === 'fabwin') {
      	opts.id += i;
      }
      Fabrik.getWindow(opts);
    });
  });
});
EOD;

		self::addScriptDeclaration($script);
		self::$modals[$sig] = true;

		return;
	}

	/**
	 * Show form to allow users to email form to a friend.
	 * Echo's out form HTML.
	 *
	 * @param   object $formModel Form model
	 * @param   string $template  Template
	 *
	 * @return  void
	 */
	public static function emailForm($formModel, $template = '')
	{
		$app                   = JFactory::getApplication();
		$input                 = $app->input;
		$layout                = self::getLayout('form.fabrik-email-form');
		$displayData           = new stdClass;
		$displayData->j3       = FabrikWorker::j3();
		$displayData->package  = $app->getUserState('com_fabrik.package', 'fabrik');
		$displayData->referrer = $input->get('referrer', '', 'string');
		$document              = JFactory::getDocument();
		$form                  = $formModel->getForm();
		$document->setTitle($form->label);
		$document->addStyleSheet('templates/' . $template . '/css/template_css.css');
		echo $layout->render($displayData);
	}

	/**
	 * Once email has been sent to a friend show this message
	 *
	 * @return  void
	 */
	public static function emailSent()
	{
		$config   = JFactory::getConfig();
		$document = JFactory::getDocument();
		$j3       = FabrikWorker::j3();
		$document->setTitle($config->get('sitename'));

		if (!$j3)
		{
			?>
			<a href='javascript:window.close();'> <span class="small"><?php echo FText::_('COM_FABRIK_CLOSE_WINDOW'); ?>
</span>
			</a>
			<?php
		}
	}

	/**
	 * Writes a print icon
	 *
	 * @param   object $formModel form model
	 * @param   object $params    parameters
	 *
	 * @return  string    print html icon/link
	 */
	public static function printIcon($formModel, $params)
	{
		$layout      = self::getLayout('form.fabrik-print-icon');
		$displayData = new stdClass;

		$displayData->status = "status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=400,height=350,directories=no,location=no";
		$displayData->link   = self::printURL($formModel);
		$displayData->popup  = $params->get('popup', 1);

		if ($params->get('icons', true))
		{
			$displayData->image = self::image('print.png');
		}
		else
		{
			$displayData->image = '&nbsp;' . FText::_('COM_FABRIK_PRINT');
		}

		return $layout->render($displayData);
	}

	/**
	 * Create print URL
	 *
	 * @param   object $formModel form model
	 *
	 * @since   3.0.6
	 *
	 * @return  string
	 */
	public static function printURL($formModel)
	{
		$app     = JFactory::getApplication();
		$input   = $app->input;
		$form    = $formModel->getForm();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$table   = $formModel->getTable();

		$url = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $package . '&view=details&tmpl=component&formid=' . $form->id . '&listid=' . $table->id
			. '&rowid=' . $formModel->getRowId() . '&iframe=1&print=1';

		$url .= '&Itemid=' . FabrikWorker::itemId();

		/* $$$ hugh - @TODO - FIXME - if they were using rowid=-1, we don't need this, as rowid has already been transmogrified
		 * to the correct (PK based) rowid.  but how to tell if original rowid was -1???
		*/
		if ($input->get('usekey') !== null)
		{
			$url .= '&usekey=' . $input->get('usekey');
		}

		$url = JRoute::_($url);

		// $$$ rob for some reason JRoute wasn't doing this ???
		$url            = str_replace('&', '&amp;', $url);
		self::$printURL = $url;

		return self::$printURL;
	}

	/**
	 * Writes Email icon
	 *
	 * @param   object $formModel Form model
	 * @param   object $params    Parameters
	 *
	 * @return  string    Email icon/link html
	 */
	public static function emailIcon($formModel, $params)
	{
		$layout              = self::getLayout('form.fabrik-email-icon');
		$displayData         = new stdClass;
		$displayData->popup  = $params->get('popup', 1);
		$displayData->status = "status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=400,height=250,directories=no,location=no";
		$displayData->link   = self::emailURL($formModel);
		$displayData->icons  = $params->get('icons', true);

		return $layout->render($displayData);
	}

	/**
	 * Create URL for form email button
	 *
	 * @param   object $formModel form model
	 *
	 * @since 3.0.6
	 *
	 * @return  string
	 */
	public static function emailURL($formModel)
	{
		$app     = JFactory::getApplication();
		$input   = $app->input;
		$package = $app->getUserState('com_fabrik.package', 'fabrik');

		if ($app->isAdmin())
		{
			$url = 'index.php?option=com_fabrik&task=emailform.display&tmpl=component&formid=' . $formModel->get('id') . '&rowid='
				. $formModel->getRowId();
		}
		else
		{
			$url = 'index.php?option=com_' . $package . '&view=emailform&tmpl=component&formid=' . $formModel->get('id') . '&rowid=' . $formModel->getRowId();
		}

		if ($input->get('usekey') !== null)
		{
			$url .= '&usekey=' . $input->get('usekey');
		}

		$url .= '&referrer=' . urlencode(JURI::getInstance()->toString());
		self::$emailURL = JRoute::_($url);

		return self::$emailURL;
	}

	/**
	 * Get a list of condition options - used in advanced search
	 *
	 * @param   string $listId list ref
	 * @param   string $sel    selected value
	 *
	 * @return  string    html select list
	 */
	public static function conditionList($listId, $sel = '')
	{
		$conditions   = array();
		$conditions[] = JHTML::_('select.option', 'AND', FText::_('COM_FABRIK_AND'));
		$conditions[] = JHTML::_('select.option', 'OR', FText::_('COM_FABRIK_OR'));
		$name         = 'fabrik___filter[list_' . $listId . '][join][]';

		return JHTML::_('select.genericlist', $conditions, $name, 'class="inputbox input-mini" size="1" ', 'value', 'text', $sel);
	}

	/**
	 * Get a select list of fabrik lists
	 *
	 * @param   string $sel selected value
	 *
	 * @return  mixed    html select list or error
	 */
	public static function tableList($sel = '')
	{
		$db    = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('id, label')->from('#__{package}_lists')->where('published = 1')->order('label');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		return JHTML::_('select.genericlist', $rows, 'fabrik__swaptable', 'class="inputbox" size="1" ', 'id', 'label', $sel);
	}

	/**
	 * Load the css and js files once only (using calendar-eightsix)
	 *
	 * @deprecated - behavior.calendar is loaded in framework();
	 *
	 * @return  void
	 */
	public static function loadCalendar()
	{
	}

	/**
	 * Fabrik script to load in a style sheet
	 * takes into account if you are viewing the page in raw format
	 * if so sends js code back to web page to inject css file into document head
	 * If not raw format then apply standard J stylesheet
	 *
	 * @param   string $file    stylesheet URL
	 * @param   array  $attribs not used
	 *
	 * @return  null
	 */
	public static function stylesheet($file, $attribs = array())
	{
		// $$$ hugh - moved this to top of function, as we now apply livesite in either usage cases below.
		if (!strstr($file, COM_FABRIK_LIVESITE))
		{
			$file = COM_FABRIK_LIVESITE . $file;
		}

		if (self::cssAsAsset())
		{
			// Send an inline script back which will inject the css file into the doc head
			// Note your ajax call must have 'evalScripts':true set in its properties
			if (!in_array($file, self::$ajaxCssFiles))
			{
				if (!strstr($file, 'fabrik.css'))
				{
					$opts = new stdClass;
					echo "<script type=\"text/javascript\">
				var v = new Asset.css('" . $file . "', " . json_encode($opts) . ");
    		</script>\n";
					self::$ajaxCssFiles[] = $file;
				}
			}
		}
		else
		{
			$document = JFactory::getDocument();
			/* $$$ rob 27/04/2011 changed from JHTML::styleSheet as that doesn't work loading
			 * php style sheets with querystrings in them
			*/
			$document->addStylesheet($file);
		}
	}

	/**
	 * Will the CSS be loaded as Asset.css()
	 *
	 * @since   3.0.6
	 *
	 * @return  bool
	 */
	public static function cssAsAsset()
	{
		$app    = JFactory::getApplication();
		$input  = $app->input;
		$tpl    = $input->get('tmpl');
		$iFrame = $input->get('iframe');
		$print  = $input->get('print');
		$format = $input->get('format');

		return $input->get('format') == 'raw' || ($tpl == 'component' && $iFrame != 1) && $print != 1 && $format !== 'pdf';
	}

	/**
	 * Check for a custom css file and include it if it exists
	 *
	 * @param   string $path NOT including JPATH_SITE (so relative too root dir) may include querystring
	 *
	 * @return    bool    if loaded or not
	 */
	public static function stylesheetFromPath($path)
	{
		if (strstr($path, '?'))
		{
			$file = explode('?', $path);
			$file = $file[0];
		}
		else
		{
			$file = $path;
		}

		if (JFile::exists(JPATH_SITE . '/' . $file))
		{
			self::stylesheet($path);

			return true;
		}

		return false;
	}

	/**
	 * Generates an HTML radio list
	 *
	 * @param   array  &$arr            An array of objects
	 * @param   string $tag_name        The value of the HTML name attribute
	 * @param   string $tag_attribs     Additional HTML attributes for the <select> tag
	 * @param   mixed  $selected        The key that is selected
	 * @param   string $key             The name of the object variable for the option value
	 * @param   string $text            The name of the object variable for the option text
	 * @param   int    $options_per_row number of options to show per row @since 2.0.5
	 *
	 * @return  string    HTML for the select list
	 */
	public static function radioList(&$arr, $tag_name, $tag_attribs, $selected = null, $key = 'value', $text = 'text', $options_per_row = 0)
	{
		return self::aList('radio', $arr, $tag_name, $tag_attribs, $selected, $key, $text, $options_per_row);
	}

	/**
	 * Generates an HTML radio OR checkbox list
	 *
	 * @param   string $type            Radio/checkbox
	 * @param   array  &$arr            An array of objects
	 * @param   string $tag_name        The value of the HTML name attribute
	 * @param   string $tag_attribs     Additional HTML attributes for the <select> tag
	 * @param   mixed  $selected        The key that is selected
	 * @param   string $key             The name of the object variable for the option value
	 * @param   string $text            The name of the object variable for the option text
	 * @param   int    $options_per_row Number of options to show per row @since 2.0.5
	 * @param   bool   $editable        Editable or not
	 *
	 * @return    string    HTML for the select list
	 */
	public static function aList($type, &$arr, $tag_name, $tag_attribs, $selected = null,
		$key = 'value', $text = 'text', $options_per_row = 0, $editable = true)
	{
		reset($arr);
		$html = array();

		if ($options_per_row > 1)
		{
			$percentageWidth = floor(floatval(100) / $options_per_row) - 2;
			$div             = "<div class=\"fabrik_subelement\" style=\"float:left;width:" . $percentageWidth . "%\">\n";
		}
		else
		{
			$div = '<div class="fabrik_subelement">';
		}

		if ($editable)
		{
			$selectText = $type == 'checkbox' ? ' checked="checked"' : ' selected="selected"';
		}
		else
		{
			$selectText = '';
		}

		for ($i = 0, $n = count($arr); $i < $n; $i++)
		{
			$k     = $arr[$i]->$key;
			$t     = $arr[$i]->$text;
			$id    = isset($arr[$i]->id) ? @$arr[$i]->id : null;
			$extra = '';
			$extra .= $id ? ' id="' . $arr[$i]->id . '"' : '';
			$found = false;

			if (is_array($selected))
			{
				foreach ($selected as $obj)
				{
					if (is_object($obj))
					{
						$k2 = $obj->$key;

						if ($k === $k2)
						{
							$found = true;
							$extra .= $selected;
							break;
						}
					}
					else
					{
						if ($k === $obj)
						{
							// Checkbox from db join
							$extra .= $selectText;
							$found = true;
							break;
						}
					}
				}
			}
			else
			{
				$extra .= $k === $selected ? ' checked="checked"' : '';
				$found = $k == $selected;
			}

			$html[] = $div;

			if ($editable)
			{
				$tmpName = $type === 'checkbox' ? $tag_name . '[' . $i . ']' : $tag_name;
				$html[]  = '<label class="' . $type . '">';
				$html[]  = '<input type="' . $type . '" value="' . $k . '" name="' . $tmpName . '" class="fabrikinput" ' . $extra . '/>';
			}

			if ($editable || $found)
			{
				$html[] = '<span>' . $t . '</span>';
			}

			if ($editable)
			{
				$html[] = '</label>';
			}

			$html[] = '</div>';
		}

		$html[] = "";

		return implode("\n", $html);
	}

	/**
	 * Keep session alive, for example, while editing or creating an article.
	 *
	 * @return  void
	 */
	public static function keepalive()
	{
		// Test since 2.0b3 don't do anything if loading from Fabrik win
		if (self::inAjaxLoadedPage())
		{
			return;
		}

		JHtml::_('behavior.keepalive');
	}

	/**
	 * Load the MCL canvas layer library
	 *
	 * @return  array Scripts needed to load MCL
	 */
	public static function mcl()
	{
		// Cant used compressed version as its not up to date
		$src = array('media/com_fabrik/js/lib/mcl/CANVAS.js', 'media/com_fabrik/js/lib/mcl/CanvasItem.js',
			'media/com_fabrik/js/lib/mcl/Cmorph.js', 'media/com_fabrik/js/lib/mcl/Layer.js', 'media/com_fabrik/js/lib/mcl/LayerHash.js',
			'media/com_fabrik/js/lib/mcl/Thread.js');

		if (!self::$mcl)
		{
			self::script($src);
			self::$mcl = true;
		}

		return $src;
	}

	/**
	 * Append a js file to the main require.js list of files to load.
	 * Will use the -min.js or .js file based on debug settings
	 *
	 * @param   array  &$srcs Already loaded scripts from framework()
	 * @param   string $file  JS File path relative to root without .js extension e.g. 'media/com_fabrik/js/list'
	 *
	 * @since   3.0b
	 *
	 * @return  void
	 */
	public static function addToFrameWork(&$srcs, $file)
	{
		$ext    = self::isDebug() ? '.js' : '-min.js';
		$srcs[] = $file . $ext;
	}

	/**
	 * Get the media folder
	 *
	 * @return  string  media folder
	 */
	public static function getMediaFolder()
	{
		return self::isDebug() ? 'media/com_fabrik/js' : 'media/com_fabrik/js/dist';
	}

	/**
	 * Load Fabrik's framework (js and base css file)
	 *
	 * @return  array  Framework js files
	 */
	public static function framework()
	{
		if (!self::$framework)
		{
			$app     = JFactory::getApplication();
			$version = new JVersion;
			FabrikHelperHTML::modalJLayouts();
			$liveSiteSrc = array();
			$liveSiteReq = array();
			$fbConfig    = JComponentHelper::getParams('com_fabrik');

			// Only use template test for testing in 2.5 with my temp J bootstrap template.
			$bootstrapped = in_array($app->getTemplate(), array('bootstrap', 'fabrik4')) || $version->RELEASE > 2.5;

			//$ext = self::isDebug() ? '.js' : '-min.js';
			$mediaFolder = self::getMediaFolder();
			$src         = array();
			JHtml::_('behavior.framework', true);

			// Ensure bootstrap js is loaded - as J template may not load it.
			if ($version->RELEASE > 2.5)
			{
				JHtml::_('bootstrap.framework');
				self::loadBootstrapCSS();
				JHtml::_('script', $mediaFolder . '/lib/jquery-ui/jquery-ui.min.js');
			}

			// Require js test - list with no cal loading ajax form with cal
			JHTML::_('behavior.calendar');
			$liveSiteReq['Chosen'] = $mediaFolder . '/chosen-loader';
			$liveSiteReq['Fabrik'] = $mediaFolder . '/fabrik';

			if ($bootstrapped)
			{
				$liveSiteReq['FloatingTips'] = $mediaFolder . '/tipsBootStrapMock';
			}
			else
			{
				$liveSiteReq['FloatingTips'] = $mediaFolder . '/tips';
			}

			if ($fbConfig->get('advanced_behavior', '0') == '1')
			{
				$chosenOptions = $fbConfig->get('advanced_behavior_options', '{}');
				$chosenOptions = empty($chosenOptions) ? new stdClass : ArrayHelper::fromObject(json_decode($chosenOptions));
				JHtml::_('stylesheet', 'jui/chosen.css', false, true);
				JHtml::_('script', 'jui/chosen.jquery.min.js', false, true, false, false, self::isDebug());
			}

			if (self::inAjaxLoadedPage() && !$bootstrapped)
			{
				// $$$ rob 06/02/2012 recall ant so that Color.detach is available (needed for opening a window from within a window)
				JHtml::_('script', 'media/com_fabrik/js/lib/art.js');
			}

			if ($fbConfig->get('advanced_behavior', '0') == '1')
			{
				$liveSiteSrc[] = "var chosenInterval = window.setInterval(function () {
						if (Fabrik.buildChosen) {
							window.clearInterval(chosenInterval);
	                        Fabrik.buildChosen('select.advancedSelect', " . json_encode($chosenOptions) . ");
						}
					}, 100);";
			}

			if (!self::inAjaxLoadedPage())
			{
				// Require.js now added in fabrik system plugin onAfterRender()
				JText::script('COM_FABRIK_LOADING');
				$src['Window'] = $mediaFolder . '/window.js';

				self::styleSheet(COM_FABRIK_LIVESITE . 'media/com_fabrik/css/fabrik.css');

				$liveSiteSrc[] = "\tFabrik.liveSite = '" . COM_FABRIK_LIVESITE . "';";
				$liveSiteSrc[] = "\tFabrik.package = '" . $app->getUserState('com_fabrik.package', 'fabrik') . "';";
				$liveSiteSrc[] = "\tFabrik.debug = " . (self::isDebug() ? 'true;' : 'false;');

				// need to put jLayouts in session data, and add it in the system plugin buildjs(), so just add %%jLayouts%% placeholder
				//$liveSiteSrc[] = "\tFabrik.jLayouts = " . json_encode(ArrayHelper::toObject(self::$jLayoutsJs)) . ";";
				$liveSiteSrc[] = "\tFabrik.jLayouts = %%jLayouts%%;\n";

				if ($bootstrapped)
				{
					$liveSiteSrc[] = "\tFabrik.bootstrapped = true;";
				}
				else
				{
					$liveSiteSrc[] = "\tFabrik.iconGen = new IconGenerator({scale: 0.5});";
					$liveSiteSrc[] = "\tFabrik.bootstrapped = false;";
				}

				$liveSiteSrc[] = self::tipInt();
				$liveSiteSrc   = implode("\n", $liveSiteSrc);
			}
			else
			{
				if ($bootstrapped)
				{
					$liveSiteSrc[] = "\tFabrik.bootstrapped = true;";
				}
				else
				{
					$liveSiteSrc[] = "\tFabrik.iconGen = new IconGenerator({scale: 0.5});";
					$liveSiteSrc[] = "\tFabrik.bootstrapped = false;";
				}

				$liveSiteSrc[] = "\tif (!Fabrik.jLayouts) {
				Fabrik.jLayouts = {};
				}
				Fabrik.jLayouts = jQuery.extend(Fabrik.jLayouts, %%jLayouts%%);";
			}

			self::script($liveSiteReq, $liveSiteSrc, '-min.js');
			self::$framework = $src;
		}

		self::addToSessionJLayouts();

		return self::$framework;
	}

	/**
	 * Build JS to initiate tips, and observer application state changes,
	 * reloading the tips if needed.
	 *
	 * @return  string
	 */
	public static function tipInt()
	{
		$tipOpts = self::tipOpts();
		$tipJs   = array();
		$tipJs[] = "\tFabrik.tips = new FloatingTips('.fabrikTip', " . json_encode($tipOpts) . ");";
		$tipJs[] = "\tFabrik.addEvent('fabrik.list.updaterows', function () {";
		$tipJs[] = "\t\t// Reattach new tips after list redraw";
		$tipJs[] = "\t\tFabrik.tips.attach('.fabrikTip');";
		$tipJs[] = "\t});";
		$tipJs[] = "\tFabrik.addEvent('fabrik.plugin.inlineedit.editing', function () {";
		$tipJs[] = "\t\tFabrik.tips.hideAll();";
		$tipJs[] = "\t});";
		$tipJs[] = "\tFabrik.addEvent('fabrik.list.inlineedit.setData', function () {";
		$tipJs[] = "\t\tFabrik.tips.attach('.fabrikTip');";
		$tipJs[] = "\t});";

		// Reload tips if a form is loaded (e.g. a list view with ajax links on which loads a form in a popup)
		// see: https://github.com/Fabrik/fabrik/issues/1394
		$tipJs[] = "\tFabrik.addEvent('fabrik.form.loaded', function () {";
		$tipJs[] = "\t\tFabrik.tips.attach('.fabrikTip');";
		$tipJs[] = "\t});";

		$tipJs[] = "\tFabrik.addEvent('fabrik.list.loaded', function () {";
		$tipJs[] = "\t\tFabrik.tips.attach('.fabrikTip');";
		$tipJs[] = "\t});";

		// Load tips
		//$tipJs[] = "\tFabrik.tips.attach('.fabrikTip');";

		return implode("\n", $tipJs);
	}

	/**
	 * Checks the js_base_url global config, to see if admin has set a base URI they want to use to
	 * fetch JS assets from.  Allows for putting JS files in a fast CDN like Amazon.  If not set,
	 * return COM_FABRIK_LIVESITE.
	 *
	 * @return string
	 */
	public static function getJSAssetBaseURI()
	{
		if (!isset(static::$baseJSAssetURI))
		{
			$usersConfig      = JComponentHelper::getParams('com_fabrik');
			$requirejsBaseURI = $usersConfig->get('requirejs_base_uri', COM_FABRIK_LIVESITE);

			if (empty($requirejsBaseURI))
			{
				$requirejsBaseURI = COM_FABRIK_LIVESITE;
			}

			$requirejsBaseURI       = rtrim($requirejsBaseURI, '/') . '/';
			static::$baseJSAssetURI = $requirejsBaseURI;
		}

		return static::$baseJSAssetURI;
	}

	/**
	 * Ini the require JS configuration
	 * Stores the shim and config to the session, which Fabrik system plugin
	 * then uses to inject scripts into document.
	 *
	 * @param   array $shim  Shim js files
	 * @param   array $paths Additional require js paths
	 *
	 * @since   3.1
	 *
	 * @return  void
	 */
	public static function iniRequireJs($shim = array(), $paths = array())
	{
		$session      = JFactory::getSession();
		$requirePaths = (object) array_merge((array) self::requirePaths(), $paths);
		$framework    = array();
		$deps         = array();
		$j3           = FabrikWorker::j3();

		$requirejsBaseURI = self::getJSAssetBaseURI();

		// Load any previously created shim (e.g form which then renders list in outro text)
		$newShim = $session->get('fabrik.js.shim', array());

		foreach ($shim as $k => &$s)
		{
			if (is_array($newShim) && array_key_exists($k, $newShim))
			{
				$s->deps = array_merge($s->deps, $newShim[$k]->deps);
			}

			$newShim[$k] = $s;
		}

		$navigator = JBrowser::getInstance();

		if ($navigator->getBrowser() == 'msie' && !$j3)
		{
			$deps[] = 'lib/flexiejs/flexie';
		}

		$deps[] = 'fab/utils';
		$deps[] = 'jquery';

		$deps[] = 'fab/mootools-ext';
		$deps[] = 'lib/Event.mock';

		if (!$j3)
		{
			$deps[] = 'lib/art';
			$deps[] = 'fab/tips';
			$deps[] = 'fab/icons';
			$deps[] = 'fab/icongen';
		}

		self::addRequireJsShim($framework, 'fab/fabrik', $deps, false);
		self::addRequireJsShim($framework, 'fab/autocomplete-bootstrap', array('fab/fabrik'), false);
		self::addRequireJsShim($framework, 'jQueryUI', array('jquery'), false);

		$newShim = array_merge($framework, $newShim);
		$config  = array();

		$config[] = "define('jquery', [], function() {
			return jQuery;
		});";

		// Required for full calendar
		$config[] = "define('moment', [], function() {
			return moment;
		});";

		$opts = array(
			'baseUrl' => $requirejsBaseURI,
			'paths' => $requirePaths,
			'shim' => $newShim,
			'waitSeconds' => 30
		);

		// Force script reloads if in burst is on.
		if (self::getBurstJs())
		{
			$opts['urlArgs'] = 'bust=' . time();
		}

		$config[] = "requirejs.config(";
		$config[] = json_encode($opts, self::isDebug() && defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : false);
		$config[] = ");";
		$config[] = "\n";

		// Store in session - included in fabrik system plugin
		$session->set('fabrik.js.shim', $newShim);
		$session->set('fabrik.js.config', $config);
	}

	/**
	 * Should we 'burst' the loading of JS files. If true then loaded
	 * js files will be appended with a random query string ensuring they
	 * are not loaded from cache
	 *
	 * @return boolean
	 * @throws Exception
	 */
	protected static function getBurstJs()
	{
		$app    = JFactory::getApplication();
		$config = JComponentHelper::getParams('com_fabrik');

		return (bool) $app->input->get('burst', $config->get('burst_js', 0));
	}

	/**
	 * Helper for create RequireJS shim dependencies
	 *
	 * @param array  $framework    Array to append the dependency to
	 * @param string $key          RequireJs key - the file to load
	 * @param array  $dependencies The dependencies to load before the $key file
	 * @param bool   $useMin       Should we append -min to the $key if we are not in debug mode
	 */
	protected static function addRequireJsShim(&$framework, $key, $dependencies, $useMin = true)
	{
		$ext                    = self::isDebug() || !$useMin ? '' : '-min';
		$info                   = new stdClass;
		$info->deps             = $dependencies;
		$framework[$key . $ext] = $info;
	}

	public static function mediaFile($file)
	{
		return self::isDebug() ? 'media/com_fabrik/js/' . $file : 'media/com_fabrik/js/dist/' . $file;
	}

	/**
	 * Get the js file path map that requireJS uses
	 *
	 * @since  3.1
	 *
	 * @return stdClass
	 */
	protected static function requirePaths()
	{
		$r              = new stdClass;
		$r->fab         = 'media/com_fabrik/js';
		$r->lib         = 'media/com_fabrik/js/lib';
		$r->element     = 'plugins/fabrik_element';
		$r->list        = 'plugins/fabrik_list';
		$r->form        = 'plugins/fabrik_form';
		$r->cron        = 'plugins/fabrik_cron';
		$r->viz         = 'plugins/fabrik_visualization';
		$r->admin       = 'administrator/components/com_fabrik/views';
		$r->adminfields = 'administrator/components/com_fabrik/models/fields';

		$r->jQueryUI = 'media/com_fabrik/js/lib/jquery-ui/jquery-ui';
		$r->chosen   = 'media/jui/js/chosen.jquery.min';
		$r->ajaxChosen   = 'media/jui/js/ajax-chosen.min';

		// We are now loading compressed js fabrik files from the media/com_fabrik/js/dist folder
		// This avoids AMD issues where we were loading fab/form or fab/form-min.
		if (!self::isDebug())
		{
			$r->fab .= '/dist';
		}

		$version = new JVersion;

		if ($version->RELEASE >= 3.2 && $version->DEV_LEVEL > 1)
		{
			$r->punycode = 'media/system/js/punycode';
		}

		return $r;
	}

	/**
	 * Load mootools lib
	 *
	 * @deprecated use ::framework instead
	 *
	 * @return  void
	 */
	public static function mootools()
	{
		self::framework();
	}

	/**
	 * Load J!'s bootstrap CSS if requested.  Special case for iframes in non J! pages loading us.
	 *
	 * @return  void
	 */
	public static function loadBootstrapCSS()
	{
		$app = JFactory::getApplication();
		if ($app->input->get('loadbootstrapcss', '') !== '')
		{
			$doc = JFactory::getDocument();
			JHtmlBootstrap::loadCss(true, $doc->direction);
		}
	}

	/**
	 * Get tip options to control its fx - set in Fabrik global configuration
	 *
	 * @return stdClass
	 */
	public static function tipOpts()
	{
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$opts        = new stdClass;
		$opts->tipfx = 'Fx.Transitions.' . $usersConfig->get('tipfx', 'Linear');

		if ($usersConfig->get('tipfx', 'Linear') !== 'Linear')
		{
			$opts->tipfx .= '.' . $usersConfig->get('tipfx_ease', 'easeIn');
		}

		$opts->duration = $usersConfig->get('tipfx_duration', '500');
		$opts->distance = (int) $usersConfig->get('tipfx_distance', '20');
		$opts->fadein   = (bool) $usersConfig->get('tipfx_fadein', false);

		return $opts;
	}

	/**
	 * Add a script declaration to the session. Inserted into doc via system plugin
	 *
	 * @param   string $script Js code to add
	 *
	 * @return  null
	 */
	public static function addScriptDeclaration($script)
	{
		self::addToSessionScripts($script);
	}

	/**
	 * Add a rendered JLayout to the Fabrik.jLayouts object
	 *
	 * @param   string   $name       Reference to layout, used in JavaScript
	 * @param   string   $layoutName Dot syntax path to layout file
	 * @param   stdClass $data       Template data
	 * @param   array    $paths      Additional layout paths
	 * @param   array    $options    Options
	 */
	public static function jLayoutJs($name, $layoutName, stdClass $data = null, $paths = array(), $options = array())
	{
		if (!array_key_exists($name, self::$jLayoutsJs))
		{
			$layout                  = self::getLayout($layoutName, $paths, $options);
			self::$jLayoutsJs[$name] = $layout->render($data);
		}
	}

	/**
	 * Add a CSS style declaration, either to the head or inline if format=raw
	 *
	 * @param   string $style CSS
	 *
	 * @return  void
	 */
	public static function addStyleDeclaration($style)
	{
		$app = JFactory::getApplication();

		if ($app->input->get('format') == 'raw')
		{
			echo '<style type="text/css">' . $style . '</style>';
		}
		else
		{
			JFactory::getDocument()->addStyleDeclaration($style);
		}
	}

	/**
	 * Sometimes you want to load a page in an iframe and want to use tmpl=component - in this case
	 * append iframe=1 to the url to ensure that we don't try to add the scripts via FBAsset()
	 *
	 * @return  bool
	 */
	public static function inAjaxLoadedPage()
	{
		$app     = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');

		// Are we in fabrik or a content view, if not return false (things like com_config need to load in Mootools)
		$app    = JFactory::getApplication();
		$input  = $app->input;
		$option = $input->get('option');

		if ($option !== 'com_' . $package && $option !== 'com_content')
		{
			return false;
		}

		if (class_exists('JSite'))
		{
			$app   = JFactory::getApplication();
			$menus = $app->getMenu();
			$menu  = $menus->getActive();

			if (is_object($menu) && ($menu->browserNav == 2))
			{
				return false;
			}
		}

		return $input->get('format') == 'raw'
		|| ($input->get('tmpl') == 'component' && $input->get('iframe') != 1 && $input->get('format') !== 'pdf');
	}

	/**
	 * Returns true if either J! or Fabrik debug is enabled
	 * Use this for things like choosing whether to include compressed or uncompressed JS, etc.
	 * Do NOT use for actual debug output.
	 *
	 * @param   bool $enabled Set to true if Fabrik debug global option must be set to true
	 *
	 * @return  bool
	 */
	public static function isDebug($enabled = false)
	{
		$app    = JFactory::getApplication();
		$config = JComponentHelper::getParams('com_fabrik');

		if ($enabled && $config->get('use_fabrikdebug') == 0)
		{
			return false;
		}

		if ($config->get('use_fabrikdebug') == 2)
		{
			return true;
		}

		$config = JFactory::getConfig();
		$debug  = (int) $config->get('debug');

		return $debug === 1 || $app->input->get('fabrikdebug', 0) == 1;
	}

	/**
	 * Returns true if either J! system debug is true, and &fabrikdebug=2,
	 * will then bypass ALL redirects, so we can see J! profile info.
	 *
	 * @return  bool
	 */
	public static function isDebugSubmit()
	{
		$app    = JFactory::getApplication();
		$config = JComponentHelper::getParams('com_fabrik');

		if ($config->get('use_fabrikdebug') == 0)
		{
			return false;
		}

		$jConfig = JFactory::getConfig();
		$debug   = (int) $jConfig->get('debug');

		return $debug === 1 && $app->input->get('fabrikdebug', 0) == 2;
	}

	/**
	 * Wrapper for JHTML::Script() loading with require.js
	 * If not debugging will replace file names .js => -min.js
	 *
	 * @param   mixed  $file       String or array of files to load, relative path to root for local files
	 *                             e.g. 'administrator/components/com_fabrik/models/fields/tables.js'
	 * @param   string $onLoad     Optional js to run once the Js file has been loaded
	 * @param   string $minSuffix  The minimised file suffix to use, replaces '.js'
	 *
	 * @return  void
	 */
	public static function script($file, $onLoad = '', $minSuffix = '-min.js')
	{
		if (empty($file))
		{
			return;
		}

		if (is_array($onLoad))
		{
			$onLoad = implode("\n", $onLoad);
		}

		$ext   = self::isDebug() ? '.js' : $minSuffix;
		$paths = self::requirePaths();
		$files = (array) $file;

		// Replace with minified files if found
		foreach ($files as &$file)
		{
			if (!(JString::stristr($file, 'http://') || JString::stristr($file, 'https://')))
			{
				/**
				 * Fix for new media compressed JS paths, which we switched from ./js/foo-mins.js to ./js/dist/foo.js.
				 * Some code feeds us the new dist path, but some still uses just media/com_fabrik/js.  So, if we're
				 * not in debug mode, and the path is media/com_fabrik/js and doesn't have /dist, add it.
				 **/
				if (!self::isDebug())
				{
					if (strpos($file, 'media/com_fabrik/js/') !== false)
					{
						if (strpos($file, 'media/com_fabrik/js/lib/') === false)
						{
							if (strpos($file, 'media/com_fabrik/js/dist/') === false)
							{
								$file = str_replace('media/com_fabrik/js/', 'media/com_fabrik/js/dist/', $file);
							}
						}
					}
				}

				if (JFile::exists(COM_FABRIK_BASE . $file))
				{
					$compressedFile = str_replace('.js', $ext, $file);

					if (JFile::exists(COM_FABRIK_BASE . $compressedFile) || JFile::exists($compressedFile))
					{
						$file = $compressedFile;
					}
				}
			}

			// Set file name based on requirejs basePath
			$pathMatched = false;

			foreach ($paths as $requireKey => $path)
			{
				if (strstr($file, $path))
				{
					$file        = str_replace($path, '', $file);
					$file        = str_replace('.js', '', $file);
					$file        = $requireKey . $file;
					$pathMatched = true;
				}
			}

			if (!$pathMatched)
			{
				if (!(JString::stristr($file, 'http://') || JString::stristr($file, 'https://')))
				{
					$file = COM_FABRIK_LIVESITE . $file;
				}
			}
		}

		// Need to load element for ajax popup forms in IE.
		$needed = array();

		if (!FabrikWorker::j3())
		{
			$needed[] = self::isDebug() ? 'fab/icongen' : 'fab/icongen-min';
			$needed[] = self::isDebug() ? 'fab/icons' : 'fab/icons-min';
		}

		foreach ($needed as $need)
		{
			if (!in_array($need, $files))
			{
				array_unshift($files, $need);
			}
		}

		$files = array_unique($files);

		// Set names from $files keys if assoc array. In general it is for require js files
		$names = array_keys($files) !== range(0, count($files) - 1) ? array_keys($files) : array();

		$files     = "['" . implode("', '", $files) . "']";
		$require[] = 'requirejs(' . ($files) . ', function (' . implode(", ", $names) . ') {';
		$require[] = $onLoad;
		$require[] = '});';
		$require[] = "\n";
		$require   = implode("\n", $require);
		self::addToSessionScripts($require);
	}

	/**
	 * Add jLayouts to session - will then be added via Fabrik System plugin
	 *
	 * @return  void
	 */
	protected static function addToSessionJLayouts()
	{
		$key     = 'fabrik.js.jlayouts';
		$session = JFactory::getSession();

		/*
		 * No need to figure out what's already there, unlike addToSessionScripts,
		 * we're just updating the whole thing each time framework is added.
		 */

		$session->set($key, self::$jLayoutsJs);
	}

	/**
	 * Add script to session - will then be added via Fabrik System plugin
	 *
	 * @param   string $js JS code
	 *
	 * @return  void
	 */
	protected static function addToSessionScripts($js)
	{
		$key     = 'fabrik.js.scripts';
		$session = JFactory::getSession();

		if ($session->has($key))
		{
			$scripts = $session->get($key);
		}
		else
		{
			$scripts = array();
		}

		$scripts[] = $js;
		$session->set($key, $scripts);
	}

	/**
	 * Add script to session - will then be added (in head) via Fabrik System plugin
	 *
	 * @param   string $js JS code
	 *
	 * @return  void
	 */
	protected static function addToSessionHeadScripts($js)
	{
		$key     = 'fabrik.js.head.scripts';
		$session = JFactory::getSession();

		if ($session->has($key))
		{
			$scripts = $session->get($key);
		}
		else
		{
			$scripts = array();
		}

		$scripts[] = $js;
		$session->set($key, $scripts);
	}

	/**
	 * Load the slimbox / media box css and js files
	 *
	 * @return  void
	 */
	public static function slimbox()
	{
		$input = JFactory::getApplication()->input;

		if ($input->get('format') === 'raw')
		{
			return;
		}

		if (!self::$modal)
		{
			$fbConfig = JComponentHelper::getParams('com_fabrik');

			if ($fbConfig->get('include_lightbox_js', 1) == 0)
			{
				return;
			}

			if ($fbConfig->get('use_mediabox', false))
			{
				$folder  = 'components/com_fabrik/libs/mediabox-advanced/';
				$mbStyle = $fbConfig->get('mediabox_style', 'Dark');
				JHTML::stylesheet($folder . 'mediabox-' . $mbStyle . '.css');
				self::script($folder . 'mediaboxAdv.js');
			}
			else
			{
				if (FabrikWorker::j3())
				{
					JHTML::stylesheet('components/com_fabrik/libs/slimbox2/css/slimbox2.css');
					self::script('components/com_fabrik/libs/slimbox2/js/slimbox2.js');
				}
				else
				{
					JHTML::stylesheet('components/com_fabrik/libs/slimbox1.64/css/slimbox.css');
					self::script('components/com_fabrik/libs/slimbox1.64/js/slimbox.js');
				}
			}

			self::$modal = true;
		}
	}

	/**
	 * Load the slide-show css and js files
	 *
	 * @return  void
	 */
	public static function slideshow()
	{
		/*
		 * switched from cycle2, to bootstrap, so for now don't need anything
		 */
		/*
		$folder = 'components/com_fabrik/libs/cycle2/';
		$ext = self::isDebug() ? '.js' : '.min.js';
		self::script($folder . 'jquery.cycle2' . $ext);
		*/
	}

	/**
	 * Attach tooltips to document
	 *
	 * @param   string $selector       String class name of tips
	 * @param   array  $params         Array parameters
	 * @param   string $selectorPrefix Limit the tips selection to those contained within an id
	 *
	 * @return  void
	 */
	public static function tips($selector = '.hasTip', $params = array(), $selectorPrefix = 'document')
	{
		$sig = md5(serialize(array($selector, $params)));

		if (isset(self::$tips[$sig]) && (self::$tips[$sig]))
		{
			return;
		}

		// Setup options object
		$opt['maxTitleChars'] = (isset($params['maxTitleChars']) && ($params['maxTitleChars'])) ? (int) $params['maxTitleChars'] : 50;
		$opt['offsets']       = (isset($params['offsets'])) ? (int) $params['offsets'] : null;
		$opt['showDelay']     = (isset($params['showDelay'])) ? (int) $params['showDelay'] : null;
		$opt['hideDelay']     = (isset($params['hideDelay'])) ? (int) $params['hideDelay'] : null;
		$opt['className']     = (isset($params['className'])) ? $params['className'] : null;
		$opt['fixed']         = (isset($params['fixed']) && ($params['fixed'])) ? '\\true' : '\\false';
		$opt['onShow']        = (isset($params['onShow'])) ? '\\' . $params['onShow'] : null;
		$opt['onHide']        = (isset($params['onHide'])) ? '\\' . $params['onHide'] : null;

		$options = json_encode($opt);

		// Attach tooltips to document
		// Force the zindex to 9999 so that it appears above the popup window.
		$tooltipInit = 'window.addEvent("fabrik.load", function() {if(typeOf(' . $selectorPrefix . ') !== \'null\' && ' . $selectorPrefix
			. '.getElements(\'' . $selector
			. '\').length !== 0) {window.JTooltips = new Tips(' . $selectorPrefix . '.getElements(\'' . $selector . '\'), ' . $options
			. ');$$(".tool-tip").setStyle("z-index", 999999);}});';
		/* self::addScriptDeclaration($tooltipInit); */

		self::$tips[$sig] = true;
	}

	/**
	 * Add a debug out put section
	 *
	 * @param   mixed  $content String/object
	 * @param   string $title   Debug title
	 *
	 * @return  void
	 */
	public static function debug($content, $title = 'output:')
	{
		$config = JComponentHelper::getParams('com_fabrik');
		$app    = JFactory::getApplication();
		$input  = $app->input;

		if ($config->get('use_fabrikdebug') == 0)
		{
			return;
		}

		if ($input->getBool('fabrikdebug', 0, 'request') != 1)
		{
			return;
		}

		if ($input->get('format') == 'raw')
		{
			return;
		}

		echo '<div class="fabrikDebugOutputTitle">' . $title . '</div>';
		echo '<div class="fabrikDebugOutput fabrikDebugHidden">';

		if (is_object($content) || is_array($content))
		{
			echo '<pre>' . htmlspecialchars(print_r($content, true)) . '</pre>';
		}
		else
		{
			// Remove any <pre> tags provided by e.g. JQuery::dump
			$content = preg_replace('/(^\s*<pre( .*)?>)|(<\/pre>\s*$)/i', '', $content);
			echo htmlspecialchars($content);
		}

		echo '</div>';

		if (!isset(self::$debug))
		{
			self::$debug = true;
			$style       = ".fabrikDebugOutputTitle{padding:5px;background:#efefef;color:#333;border:1px solid #999;cursor:pointer}";
			$style .= ".fabrikDebugOutput{padding:5px;background:#efefef;color:#999;}";
			$style .= ".fabrikDebugOutput pre{padding:5px;background:#efefef;color:#999;}";
			$style .= ".fabrikDebugHidden{display:none}";
			self::addStyleDeclaration($style);
			$script = "window.addEvent('domready', function() {
				document.getElements('.fabrikDebugOutputTitle').each(function (title) {
				title.addEvent('click', function (e) {
				title.getNext().toggleClass('fabrikDebugHidden');
		});
		});
		})";
			self::addScriptDeclaration($script);
		}
	}

	/**
	 * Create html for ajax folder browser (used by file-upload and image elements)
	 *
	 * @param   array  $folders array of folders to show
	 * @param   string $path    start path
	 * @param   string $tpl     view template
	 *
	 * @return  string    html snippet
	 */
	public static function folderAjaxSelect($folders, $path = '', $tpl = '')
	{
		$str   = array();
		$str[] = '<a href="#" class="btn btn-default toggle" title="' . FText::_('COM_FABRIK_BROWSE_FOLDERS') . '">';
		$str[] = self::image('orderneutral.png', 'form', $tpl, array('alt' => FText::_('COM_FABRIK_BROWSE_FOLDERS'), 'icon-class' => 'icon-menu-2'));
		$str[] = '</a>';
		$str[] = '<div class="folderselect-container">';
		$str[] = '<span class="breadcrumbs"><a href="#">' . FText::_('HOME') . '</a><span> / </span>';
		$i     = 1;
		$path  = explode("/", $path);

		foreach ($path as $p)
		{
			$str[] = '<a href="#" class="crumb' . $i . '">' . $p . '</a><span> / </span>';
			$i++;
		}

		$str[] = '</span>';
		$str[] = '<ul class="folderselect">';
		settype($folders, 'array');

		foreach ($folders as $folder)
		{
			if (trim($folder) != '')
			{
				$str[] = '<li class="fileupload_folder"><a href="#">' . $folder . '</a></li>';
			}
		}

		// For html validation
		if (empty($folder))
		{
			$str[] = '<li></li>';
		}

		$str[] = '</ul></div>';

		return implode("\n", $str);
	}

	/**
	 * Add auto-complete JS code to head
	 *
	 * @param   string $htmlId      Of element to turn into autocomplete
	 * @param   int    $elementId   Element id
	 * @param   int    $formId      Form id
	 * @param   string $plugin      Plugin name
	 * @param   array  $opts        * onSelection - function to run when option selected
	 *                              * max - max number of items to show in selection list
	 *
	 * @return  void
	 */
	public static function autoComplete($htmlId, $elementId, $formId, $plugin = 'field', $opts = array())
	{
		/*
		$input = JFactory::getApplication()->input;

		if ($input->get('format') === 'raw')
		{
			return;
		}
		*/

		$json = self::autoCompleteOptions($htmlId, $elementId, $formId, $plugin, $opts);
		$str  = json_encode($json);
		JText::script('COM_FABRIK_NO_RECORDS');
		JText::script('COM_FABRIK_AUTOCOMPLETE_AJAX_ERROR');
		$jsFile = 'autocomplete';

		if (FabrikWorker::j3())
		{
			$jsFile = $plugin === 'cascadingdropdown' ? 'autocomplete-bootstrap-cdd' : 'autocomplete-bootstrap';
		}

		$needed   = array();
		$needed[] = 'fab/' . $jsFile;
		$needed[] = 'lib/Event.mock';
		$needed   = implode("', '", $needed);
		self::addScriptDeclaration(
			"require(['$needed'], function (AutoComplete) {
	new AutoComplete('$htmlId', $str);
});"
		);
	}

	/**
	 * Gets auto complete js options (needed separate from autoComplete as db js class needs these values for repeat
	 * group duplication)
	 *
	 * @param   string $htmlId      Element to turn into autocomplete
	 * @param   int    $elementId   Element id
	 * @param   int    $formId      Form id
	 * @param   string $plugin      Plugin type
	 * @param   array  $opts        * onSelection - function to run when option selected
	 *                              * max - max number of items to show in selection list
	 *
	 * @return  array    Autocomplete options (needed for elements so when duplicated we can create a new
	 *                   FabAutocomplete object
	 */
	public static function autoCompleteOptions($htmlId, $elementId, $formId, $plugin = 'field', $opts = array())
	{
		$json = new stdClass;

		if (!array_key_exists('minTriggerChars', $opts))
		{
			$usersConfig           = JComponentHelper::getParams('com_fabrik');
			$json->minTriggerChars = (int) $usersConfig->get('autocomplete_min_trigger_chars', '1');
		}

		$app       = JFactory::getApplication();
		$package   = $app->getUserState('com_fabrik.package', 'fabrik');
		$json->url = 'index.php?option=com_' . $package . '&format=raw';
		$json->url .= $app->isAdmin() ? '&task=plugin.pluginAjax' : '&view=plugin&task=pluginAjax';
		$json->url .= '&g=element&element_id=' . $elementId
			. '&formid=' . $formId . '&plugin=' . $plugin . '&method=autocomplete_options&package=' . $package;
		$c = FArrayHelper::getValue($opts, 'onSelection');

		if ($c != '')
		{
			$json->onSelections = $c;
		}

		foreach ($opts as $k => $v)
		{
			$json->$k = $v;
		}

		$json->formRef   = FArrayHelper::getValue($opts, 'formRef', 'form_' . $formId);
		$json->container = FArrayHelper::getValue($opts, 'container', 'fabrikElementContainer');
		$json->menuclass = FArrayHelper::getValue($opts, 'menuclass', 'auto-complete-container');

		return $json;
	}

	/**
	 * Load the auto-complete script once
	 *
	 * @deprecated since 3.1b
	 *
	 * @return  void
	 */
	public static function autoCompleteScript()
	{
	}

	public static function atWho($selector, $placeHolders = array())
	{
		array_filter($placeHolders);
		$key = $selector . implode('.', $placeHolders);

		if (!array_key_exists($key, self::$atWho))
		{
			$replacements = FabrikWorker::globalReplacements();
			$replacements = array_keys($replacements);

			$replacements = array_map(function ($v)
			{
				$v = str_replace(array('{', '}'), array('', ''), $v);

				return $v;
			}, $replacements);

			$placeHolders = array_merge($placeHolders, $replacements);

			$placeHolders      = json_encode($placeHolders);
			$script[]          = "jQuery('$selector').atwho({
				'at': '{',
				'insertTpl' : '\${atwho-at}\${name}}',
				data: $placeHolders,
				 limit: 5,
            });";
			self::$atWho[$key] = true;
			$css               = self::isDebug() ? 'jquery.atwho.css' : 'jquery.atwho.min.css';
			FabrikHelperHTML::stylesheet('media/com_fabrik/js/lib/at/' . $css);

			$needed[] = self::isDebug() ? '\'lib/caret/caret\'' : '\'lib/caret/caret-min\'';
			$needed[] = self::isDebug() ? '\'lib/at/atwho\'' : '\'lib/at/atwho-min\'';
			$needed   = implode(", ", $needed);
			$script   = implode("\n", $script);
			self::addScriptDeclaration(
				"requirejs([$needed], function (j, f) {
	$script
});"
			);
		}
	}

	/**
	 * Load the Facebook Graph API
	 *
	 * @param   string $appId  Application id
	 * @param   string $locale locale e.g 'en_US'
	 * @param   array  $meta   meta tags to add
	 *
	 * @return  void|string
	 */
	public static function facebookGraphAPI($appId, $locale = 'en_US', $meta = array())
	{
		if (!isset(self::$facebookgraphapi))
		{
			self::$facebookgraphapi = true;

			return "<div id=\"fb-root\"></div>
			<script>
			window.fbAsyncInit = function() {
			FB.init({appId: '$appId', status: true, cookie: true,
			xfbml: true});
		};
		(function() {
		var e = document.createElement('script'); e.async = true;
		e.src = document.location.protocol +
		'//connect.facebook.net/$locale/all.js';
		document.getElementById('fb-root').appendChild(e);
		}());
		</script>";
		}

		$document  = JFactory::getDocument();
		$data      = array('custom' => array());
		$typeFound = false;

		foreach ($meta as $k => $v)
		{
			if (is_array($v))
			{
				$v = implode(',', $v);
			}

			$v = strip_tags($v);

			// $$$ rob og:type required
			if ($k == 'og:type')
			{
				$typeFound = true;

				if ($v == '')
				{
					$v = 'article';
				}
			}

			$data['custom'][] = '<meta property="' . $k . '" content="' . $v . '"/>';
		}

		if (!$typeFound)
		{
			$data['custom'][] = '<meta property="og:type" content="article"/>';
		}

		$document->setHeadData($data);
	}

	/**
	 * Add path for image() function
	 *
	 * @param   string $path         to add to list of folders to search
	 * @param   string $type         of path set to load (currently only image is used)
	 * @param   string $view         are we looking at loading form or list images?
	 * @param   bool   $highPriority should the added $path take precedence over previously added paths (default true)
	 *
	 * @since 3.0
	 *
	 * @return  array paths
	 */
	public static function addPath($path = '', $type = 'image', $view = 'form', $highPriority = true)
	{
		if (!array_key_exists($type, self::$helperpaths))
		{
			self::$helperpaths[$type] = array();
			$app                      = JFactory::getApplication();
			$template                 = $app->getTemplate();

			switch ($type)
			{
				case 'image':
					if ($app->isAdmin())
					{
						self::$helperpaths[$type][] = JPATH_SITE . DIRECTORY_SEPARATOR . 'administrator/templates/' . $template . '/images/';
					}

					self::$helperpaths[$type][] = COM_FABRIK_BASE . 'templates/' . $template . '/html/com_fabrik/' . $view . '/%s/images/';
					self::$helperpaths[$type][] = COM_FABRIK_BASE . 'templates/' . $template . '/html/com_fabrik/' . $view . '/images/';
					self::$helperpaths[$type][] = COM_FABRIK_BASE . 'templates/' . $template . '/html/com_fabrik/images/';
					self::$helperpaths[$type][] = COM_FABRIK_FRONTEND . '/views/' . $view . '/tmpl/%s/images/';
					self::$helperpaths[$type][] = COM_FABRIK_BASE . 'media/com_fabrik/images/';
					self::$helperpaths[$type][] = COM_FABRIK_BASE . 'images/';
					self::$helperpaths[$type][] = COM_FABRIK_BASE . 'images/stories/';
					self::$helperpaths[$type][] = COM_FABRIK_BASE . 'media/system/images/';
					break;
			}
		}

		if (!array_key_exists($path, self::$helperpaths[$type]) && $path !== '')
		{
			$highPriority ? array_unshift(self::$helperpaths[$type], $path) : self::$helperpaths[$type][] = $path;
		}

		return self::$helperpaths[$type];
	}

	/**
	 * Search various folder locations for an image
	 *
	 * @param   string $file file name
	 * @param   string $type type e.g. form/list/element
	 * @param   string $tmpl template folder name
	 *
	 * @return  string    full path name if found, original filename if not found
	 */
	public static function getImagePath($file, $type = 'form', $tmpl = '')
	{
		$file  = JString::ltrim($file, DIRECTORY_SEPARATOR);
		$paths = self::addPath('', 'image', $type, true);

		foreach ($paths as $path)
		{
			$path = sprintf($path, $tmpl);
			$src  = $path . $file;

			if (JFile::exists($src))
			{
				return $src;
			}
		}

		return '';
	}

	/**
	 * Search various folder locations for a template image
	 *
	 * @param   string       $file       File name
	 * @param   string       $type       Type e.g. form/list/element
	 * @param   string       $tmpl       Template folder name
	 * @param   array|string $properties Assoc list of properties or string (if you just want to set the image alt tag)
	 * @param   bool         $srcOnly    Src only (default false)
	 * @param   array        $opts       Additional render options:
	 *                                   forceImage: regardless of in J3 site - render an <img> if set to true
	 *                                   (bypasses bootstrap icon loading)
	 *
	 * @since 3.0
	 *
	 * @return  string  image
	 */
	public static function image($file, $type = 'form', $tmpl = '', $properties = array(), $srcOnly = false, $opts = array())
	{
		if (is_string($properties))
		{
			$properties = array('alt' => $properties);
		}

		$forceImage = FArrayHelper::getValue($opts, 'forceImage', false);

		if (FabrikWorker::j3() && $forceImage !== true)
		{
			unset($properties['alt']);
			$class = FArrayHelper::getValue($properties, 'icon-class', '');
			$class = 'icon-' . JFile::stripExt($file) . ($class ? ' ' . $class : '');
			unset($properties['icon-class']);
			$class .= ' ' . FArrayHelper::getValue($properties, 'class', '');
			unset($properties['class']);
			$p = self::propertiesFromArray($properties);

			if (!$srcOnly)
			{
				return FabrikHelperHTML::icon($class, '', $p);
			}
			else
			{
				return $class;
			}
		}

		$src = self::getImagePath($file, $type, $tmpl);
		$src = str_replace(COM_FABRIK_BASE, COM_FABRIK_LIVESITE, $src);
		$src = str_replace("\\", "/", $src);

		if ($srcOnly)
		{
			return $src;
		}

		if (isset($properties['class']))
		{
			$properties['class'] .= ' fabrikImg';
		}
		else
		{
			$properties['class'] = 'fabrikImg';
		}

		$p = self::propertiesFromArray($properties);

		return $src == '' ? '' : '<img src="' . $src . '" ' . $p . '/>';
	}

	/**
	 * Build HTML properties from an associated array
	 *
	 * @param   array $properties Properties
	 *
	 * @return string
	 */
	public static function propertiesFromArray($properties)
	{
		$bits = array();
		$p    = '';

		foreach ($properties as $key => $val)
		{
			if ($key === 'title')
			{
				$val = htmlspecialchars($val, ENT_QUOTES);
			}

			$bits[$key] = $val;
		}

		foreach ($bits as $key => $val)
		{
			$val = str_replace('"', "'", $val);
			$p .= $key . '="' . $val . '" ';
		}

		return $p;
	}

	/**
	 * Build array of items for use in grid()
	 *
	 * @param   array  $values              Option values
	 * @param   array  $labels              Option labels
	 * @param   array  $selected            Selected options
	 * @param   string $name                Input name
	 * @param   string $type                Checkbox/radio etc
	 * @param   bool   $elementBeforeLabel  Element before or after the label - deprecated - not used in Joomla 3
	 * @param   array  $classes             Label classes
	 * @param   bool   $buttonGroup         Should it be rendered as a bootstrap button group (radio only)
	 * @param   array  $inputDataAttributes Input data attributes e.g. array('data-foo="bar")
	 *
	 * @return  array  Grid items
	 */
	public static function gridItems($values, $labels, $selected, $name, $type = 'checkbox',
		$elementBeforeLabel = true, $classes = array(), $buttonGroup = false, $inputDataAttributes = array())
	{
		$items                            = array();
		$layout                           = self::getLayout('fabrik-grid-item');
		$displayData                      = new stdClass;
		$displayData->type                = $type;
		$displayData->name                = $name;
		$displayData->classes             = $classes;
		$displayData->inputDataAttributes = implode(' ', $inputDataAttributes);
		$displayData->selected            = $selected;
		$displayData->elementBeforeLabel  = $elementBeforeLabel;
		$displayData->buttonGroup         = $buttonGroup;

		for ($i = 0; $i < count($values); $i++)
		{
			$displayData->i     = $i;
			$displayData->label = $labels[$i];

			// For values like '1"'
			$displayData->value = htmlspecialchars($values[$i], ENT_QUOTES);
			$items[]            = $layout->render($displayData);
		}

		return $items;
	}

	/**
	 * Make a grid of items
	 *
	 * @param   array  $values              Option values
	 * @param   array  $labels              Option labels
	 * @param   array  $selected            Selected options
	 * @param   string $name                Input name
	 * @param   string $type                Checkbox/radio etc.
	 * @param   bool   $elementBeforeLabel  Element before or after the label - deprecated - not used in Joomla 3
	 * @param   int    $optionsPerRow       Number of suboptions to show per row
	 * @param   array  $classes             Array of arrays, for 'label' and 'container' classes
	 * @param   bool   $buttonGroup         Should it be rendered as a bootstrap button group (radio only)
	 * @param   array  $dataAttributes      Additional array('data-foo="bar"), like YesNo needs data-toggle="button"
	 * @param   array  $inputDataAttributes Input data attributes e.g. array('data-foo="bar")
	 *
	 * @return  string  grid
	 */
	public static function grid($values, $labels, $selected, $name, $type = 'checkbox',
		$elementBeforeLabel = true, $optionsPerRow = 4, $classes = array(), $buttonGroup = false, $dataAttributes = array(),
		$inputDataAttributes = array())
	{
		if (FabrikWorker::j3())
		{
			$elementBeforeLabel = true;
		}

		$containerClasses = array_key_exists('container', $classes) ? implode(' ', $classes['container']) : '';
		$dataAttributes   = implode(' ', $dataAttributes);

		$items = self::gridItems($values, $labels, $selected, $name, $type, $elementBeforeLabel, $classes, $buttonGroup, $inputDataAttributes);

		$grid          = array();
		$optionsPerRow = empty($optionsPerRow) ? 4 : $optionsPerRow;
		$w             = floor(100 / $optionsPerRow);

		if ($buttonGroup && $type == 'radio')
		{
			$grid[] = '<fieldset class="' . $type . ' ' . $containerClasses . ' btn-group" ' . $dataAttributes . '>';

			foreach ($items as $i => $s)
			{
				$grid[] = $s;
			}

			$grid[] = '</fieldset>';
		}
		else
		{
			if (FabrikWorker::j3())
			{
				$grid = self::bootstrapGrid($items, $optionsPerRow, 'fabrikgrid_' . $type);
			}
			else
			{
				$grid[] = '<ul>';

				foreach ($items as $i => $s)
				{
					$clear  = ($i % $optionsPerRow == 0) ? 'clear:left;' : '';
					$grid[] = '<li style="' . $clear . 'float:left;width:' . $w . '%;padding:0;margin:0;">' . $s . '</li>';
				}

				$grid[] = '</ul>';
			}
		}

		return $grid;
	}

	/**
	 * Wrap items in bootstrap grid markup
	 *
	 * @param   array  $items     Content to wrap
	 * @param   int    $columns   Number of columns in the grid
	 * @param   string $spanClass Additional class to add to cells
	 * @param   bool   $explode   Should the results be exploded to a string or returned as an array
	 *
	 * @return mixed  string/array based on $explode parameter
	 */
	public static function bootstrapGrid($items, $columns, $spanClass = '', $explode = false, $spanId = null)
	{
		$layout                 = self::getLayout('fabrik-bootstrap-grid');
		$displayData            = new stdClass;
		$displayData->items     = $items;
		$displayData->columns   = $columns;
		$displayData->spanClass = $spanClass;
		$displayData->spanId    = $spanId;
		$displayData->explode   = $explode;

		$grid = $layout->render($displayData);

		return $explode ? $grid : explode("\n", $grid);
	}

	/**
	 * Does the browser support Canvas elements
	 *
	 * @since  3.0.9
	 *
	 * @return boolean
	 */
	public static function canvasSupport()
	{
		$navigator = JBrowser::getInstance();

		return !($navigator->getBrowser() == 'msie' && $navigator->getMajor() < 9);
	}

	/**
	 * Run Joomla content plugins over text
	 *
	 * @param   string &$text Content
	 *
	 * @return  void
	 *
	 * @since   3.0.7
	 */
	public static function runContentPlugins(&$text)
	{
		$app    = JFactory::getApplication();
		$input  = $app->input;
		$opt    = $input->get('option');
		$view   = $input->get('view');
		$format = $input->get('format');
		$input->set('option', 'com_content');
		$input->set('view', 'article');
		$input->set('format', 'html');
		jimport('joomla.html.html.content');

		/**
		 * J!'s email cloaking will cloak email addresses in form inputs, which is a Bad Thing<tm>.
		 * What we really need to do is work out a way to prevent ONLY cloaking of emails in form inputs,
		 * but that's not going to be trivial.  So band-aid is to turn it off in form and list views, so
		 * addresses only get cloaked in details view.
		 * In addition, if we are in a details PDF view we should not run the email cloak plugin.
		 */

		if ($view !== 'details' || $input->get('format') === 'pdf')
		{
			$text .= '{emailcloak=off}';
		}

		$text = JHTML::_('content.prepare', $text);

		if ($view !== 'details' || $input->get('format') === 'pdf')

		{
			$text = FabrikString::rtrimword($text, '{emailcloak=off}');
		}

		$input->set('option', $opt);
		$input->set('view', $view);
		$input->set('format', $format);
	}

	/**
	 * Get content item template
	 *
	 * @param   int     $contentTemplate Joomla article id
	 * @param    string $part            which part, intro, full, or both
	 * @param   bool    $runPlugins      run content plugins on the text
	 *
	 * @since   3.0.7
	 *
	 * @return  string  content item html
	 */
	public static function getContentTemplate($contentTemplate, $part = 'both', $runPlugins = false)
	{
		$app = JFactory::getApplication();

		if ($app->isAdmin())
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('introtext, ' . $db->quoteName('fulltext'))->from('#__content')->where('id = ' . (int) $contentTemplate);
			$db->setQuery($query);
			$res = $db->loadObject();
		}
		else
		{
			JModelLegacy::addIncludePath(COM_FABRIK_BASE . 'components/com_content/models');
			$articleModel = JModelLegacy::getInstance('Article', 'ContentModel');
			$res          = $articleModel->getItem($contentTemplate);
		}

		if ($part == 'intro')
		{
			$res = $res->introtext;
		}
		else
		{
			if ($part == 'full')
			{
				$res = $res->fulltext;
			}
			else
			{
				$res = $res->introtext . ' ' . $res->fulltext;
			}
		}

		if ($runPlugins === true)
		{
			self::runContentPlugins($res);
		}

		return $res;
	}

	/**
	 * Read a template file
	 *
	 * @param   string $templateFile Path to template
	 *
	 * @return   string  template content
	 */
	public static function getTemplateFile($templateFile)
	{
		return file_get_contents($templateFile);
	}

	/**
	 * Run a PHP template as a require.  Return buffered output, or false if require returns false.
	 *
	 * @param   string $tmpl  Path to template
	 * @param   array  $data  Optional element data in standard format, for eval'd code to use
	 * @param   object $model Optional model object, depending on context, for eval'd code to use
	 *
	 * @return   mixed  email message or false
	 */
	public static function getPHPTemplate($tmpl, $data = array(), $model = null)
	{
		// Start capturing output into a buffer
		ob_start();
		$result  = require $tmpl;
		$message = ob_get_contents();
		ob_end_clean();

		if ($result === false)
		{
			return false;
		}
		else
		{
			return $message;
		}
	}

	/**
	 * Get base tag url
	 *
	 * @param   string $fullName Full name (key value to remove from querystring)
	 * @param   string $rootUrl  Optional root to use rather than REQUEST_URI
	 *
	 * @return string
	 */
	public static function tagBaseUrl($fullName, $rootUrl = null)
	{
		$url  = filter_var(ArrayHelper::getValue($_SERVER, 'REQUEST_URI', 'index.php'), FILTER_SANITIZE_URL);
		$bits = explode('?', $url);
		$root = isset($rootUrl) ? $rootUrl : FArrayHelper::getValue($bits, 0, '', 'string');
		$bits = FArrayHelper::getValue($bits, 1, '', 'string');
		$bits = explode("&", $bits);

		for ($b = count($bits) - 1; $b >= 0; $b--)
		{
			$parts = explode("=", $bits[$b]);

			if (count($parts) > 1)
			{
				$key = FabrikString::ltrimword(FabrikString::safeColNameToArrayKey($parts[0]), '&');

				if ($key == $fullName)
				{
					unset($bits[$b]);
				}

				if ($key == $fullName . '[value]')
				{
					unset($bits[$b]);
				}

				if ($key == $fullName . '[condition]')
				{
					unset($bits[$b]);
				}
			}
		}

		$url = $root . '?' . implode('&', $bits);

		return $url;
	}

	/**
	 * Tagify a string
	 *
	 * @param   array  $data    Data to tagify
	 * @param   string $baseUrl Base Href url
	 * @param   string $name    Key name for querystring
	 * @param   string $icon    HTML bootstrap icon
	 *
	 * @return  string    tagified string
	 */
	public static function tagify($data, $baseUrl = '', $name = '', $icon = '')
	{
		$url  = $baseUrl;
		$tags = array();

		if ($url == '')
		{
			$url = self::tagBaseUrl();
		}

		// Remove duplicates from tags
		$data = array_unique($data);

		foreach ($data as $key => $d)
		{
			$d = trim($d);

			if ($d != '')
			{
				if (trim($baseUrl) == '')
				{
					$qs = strstr($url, '?');

					if (substr($url, -1) === '?')
					{
						$thisUrl = $url . $name . '[value]=' . $d;
					}
					else
					{
						$thisUrl = strstr($url, '?') ? $url . '&' . $name . '[value]=' . urlencode($d) : $url . '?' . $name . '[value]=' . urlencode($d);
					}

					$thisUrl .= '&' . $name . '[condition]=CONTAINS';
					$thisUrl .= '&resetfilters=1';
				}
				else
				{
					$thisUrl = str_replace('{tag}', urlencode($d), $url);
					$thisUrl = str_replace('{key}', urlencode($key), $url);
				}

				$tags[] = '<a href="' . $thisUrl . '" class="fabrikTag">' . $icon . $d . '</a>';
			}
		}

		return $tags;
	}

	/**
	 * Return a set of attributes for an <a> tag
	 *
	 * @param   string $title Title to use for popup image
	 * @param   string $group Grouping tag for next/prev, if applicable
	 *
	 * @return string
	 */
	public static function getLightboxAttributes($title = "", $group = "")
	{
		$fbConfig       = JComponentHelper::getParams('com_fabrik');
		$lightBoxScript = $fbConfig->get('use_mediabox', '0');
		$attributes     = array();

		switch ($lightBoxScript)
		{
			case 0:
			case 1:
			default:
				$attributes[] = 'rel="lightbox[' . $group . ']"';
				break;
			case 2:
				$attributes[] = "data-rokbox";
				if (!empty($title))
				{
					$attributes[] = 'data-rockbox-caption="' . addslashes($title) . '"';
				}
				if (!empty($group))
				{
					$attributes[] = 'data-rokbox-album="' . addslashes($group) . '"';
				}
				break;
		}

		return implode(' ', $attributes);
	}

	/**
	 * Make an <a> tag
	 *
	 * @param   string $href URL
	 * @param   string $lbl  Link text
	 * @param   array  $opts Link properties key = value
	 *
	 * @since  3.1
	 *
	 * @return string  <a> tag or empty string if not $href
	 */
	public static function a($href, $lbl = '', $opts = array())
	{
		if (empty($href) || JString::strtolower($href) == 'http://' || JString::strtolower($href) == 'https://')
		{
			// Don't return empty links
			return '';
		}

		if (FabrikWorker::isEmail($href))
		{
			jimport('joomla.mail.helper');

			return JHTML::_('email.cloak', $href);
		}

		if (empty($lbl))
		{
			// If label is empty, set as a copy of the link
			$lbl = $href;
		}

		$smart_link = FArrayHelper::getValue($opts, 'smart_link', false);
		$target     = FArrayHelper::getValue($opts, 'target', false);

		if ($smart_link || $target == 'mediabox')
		{
			$smarts = self::getSmartLinkType($href);

			// Not sure that the type option is now needed.
			$opts['rel'] = 'lightbox[' . $smarts['type'] . ' ' . $smarts['width'] . ' ' . $smarts['height'] . ']';
		}

		unset($opts['smart_link']);
		$a[] = '<a href="' . $href . '"';

		foreach ($opts as $key => $value)
		{
			$a[] = ' ' . $key . '="' . trim($value) . '"';
		}

		$a[] = '>' . $lbl . '</a>';

		return implode('', $a);
	}

	/**
	 * Get an array containing info about the media link
	 *
	 * @param   string $link to examine
	 *
	 * @return  array width, height, type of link
	 */
	public static function getSmartLinkType($link)
	{
		/* $$$ hugh - not really sure how much of this is necessary, like setting different widths
		 * and heights for different social video sites. I copied the numbers from the examples page
		* for mediabox: http://iaian7.com/webcode/mediaboxAdvanced
		*/
		$ret = array('width' => '800', 'height' => '600', 'type' => 'mediabox');

		if (preg_match('#^http://([\w\.]+)/#', $link, $matches))
		{
			$site = $matches[1];
			/*
			 * @TODO should probably make this a little more intelligent, like optional www,
			* and check for site specific spoor in the URL (like '/videoplay' for google,
				* '/photos' for flicker, etc).
			*/
			switch ($site)
			{
				case 'www.flickr.com':
					$ret['width']  = '400';
					$ret['height'] = '300';
					$ret['type']   = 'social';
					break;
				case 'video.google.com':
					$ret['width']  = '640';
					$ret['height'] = '400';
					$ret['type']   = 'social';
					break;
				case 'www.metacafe.com':
					$ret['width']  = '400';
					$ret['height'] = '350';
					$ret['type']   = 'social';
					break;
				case 'vids.myspace.com':
					$ret['width']  = '430';
					$ret['height'] = '346';
					$ret['type']   = 'social';
					break;
				case 'myspacetv.com':
					$ret['width']  = '430';
					$ret['height'] = '346';
					$ret['type']   = 'social';
					break;
				case 'www.revver.com':
					$ret['width']  = '480';
					$ret['height'] = '392';
					$ret['type']   = 'social';
					break;
				case 'www.seesmic.com':
					$ret['width']  = '425';
					$ret['height'] = '353';
					$ret['type']   = 'social';
					break;
				case 'www.youtube.com':
					$ret['width']  = '480';
					$ret['height'] = '380';
					$ret['type']   = 'social';
					break;
				case 'www.veoh.com':
					$ret['width']  = '540';
					$ret['height'] = '438';
					$ret['type']   = 'social';
					break;
				case 'www.viddler.com':
					$ret['width']  = '437';
					$ret['height'] = '370';
					$ret['type']   = 'social';
					break;
				case 'vimeo.com':
					$ret['width']  = '400';
					$ret['height'] = '302';
					$ret['type']   = 'social';
					break;
				case '12seconds.tv':
					$ret['width']  = '431';
					$ret['height'] = '359';
					$ret['type']   = 'social';
					break;
			}

			if ($ret['type'] == 'mediabox')
			{
				$ext = JString::strtolower(JFile::getExt($link));

				switch ($ext)
				{
					case 'swf':
					case 'flv':
					case 'mp4':
						$ret['width']  = '640';
						$ret['height'] = '360';
						$ret['type']   = 'flash';
						break;
					case 'mp3':
						$ret['width']  = '400';
						$ret['height'] = '20';
						$ret['type']   = 'audio';
						break;
				}
			}
		}

		return $ret;
	}

	public static function formvalidation()
	{
		// Only load once
		if (isset(static::$loaded[__METHOD__]))
		{
			return;
		}

		// Add validate.js language strings
		JText::script('JLIB_FORM_FIELD_INVALID');

		// Include MooTools More framework
		static::framework('more');

		$debug   = JFactory::getConfig()->get('debug');
		$version = new JVersion;

		if ($version->RELEASE >= 3.2 && $version->DEV_LEVEL > 1)
		{
			$file = $debug ? 'punycode-uncompressed' : 'punycode';
			$path = JURI::root() . 'media/system/js/' . $file;

			$js   = array();
			$js[] = "requirejs({";
			$js[] = "   'paths': {";
			$js[] = "     'punycode': '" . $path . "'";
			$js[] = "   }";
			$js[] = " },";
			$js[] = "['punycode'], function (p) {";
			$js[] = "  window.punycode = p;";
			$js[] = "});";

			self::addToSessionHeadScripts(implode("\n", $js));
		}

		JHtml::_('script', 'system/validate.js', false, true);
		static::$loaded[__METHOD__] = true;
	}

	/**
	 * Get the element's JLayout file
	 * Its actually an instance of FabrikLayoutFile which inverses the ordering added include paths.
	 * In FabrikLayoutFile the addedPath takes precedence over the default paths, which makes more sense!
	 *
	 * @param   string $name    Layout file name (eg. fabrik-element-label)
	 * @param   array  $paths   Optional paths to add as includes
	 * @param   array  $options Layout options
	 *
	 * @return FabrikLayoutFile
	 */
	public static function getLayout($name, $paths = array(), $options = array())
	{
		$defaultOptions = array('debug' => false, 'component' => 'com_fabrik', 'client' => 'site');
		$options        = array_merge($defaultOptions, $options);
		$basePath       = COM_FABRIK_BASE . '/com_fabrik/layouts';
		$layout         = new FabrikLayoutFile($name, $basePath, $options);

		$layout->addIncludePaths(JPATH_SITE . '/layouts');
		$layout->addIncludePaths(JPATH_THEMES . '/' . JFactory::getApplication()->getTemplate() . '/html/layouts');
		$layout->addIncludePaths(JPATH_THEMES . '/' . JFactory::getApplication()->getTemplate() . '/html/layouts/com_fabrik');

		foreach ($paths as $path)
		{
			$layout->addIncludePath($path);
		}

		return $layout;
	}

	/**
	 * Render an icon using JLayouts
	 *
	 * @param   string $icon       Icon class name
	 * @param   string $label      Label
	 * @param   string $properties Additional html properties
	 *
	 * @return string
	 */
	public static function icon($icon, $label = '', $properties = '')
	{
		$icon = FabrikHelperHTML::getLayout('fabrik-icon')->render((object) array('icon' => $icon, 'properties' => $properties));

		if ($label != '')
		{
			$icon .= ' ' . $label;
		}

		return $icon;
	}

	/**
	 * Add the js jLayout objects for rendering the modal
	 *
	 * @return void
	 */
	public static function modalJLayouts()
	{
		FabrikHelperHTML::jLayoutJs('modal-close', 'modal.fabrik-close');
		FabrikHelperHTML::jLayoutJs('icon-expand', 'fabrik-icon', (object) array('icon' => 'icon-expand'));
		FabrikHelperHTML::jLayoutJs('icon-full-screen', 'fabrik-icon', (object) array('icon' => 'icon-out-2 icon-fullscreen'));
	}
}
