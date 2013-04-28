<?php
/**
 * Fabrik Component HTML Helper
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005 Pollen 8 Design Ltd. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.filesystem.file');
if (!defined('COM_FABRIK_FRONTEND'))
{
	JError::raiseError(400, JText::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'));
}
require_once COM_FABRIK_FRONTEND . '/helpers/string.php';

// Leave in as for some reason content plugin isnt loading the fabrikworker class
require_once COM_FABRIK_FRONTEND . '/helpers/parent.php';

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
	 * Is the MLC JavaScript library loaded
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
	 * Has the auto-complete JavaScript js file been loaded
	 *
	 * @var bool
	 */
	protected static $autocomplete = null;

	/**
	 * Has the Facebook API JavaScript file been loaded
	 *
	 * @var  bool
	 */
	protected static $facebookgraphapi = null;

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
	 * @var string
	 */
	protected static $emailURL = null;

	/**
	 * Form print link URL
	 * @var  string
	 */
	protected static $printURL = null;

	/**
	 * Array of browser request headers.  Starts as null.
	 * @var array
	 */
	protected static $requestHeaders = null;

	/**
	 * Load up window code - should be run in ajax loaded pages as well (10/07/2012 but not json views)
	 * might be an issue in that we may be re-observing some links when loading in - need to check
	 *
	 * @param   string  $selector  element select to auto create windows for  - was default = a.modal
	 * @param   array   $params    window parameters
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
	 *
	 * Build an array of the request headers by hand.  Replacement for using
	 * apache_request_headers(), which only works in certain configurations.
	 * This solution gets them from the $_SERVER array, and re-munges them back
	 * from HTTP_FOO_BAR format to Foo-Bar format.  Stolen from:
	 * http://stackoverflow.com/questions/541430/how-do-i-read-any-request-header-in-php
	 *
	 * @return   array  request headers assoc
	 */

	public static function parseRequestHeaders() {
		if (isset(self::$requestHeaders))
		{
			return self::$requestHeaders;
		}
		self::$requestHeaders = array();
		foreach($_SERVER as $key => $value) {
			if (substr($key, 0, 5) <> 'HTTP_') {
				continue;
			}
			$header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
			self::$requestHeaders[$header] = $value;
		}
		return self::$requestHeaders;
	}

	/**
	 * Load up window code - should be run in ajax loaded pages as well (10/07/2012 but not json views)
	 * might be an issue in that we may be re-observing some links when loading in - need to check
	 *
	 * @param   string  $selector  element select to auto create windows for  - was default = a.modal
	 * @param   array   $params    window parameters
	 *
	 * @return  void
	 */

	public static function windows($selector = '', $params = array())
	{
		$script = '';

		// Don't include in an Request.JSON call - for autofill form plugin
		// $$$ hugh - apache_request_headers() only works for certain server configurations
		//$headers = apache_request_headers();
		$headers = self::parseRequestHeaders();
		if (JArrayHelper::getValue($headers, 'X-Request') === 'JSON')
		{
			return;
		}
		if (JRequest::getVar('format') == 'json')
		{
			return;
		}
		$document = JFactory::getDocument();

		$sig = md5(serialize(array($selector, $params)));
		if (isset(self::$modals[$sig]) && (self::$modals[$sig]))
		{
			return;
		}

		$script .= "head.ready(function() {";
		if ($selector == '')
		{
			return;
		}

		// Setup options object
		$opt['ajaxOptions'] = (isset($params['ajaxOptions']) && (is_array($params['ajaxOptions']))) ? $params['ajaxOptions'] : null;
		$opt['size'] = (isset($params['size']) && (is_array($params['size']))) ? $params['size'] : null;
		$opt['onOpen'] = (isset($params['onOpen'])) ? $params['onOpen'] : null;
		$opt['onClose'] = (isset($params['onClose'])) ? $params['onClose'] : null;
		$opt['onUpdate'] = (isset($params['onUpdate'])) ? $params['onUpdate'] : null;
		$opt['onResize'] = (isset($params['onResize'])) ? $params['onResize'] : null;
		$opt['onMove'] = (isset($params['onMove'])) ? $params['onMove'] : null;
		$opt['onShow'] = (isset($params['onShow'])) ? $params['onShow'] : null;
		$opt['onHide'] = (isset($params['onHide'])) ? $params['onHide'] : null;

		$options = json_encode($opt);

		// Attach modal behavior to document
		// Set default values which can be overwritten in <a>'s rel attribute

		$opts = new stdClass;
		$opts->id = 'fabwin';
		$opts->title = JText::_('COM_FABRIK_ADVANCED_SEARCH');
		$opts->loadMethod = 'xhr';
		$opts->minimizable = false;
		$opts->collapsible = true;
		$opts->width = 500;
		$opts->height = 150;
		$opts = json_encode($opts);

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
      var t = typeOf(opts.onContentLoaded);
      if (t !== 'null') {
      opts.onContentLoaded = function() {
  			Fabrik.Windows[opts.id].fitToContent();
			};

		} else {
			opts.onContentLoaded = function() {
	  			document.id(opts.id).position();
			};
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
	 * Show form to allow users to email form to a friend
	 *
	 * @param   object  $formModel  form model
	 * @param   string  $template   template
	 *
	 * @return  void
	 */

	public static function emailForm($formModel, $template = '')
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$document = JFactory::getDocument();
		$form = $formModel->getForm();
		$document->setTitle($form->label);
		$document->addStyleSheet("templates/'. $template .'/css/template_css.css");
?>
<form method="post" action="index.php" name="frontendForm">
	<table>
		<tr>
			<td><label for="email"><?php echo JText::_('COM_FABRIK_YOUR_FRIENDS_EMAIL') ?>:</label>
			</td>
			<td><input type="text" size="25" name="email" id="email" /></td>
		</tr>
		<tr>
			<td><label for="yourname"><?php echo JText::_('COM_FABRIK_YOUR_NAME'); ?>:</label>
			</td>
			<td><input type="text" size="25" name="yourname" id="yourname" /></td>
		</tr>
		<tr>
			<td><label for="youremail"><?php echo JText::_('COM_FABRIK_YOUR_EMAIL'); ?>:</label>
			</td>
			<td><input type="text" size="25" name="youremail" id="youremail" /></td>
		</tr>
		<tr>
			<td><label for="subject"><?php echo JText::_('COM_FABRIK_MESSAGE_SUBJECT'); ?>:</label>
			</td>
			<td><input type="text" size="40" maxlength="40" name="subject"
				id="subject" /></td>
		</tr>
		<tr>
			<td colspan="2"><input type="submit" name="submit" class="button"
				value="<?php echo JText::_('COM_FABRIK_SEND_EMAIL'); ?>" />
				&nbsp;&nbsp; <input type="button" name="cancel"
				value="<?php echo JText::_('COM_FABRIK_CANCEL'); ?>" class="button"
				onclick="window.close();" /></td>
		</tr>
	</table>
	<input name="referrer"
		value="<?php echo JRequest::getVar('referrer') ?>" type="hidden" /> <input
		type="hidden" name="option" value="com_<?php echo $package; ?>" /> <input type="hidden"
		name="view" value="emailform" /> <input type="hidden" name="tmpl"
		value="component" />

	 <?php echo JHTML::_('form.token'); ?></form>
		<?php
	}

	/**
	 * Once email has been sent to a frind show this message
	 *
	 * @param   string  $to  email address
	 * @param   bool    $ok  sent ok?
	 *
	 * @return  void
	 */

	public static function emailSent($to, $ok)
	{
		$config = JFactory::getConfig();
		$document = JFactory::getDocument();
		$document->setTitle($config->get('sitename'));

		if ($ok)
		{
		?>
<span class="contentheading"><?php echo JText::_('COM_FABRIK_THIS_ITEM_HAS_BEEN_SENT_TO') . ' ' . $to; ?>
</span>
<?php
		}
?>
<br />
<br />
<br />
<a href='javascript:window.close();'> <span class="small"><?php echo JText::_('COM_FABRIK_CLOSE_WINDOW'); ?>
</span> </a>
<?php
	}

	/**
	 * Writes a print icon
	 *
	 * @param   object  $formModel  form model
	 * @param   object  $params     parameters
	 * @param   int     $rowid      row id
	 *
	 * @return  string	print html icon/link
	 */

	public static function printIcon($formModel, $params, $rowid = '')
	{
		$app = JFactory::getApplication();
		$config = JFactory::getConfig();
		$form = $formModel->getForm();
		$table = $formModel->getTable();
		$status = "status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=400,height=350,directories=no,location=no";
		$link = self::printURL($formModel);

		if ($params->get('icons', true))
		{
			$image = JHtml::_('image', 'system/printButton.png', JText::_('COM_FABRIK_PRINT'), null, true);
		}
		else
		{
			$image = '&nbsp;' . JText::_('JGLOBAL_PRINT');
		}
		if ($params->get('popup', 1))
		{
			$ahref = '<a class=\"printlink\" href="javascript:void(0)" onclick="javascript:window.print(); return false" title="'
				. JText::_('COM_FABRIK_PRINT') . '">';
		}
		else
		{
			$ahref = "<a href=\"#\" class=\"printlink\" onclick=\"window.open('$link','win2','$status;');return false;\"  title=\""
				. JText::_('COM_FABRIK_PRINT') . "\">";
		}
		$return = $ahref . $image . "</a>";
		return $return;
	}

	/**
	 * Create print URL
	 *
	 * @param   object  $formModel  form model
	 *
	 * @since   3.0.6
	 *
	 * @return  string
	 */

	public static function printURL($formModel)
	{
		if (isset(self::$printURL))
		{
			return self::$printURL;
		}
		$app = JFactory::getApplication();
		$input = $app->input;
		$form = $formModel->getForm();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$table = $formModel->getTable();

		$url = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $package . '&tmpl=component&view=details&formid=' . $form->id . '&listid=' . $table->id
		. '&rowid=' . $formModel->getRowId() . '&iframe=1&print=1';

		/* $$$ hugh - @TODO - FIXME - if they were using rowid=-1, we don't need this, as rowid has already been transmogrified
		 * to the correct (PK based) rowid.  but how to tell if original rowid was -1???
		 */
		if ($input->get('usekey') !== null)
		{
			$url .= '&usekey=' . $input->get('usekey');
		}
		$url = JRoute::_($url);

		// $$$ rob for some reason JRoute wasnt doing this ???
		$url = str_replace('&', '&amp;', $url);
		self::$printURL = $url;
		return self::$printURL;
	}

	/**
	 * Writes Email icon
	 *
	 * @param   object  $formModel  form model
	 * @param   object  $params     parameters
	 *
	 * @return  string	email icon/link html
	 */

	public static function emailIcon($formModel, $params)
	{
		$popup = $params->get('popup', 1);
		if (!$popup)
		{
			$status = "status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=400,height=250,directories=no,location=no";
			$link = self::emailURL($formModel);

			if ($params->get('icons', true))
			{
				$image = JHtml::_('image', 'system/emailButton.png', JText::_('JGLOBAL_EMAIL'), null, true);
			}
			else
			{
				$image = '&nbsp;' . JText::_('JGLOBAL_EMAIL');
			}
			return "<a href=\"#\" onclick=\"window.open('$link','win2','$status;');return false;\"  title=\"" . JText::_('JGLOBAL_EMAIL')
				. "\">$image</a>\n";
		}
	}

	/**
	 * Create URL for form email button
	 *
	 * @param   object  $formModel  form model
	 *
	 * @since 3.0.6
	 *
	 * @return  string
	 */

	public static function emailURL($formModel)
	{
		if (isset(self::$emailURL))
		{
			return self::$emailURL;
		}
		$app = JFactory::getApplication();
		$input = $app->input;
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		if ($app->isAdmin())
		{
			$url = 'index.php?option=com_fabrik&task=emailform.display&tmpl=component&formid=' . $formModel->get('id') . '&rowid='
			. $formModel->getRowId();
		}
		else
		{
			$url = 'index.php?option=com_' . $package . '&view=emailform&tmpl=component&formid=' . $formModel->get('id') . '&rowid='
			. $formModel->getRowId();
		}

		if ($input->get('usekey') !== null)
		{
			$url .= '&usekey=' . $input->get('usekey');
		}
		$url .= '&referrer=' . urlencode(JFactory::getURI()->toString());
		self::$emailURL = $url;
		return self::$emailURL;
	}

	/**
	 * Get a list of condition options - used in advanced search
	 *
	 * @param   string  $listid  list ref
	 * @param   string  $sel     selected value
	 *
	 * @return  string	html select list
	 */

	public static function conditonList($listid, $sel = '')
	{
		$conditions = array();
		$conditions[] = JHTML::_('select.option', 'AND', JText::_('COM_FABRIK_AND'));
		$conditions[] = JHTML::_('select.option', 'OR', JText::_('COM_FABRIK_OR'));
		$name = 'fabrik___filter[list_' . $listid . '][join][]';
		return JHTML::_('select.genericlist', $conditions, $name, 'class="inputbox input-mini" size="1" ', 'value', 'text', $sel);
	}

	/**
	 * Get a select list of fabrik lists
	 *
	 * @param   string  $sel  selected value
	 *
	 * @return  mixed	html select list or error
	 */

	public static function tableList($sel = '')
	{
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('id, label')->from('#__{package}_lists')->where('published = 1');
		$db->setQuery($query);
		$rows = $db->loadObjectList();
		if ($db->getErrorNum())
		{
			JError::raiseError(500, $db->getErrorMsg());
		}
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
	 * if so sends js code back to webpage to inject css file into document head
	 * If not raw format then apply standard J stylesheet
	 *
	 * @param   string  $file     stylesheet URL
	 * @param   array   $attribs  not used
	 *
	 * @return  null
	 */

	public static function stylesheet($file, $attribs = array())
	{
		// $$$ hugh - moved this to top of function, as we now apply livesite in either usage cases below.
		if (!strstr($file, COM_FABRIK_LIVESITE))
		{
			$ls = JString::substr(COM_FABRIK_LIVESITE, -1) == '/' ? COM_FABRIK_LIVESITE : COM_FABRIK_LIVESITE . '/';
			$file = $ls . $file;
		}
		if (self::cssAsAsset())
		{

			$attribs = json_encode(JArrayHelper::toObject($attribs));

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
	 * Will the CSS be loaded js Assest.css()
	 *
	 * @since   3.0.6
	 *
	 * @return  bool
	 */

	public static function cssAsAsset()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$tpl = $input->get('tmpl');
		$iframe = $input->get('iframe');
		$print = $input->get('print');
		$format = $input->get('format');
		return $input->get('format') == 'raw' || ($tpl == 'component' && $iframe != 1) && $print != 1 && $format !== 'pdf';
	}

	/**
	 * Check for a custom css file and include it if it exists
	 *
	 * @param   string  $path  NOT including JPATH_SITE (so relative too root dir) may include querystring
	 *
	 * @return	bool	if loaded or not
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
	 * @param   array   &$arr             An array of objects
	 * @param   string  $tag_name         The value of the HTML name attribute
	 * @param   string  $tag_attribs      Additional HTML attributes for the <select> tag
	 * @param   mixed   $selected         The key that is selected
	 * @param   string  $key              The name of the object variable for the option value
	 * @param   string  $text             The name of the object variable for the option text
	 * @param   int     $options_per_row  number of options to show per row @since 2.0.5
	 *
	 * @return  string	HTML for the select list
	 */

	public static function radioList(&$arr, $tag_name, $tag_attribs, $selected = null, $key = 'value', $text = 'text', $options_per_row = 0)
	{
		return self::aList('radio', $arr, $tag_name, $tag_attribs, $selected, $key, $text, $options_per_row);
	}

	/**
	 * Generates an HTML radio OR checkbox list
	 *
	 * @param   string  $type             radio/checkbox
	 * @param   array   &$arr             An array of objects
	 * @param   string  $tag_name         The value of the HTML name attribute
	 * @param   string  $tag_attribs      Additional HTML attributes for the <select> tag
	 * @param   mixed   $selected         The key that is selected
	 * @param   string  $key              The name of the object variable for the option value
	 * @param   string  $text             The name of the object variable for the option text
	 * @param   int     $options_per_row  number of options to show per row @since 2.0.5
	 * @param   bool    $editable         editable or not
	 *
	 * @return	string	HTML for the select list
	 */

	public static function aList($type, &$arr, $tag_name, $tag_attribs, $selected = null, $key = 'value', $text = 'text', $options_per_row = 0,
		$editable = true)
	{
		reset($arr);
		if ($options_per_row > 1)
		{
			$percentageWidth = floor(floatval(100) / $options_per_row) - 2;
			$div = "<div class=\"fabrik_subelement\" style=\"float:left;width:" . $percentageWidth . "%\">\n";
		}
		else
		{
			$div = '<div class="fabrik_subelement">';
		}
		$html = "";
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
			$k = $arr[$i]->$key;
			$t = $arr[$i]->$text;
			$id = isset($arr[$i]->id) ? @$arr[$i]->id : null;

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
			}
			$html .= $div;

			if ($editable)
			{
				$tmpName = $type === 'checkbox' ? $tag_name . '[' . $i . ']' : $tag_name;
				$html .= '<label>';
				$html .= '<input type="' . $type . '" value="' . $k . '" name="' . $tmpName . '" class="fabrikinput" ' . $extra . '/>';
			}
			if ($editable || $found)
			{
				$html .= '<span>' . $t . '</span>';
			}
			if ($editable)
			{
				$html .= '</label>';
			}
			$html .= '</div>';
		}
		$html .= "\n";
		return $html;
	}

	/**
	 * Keep session alive, for example, while editing or creating an article.
	 *
	 * @return  void
	 */

	public static function keepalive()
	{
		// Test since 2.0b3 dont do anything if loading from Fabrik win
		if (self::inAjaxLoadedPage())
		{
			return;
		}
		JHtml::_('behavior.keepalive');
	}

	/**
	 * Load the MCL canvas layer library
	 *
	 * @return  void
	 */

	public static function mcl()
	{
		if (!self::$mcl)
		{
			$src = array('media/com_fabrik/js/lib/mcl/CANVAS.js', 'media/com_fabrik/js/lib/mcl/CanvasItem.js',
				'media/com_fabrik/js/lib/mcl/Cmorph.js', 'media/com_fabrik/js/lib/mcl/Layer.js', 'media/com_fabrik/js/lib/mcl/LayerHash.js',
				'media/com_fabrik/js/lib/mcl/Thread.js', 'media/com_fabrik/js/lib/canvas-extra.js');
			self::script($src);
			self::$mcl = true;
		}
	}

	/**
	 * Load Fabrik's framework (js and base css file)
	 *
	 * @return  array  framework js files
	 */

	public static function framework()
	{
		if (!self::$framework)
		{
			$src = array();
			if (self::inAjaxLoadedPage())
			{
				// 17/10/2011 (firefox) retesting loading this in ajax page as without it Class is not available? so form class doesnt load
				JHtml::_('behavior.framework', true);

				// $$$ rob 06/02/2012 recall ant so that Color.detach is available (needed for opening a window from within a window)
				JHtml::_('script', 'media/com_fabrik/js/lib/art.js');
				JHtml::_('script', 'media/com_fabrik/js/lib/Event.mock.js');
			}

			if (!self::inAjaxLoadedPage())
			{

				/*
				 * required so that any ajax loaded form can make use of it later on (otherwise stops js from working)
				 * only load in main/first window - otherwise reloading it causes js errors related to calendar translations
				 */
				JHTML::_('behavior.calendar');

				/*loading framework, if in ajax loaded page:
				 * makes document.body not found for gmap element when
				 * removes previously added window.events (17/10/2011 we're now using Fabrik.events - so this may no longer be an issue)
				 */
				JHtml::_('behavior.framework', true);

				JDEBUG ? JHtml::_('script', 'media/com_fabrik/js/lib/head/head.js') : JHtml::_('script', 'media/com_fabrik/js/lib/head/head.min.js');

				JText::script('COM_FABRIK_LOADING');
				$navigator = JBrowser::getInstance();
				$config = JComponentHelper::getParams('com_fabrik');

				if ($navigator->getBrowser() == 'msie' && $config->get('flexie', true))
				{
					$src[] = 'media/com_fabrik/js/lib/flexiejs/flexie.js';
				}
				$src[] = 'media/com_fabrik/js/mootools-ext.js';
				$src[] = 'media/com_fabrik/js/lib/art.js';
				$src[] = 'media/com_fabrik/js/icons.js';
				$src[] = 'media/com_fabrik/js/icongen.js';
				$src[] = 'media/com_fabrik/js/fabrik.js';
				$src[] = 'media/com_fabrik/js/encoder.js';
				$src[] = 'media/com_fabrik/js/tips.js';
				$src[] = 'media/com_fabrik/js/window.js';
				$src[] = 'media/com_fabrik/js/lib/Event.mock.js';

				self::styleSheet(COM_FABRIK_LIVESITE . 'media/com_fabrik/css/fabrik.css');
				$script = array();
				$script[] = "Fabrik.fireEvent('fabrik.framework.loaded');";

				$tipOpts = self::tipOpts();
				self::addScriptDeclaration(
					"head.ready(function () {
	Fabrik.liveSite = '" . COM_FABRIK_LIVESITE . "';
	Fabrik.tips = new FloatingTips('.fabrikTip', " . json_encode($tipOpts)
						. ");
	Fabrik.addEvent('fabrik.list.updaterows', function () {
		//reattach new tips after list redraw,
		Fabrik.tips.attach('.fabrikTip');
	});
	Fabrik.addEvent('fabrik.plugin.inlineedit.editing', function () {
		Fabrik.tips.hideAll();
	});
});
				");
			}
			self::$framework = $src;
		}
		return self::$framework;
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
	 * Get tip options to control its fx - set in Fabrik global configuration
	 *
	 * @return stdClass
	 */

	public static function tipOpts()
	{
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$opts = new stdClass;
		$opts->tipfx = 'Fx.Transitions.' . $usersConfig->get('tipfx', 'Linear');
		if ($usersConfig->get('tipfx', 'Linear') !== 'Linear')
		{
			$opts->tipfx .= '.' . $usersConfig->get('tipfx_ease', 'easeIn');
		}
		$opts->duration = $usersConfig->get('tipfx_duration', '500');
		$opts->distance = (int) $usersConfig->get('tipfx_distance', '20');
		$opts->fadein = (bool) $usersConfig->get('tipfx_fadein', false);
		return $opts;
	}

	/**
	 * Add a script declaration, either to the head or inline if format=raw
	 *
	 * @param   string  $script  js code to add
	 *
	 * @return  null
	 */

	public static function addScriptDeclaration($script)
	{
		$app = JFactory::getApplication();
		if ($app->input->get('format') == 'raw')
		{
			echo '<script type="text/javascript">' . $script . '</script>';
		}
		else
		{
			JFactory::getDocument()->addScriptDeclaration($script);
		}
	}

	/**
	 * Add a CSS style declaration, either to the head or iinline if format=raw
	 *
	 * @param   string  $style  CSS
	 *
	 * @return  void
	 */

	public static function addStyleDeclaration($style)
	{
		$app = JFactory::getApplication();
		if ($app->input->get('format') == 'raw')
		{
			echo '<style type="text/css">' . $style . '</script>';
		}
		else
		{
			JFactory::getDocument()->addStyleDeclaration($style);
		}
	}

	/**
	 * Dometimes you want to load a page in an iframe and want to use tmpl=component - in this case
	 * append iframe=1 to the url to ensure that we dont try to add the scripts via FBAsset()
	 *
	 * @return  bool
	 */

	public static function inAjaxLoadedPage()
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');

		// Are we in fabrik or a content view, if not return false (things like com_config need to load in mootools)
		$app = JFactory::getApplication();
		$input = $app->input;
		$option = $input->get('option');
		if ($option !== 'com_' . $package && $option !== 'com_content')
		{
			return false;
		}
		if (class_exists('JSite'))
		{
			$app = JFactory::getApplication();
			$menus = $app->getMenu();
			$menu = $menus->getActive();
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
	 * @param   bool  $enabled  set to true if Fabrik debug global option must be set to true
	 *
	 * @return  bool
	 */

	public static function isDebug($enabled = false)
	{
		$app = JFactory::getApplication();
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
		$debug = (int) $config->get('debug');
		return $debug === 1 || $app->input->get('fabrikdebug', 0) == 1;
	}

	/**
	 * Wrapper for JHTML::Script()
	 *
	 * @param   mixed   $file    string or array of files to load
	 * @param   string  $onLoad  optional js to run if format=raw (as we first load the $file via Asset.Javascript()
	 *
	 * @return  void
	 */

	public static function script($file, $onLoad = '')
	{
		if (empty($file))
		{
			return;
		}
		if (is_array($onLoad))
		{
			$onLoad = implode("\n", $onLoad);
		}
		$document = JFactory::getDocument();
		/*
		$config = JFactory::getConfig();
		$debug = $config->get('debug');
		$ext = $debug || (int) JRequest::getInt('fabrikdebug', 0) === 1 ? '.js' : '-min.js';
		*/
		$ext = self::isDebug() ? '.js' : '-min.js';

		$file = (array) $file;
		$src = array();
		foreach ($file as $f)
		{
			if (!(JString::stristr($f, 'http://') || JString::stristr($f, 'https://')))
			{
				if (!JFile::exists(COM_FABRIK_BASE . '/' . $f))
				{
					continue;
				}
			}
			if (JString::stristr($f, 'http://') || JString::stristr($f, 'https://'))
			{
				$f = $f;
			}
			else
			{
				$compressedFile = str_replace('.js', $ext, $f);
				if (JFile::exists($compressedFile))
				{
					$f = $compressedFile;
				}
				$f = COM_FABRIK_LIVESITE . $f;
			}

			/*
			 * Check if already loaded? Have to do this as multiple head.js calls with identical
			 * scripts in them stops the $onLoad method from being run see:
			 * https://github.com/Fabrik/fabrik/issues/412
			 */
			if (in_array($f, self::$scripts) && JRequest::getCmd('format') !== 'raw')
			{
				continue;
			}
			else
			{
				self::$scripts[] = $f;
			}

			if (JRequest::getCmd('format') == 'raw')
			{
				$opts = trim($onLoad) !== '' ? '\'onLoad\':function(){' . $onLoad . '}' : '';
				echo '<script type="text/javascript">Asset.javascript(\'' . $f . '\', {' . $opts . '});</script>';
			}
			else
			{
				$src[] = "'" . $f . "'";
			}
		}
		if ($onLoad !== '' && JRequest::getCmd('format') != 'raw' && !empty($src))
		{
			if (self::inAjaxLoadedPage())
			{
				$onLoad = "(function() {\n " . $onLoad . "\n //end load func \n})";
			}
			else
			{
				$onLoad = "(function() { head.ready(function() {\n" . $onLoad . "\n})\n})";
			}
			$src[] = $onLoad;
		}
		if (!empty($src))
		{
			$document->addScriptDeclaration('head.js(' . implode(",\n", array_unique($src)) . ');' . "\n");
		}
		else
		{
			// Ppreviously loaded $file but with js code in $onLoad which should still be added
			if (!empty($onLoad))
			{
				$document->addScriptDeclaration('head.ready(function () {' . $onLoad . '});' . "\n");
			}
		}
	}

	/**
	 * Load the slimbox / media box css and js files
	 *
	 * @return  void
	 */

	public static function slimbox()
	{
		if (!self::$modal)
		{
			$fbConfig = JComponentHelper::getParams('com_fabrik');
			if ($fbConfig->get('include_lightbox_js', 1) == 0)
			{
				return;
			}
			if ($fbConfig->get('use_mediabox', false))
			{
				$folder = 'components/com_fabrik/libs/mediabox/';
				JHTML::stylesheet($folder . '/css/mediabox.css');
				self::script($folder . 'mediabox.js');
			}
			else
			{
				JHTML::stylesheet('components/com_fabrik/libs/slimbox1.64/css/slimbox.css');
				self::script('components/com_fabrik/libs/slimbox1.64/js/slimbox.js');
			}
			self::$modal = true;
		}
	}

	/**
	 * Attach tooltips to document
	 *
	 * @param   string  $selector        string class name of tips
	 * @param   array   $params          array paramters
	 * @param   string  $selectorPrefix  limit the tips selection to those contained within an id
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
		$opt['offsets'] = (isset($params['offsets'])) ? (int) $params['offsets'] : null;
		$opt['showDelay'] = (isset($params['showDelay'])) ? (int) $params['showDelay'] : null;
		$opt['hideDelay'] = (isset($params['hideDelay'])) ? (int) $params['hideDelay'] : null;
		$opt['className'] = (isset($params['className'])) ? $params['className'] : null;
		$opt['fixed'] = (isset($params['fixed']) && ($params['fixed'])) ? '\\true' : '\\false';
		$opt['onShow'] = (isset($params['onShow'])) ? '\\' . $params['onShow'] : null;
		$opt['onHide'] = (isset($params['onHide'])) ? '\\' . $params['onHide'] : null;

		$options = json_encode($opt);

		// Attach tooltips to document
		// Force the zindex to 9999 so that it appears above the popup window.
		$tooltipInit = 'head.ready(function() {if(typeOf(' . $selectorPrefix . ') !== \'null\' && ' . $selectorPrefix . '.getElements(\'' . $selector
			. '\').length !== 0) {window.JTooltips = new Tips(' . $selectorPrefix . '.getElements(\'' . $selector . '\'), ' . $options
			. ');$$(".tool-tip").setStyle("z-index", 999999);}});';
		/* self::addScriptDeclaration($tooltipInit); */

		self::$tips[$sig] = true;
	}

	/**
	 * Add a debug out put section
	 *
	 * @param   mixed   $content  string/object
	 * @param   string  $title    debug title
	 *
	 * @return  void
	 */

	public static function debug($content, $title = 'output:')
	{
		$config = JComponentHelper::getParams('com_fabrik');
		$app = JFactory::getApplication();
		$input = $app->input;
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
			echo htmlspecialchars($content);
		}
		echo '</div>';

		if (!isset(self::$debug))
		{
			self::$debug = true;
			$document = JFactory::getDocument();
			$style = ".fabrikDebugOutputTitle{padding:5px;background:#efefef;color:#333;border:1px solid #999;cursor:pointer}";
			$style .= ".fabrikDebugOutput{padding:5px;background:#efefef;color:#999;}";
			$style .= ".fabrikDebugOutput pre{padding:5px;background:#efefef;color:#999;}";
			$style .= ".fabrikDebugHidden{display:none}";
			self::addStyleDeclaration($style);
			$script = "head.ready(function() {
			$$('.fabrikDebugOutputTitle').each(function(title) {
				title.addEvent('click', function(e) {
					title.getNext().toggleClass('fabrikDebugHidden');
				});
			});
			})";
			self::addScriptDeclaration($script);
		}
	}

	/**
	 * Create html for ajax folder browser (used by fileupload and image elements)
	 *
	 * @param   array   $folders  array of folders to show
	 * @param   string  $path     start path
	 * @param   string  $tpl      view template
	 *
	 * @return  string	html snippet
	 */

	public static function folderAjaxSelect($folders, $path = '', $tpl = '')
	{
		$str = array();
		$str[] = '<a href="#" class="toggle" title="' . JText::_('COM_FABRIK_BROWSE_FOLDERS') . '">';
		$str[] = self::image('orderneutral.png', 'form', $tpl, array('alt' => JText::_('COM_FABRIK_BROWSE_FOLDERS')));
		$str[] = '</a>';
		$str[] = '<div class="folderselect-container">';
		$str[] = '<span class="breadcrumbs"><a href="#">' . JText::_('HOME') . '</a><span> / </span>';
		$i = 1;
		$path = explode("/", $path);
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
	 * Add autocomplete JS code to head
	 *
	 * @param   string  $htmlid     of element to turn into autocomplete
	 * @param   int     $elementid  element id
	 * @param   string  $plugin     plugin name
	 * @param   array   $opts       (currently only takes 'onSelection')
	 *
	 * @return  void
	 */

	public static function autoComplete($htmlid, $elementid, $plugin = 'field', $opts = array())
	{
		self::autoCompleteScript();
		$json = self::autoCompletOptions($htmlid, $elementid, $plugin, $opts);
		$str = json_encode($json);
		$class = $plugin === 'cascadingdropdown' ? 'FabCddAutocomplete' : 'FbAutocomplete';
		self::addScriptDeclaration("head.ready(function() { new $class('$htmlid', $str); });");
	}

	/**
	 * Gets auto complete js options (needed separate from autoComplete as db js class needs these values for repeat group duplication)
	 *
	 * @param   string  $htmlid     element to turn into autocomplete
	 * @param   int     $elementid  element id
	 * @param   string  $plugin     plugin type
	 * @param   array   $opts       (currently only takes 'onSelection')
	 *
	 * @return  array	autocomplete options (needed for elements so when duplicated we can create a new FabAutocomplete object
	 */

	public static function autoCompletOptions($htmlid, $elementid, $plugin = 'field', $opts = array())
	{
		$json = new stdClass;
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$json->url = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $package . '&format=raw&view=plugin&task=pluginAjax&g=element&element_id=' . $elementid
			. '&plugin=' . $plugin . '&method=autocomplete_options&package=' . $package;
		$c = JArrayHelper::getValue($opts, 'onSelection');
		if ($c != '')
		{
			$json->onSelections = $c;
		}
		foreach ($opts as $k => $v)
		{
			$json->$k = $v;
		}
		$json->container = JArrayHelper::getValue($opts, 'container', 'fabrikElementContainer');
		$json->menuclass = JArrayHelper::getValue($opts, 'menuclass', 'auto-complete-container');
		return $json;
	}

	/**
	 * Load the autocomplete script once
	 *
	 * @return  void
	 */

	public static function autoCompleteScript()
	{
		if (!isset(self::$autocomplete))
		{
			self::$autocomplete = true;
			self::script('media/com_fabrik/js/autocomplete.js');
		}
	}

	/**
	 * Load the Facebook Graph API
	 *
	 * @param   string  $appid   Application id
	 * @param   string  $locale  locale e.g 'en_US'
	 * @param   array   $meta    meta tags to add
	 *
	 * @return  void
	 */

	public static function facebookGraphAPI($appid, $locale = 'en_US', $meta = array())
	{
		if (!isset(self::$facebookgraphapi))
		{
			self::$facebookgraphapi = true;
			return "<div id=\"fb-root\"></div>
<script>
  window.fbAsyncInit = function() {
    FB.init({appId: '$appid', status: true, cookie: true,
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
		$document = JFactory::getDocument();
		$data = array('custom' => array());
		$typeFound = false;
		foreach ($meta as $k => $v)
		{
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
	 * @param   string  $path          to add to list of folders to search
	 * @param   string  $type          of path set to load (currently only image is used)
	 * @param   string  $view          are we looking at loading form or list images?
	 * @param   bool    $highPriority  should the added $path take precedence over previously added paths (default true)
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
			$app = JFactory::getApplication();
			$template = $app->getTemplate();
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
	 * @param   string  $file  file name
	 * @param   string  $type  type e.g. form/list/element
	 * @param   string  $tmpl  template folder name
	 *
	 * @return  string	full path name if found, original filename if not found
	 */

	public static function getImagePath($file, $type = 'form', $tmpl = '')
	{
		$file = JString::ltrim($file, DIRECTORY_SEPARATOR);
		$paths = self::addPath('', 'image', $type, true);
		$src = '';
		foreach ($paths as $path)
		{
			$path = sprintf($path, $tmpl);
			$src = $path . $file;
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
	 * @param   string  $file        file name
	 * @param   string  $type        type e.g. form/list/element
	 * @param   string  $tmpl        template folder name
	 * @param   array   $properties  assoc list of properties or string (if you just want to set the image alt tag)
	 * @param   bool    $srcOnly     src only (default false)
	 *
	 * @since 3.0
	 *
	 * @return  string  image
	 */

	public static function image($file, $type = 'form', $tmpl = '', $properties = array(), $srcOnly = false)
	{
		if (is_string($properties))
		{
			$properties = array('alt' => $properties);
		}

		$app = JFactory::getApplication();
		$version = new JVersion;

		// Only use template test for testing in 2.5 with my temp J bootstrap template.
		if ($app->getTemplate() === 'bootstrap' || $version->RELEASE > 2.5)
		{
			return '<i class="icon-' . JFile::stripExt($file) . '"></i>';
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

		$bits = array();
		foreach ($properties as $key => $val)
		{
			if ($key === 'title')
			{
				$val = htmlspecialchars($val, ENT_QUOTES);
			}
			$bits[$key] = $val;
		}
		$p = '';
		foreach ($bits as $key => $val)
		{
			$val = str_replace('"', "'", $val);
			$p .= $key . '="' . $val . '" ';
		}
		return $src == '' ? '' : '<img src="' . $src . '" ' . $p . '/>';
	}

	/**
	 * Make a grid of items
	 *
	 * @param   array   $values              Option values
	 * @param   array   $labels              Option labels
	 * @param   array   $selected            Selected options
	 * @param   string  $name                Input name
	 * @param   string  $type                Checkbox/radio etc
	 * @param   bool    $elementBeforeLabel  Element before or after the label - deprecated - not used in Joomla 3
	 * @param   int     $optionsPerRow       Number of suboptions to show per row
	 * @param   array   $classes             Label classes
	 * @param   bool    $buttonGroup         Should it be rendered as a bootstrap button group (radio only)
	 *
	 * @return  string  grid
	 */

	public static function grid($values, $labels, $selected, $name, $type = "checkbox", $elementBeforeLabel = true, $optionsPerRow = 4, $classes = array(), $buttonGroup = false)
	{
		$items = array();

		if (FabrikWorker::j3())
		{
			$elementBeforeLabel = true;
		}
		for ($i = 0; $i < count($values); $i++)
		{
			$item = array();
			$thisname = $type === 'checkbox' ? FabrikString::rtrimword($name, '[]') . '[' . $i . ']' : $name;
			$label = FabrikWorker::j3() ? $labels[$i] : '<span>' . $labels[$i] . '</span>';

			// For values like '1"'
			$value = htmlspecialchars($values[$i], ENT_QUOTES);
			$inputClass = FabrikWorker::j3() ? '' : $type;
			if (array_key_exists('input', $classes))
			{
				$inputClass .= ' ' . implode(' ', $classes['input']);
			}
			$chx = '<input type="' . $type . '" class="fabrikinput ' . $inputClass . '" name="' . $thisname . '" value="' . $value . '" ';
			$sel = in_array($values[$i], $selected);
			$chx .= $sel ? ' checked="checked" />' : ' />';
			$labelClass = FabrikWorker::j3() && !$buttonGroup ? $type : '';
			$item[] = '<label class="fabrikgrid_' . $value .  ' ' . $labelClass . '">';
			$item[] = $elementBeforeLabel == '1' ? $chx . $label : $label . $chx;
			$item[] = '</label>';
			$items[] = implode("\n", $item);
		}
		$grid = array();

		$optionsPerRow = empty($optionsPerRow) ? 4 : $optionsPerRow;
		$w = floor(100 / $optionsPerRow);
		$widthConstraint = '';
		if ($buttonGroup && $type == 'radio')
		{
			$grid[] = '<fieldset class="' . $type . ' btn-group">';
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
				$span = floor(12 / $optionsPerRow);
				foreach ($items as $i => $s)
				{
					$endLine = ($i !== 0 && (($i ) % $optionsPerRow == 0));
					if ($endLine && $optionsPerRow > 1)
					{
						$grid[] = '</div><!-- grid close row -->';
					}

					$newLine = ($i % $optionsPerRow == 0);
					if ($newLine && $optionsPerRow > 1)
					{
						$grid[] = '<div class="row-fluid">';
					}

					$grid[] =  $optionsPerRow != 1 ? '<div class="span' . $span . '">' . $s . '</div>' : $s;
				}
				if ($i + 1 % $optionsPerRow !== 0 && $optionsPerRow > 1)
				{
					// Close opened and unfinished row.
					$grid[] = '</div><!-- grid close end row -->';
				}
			}
			else
			{
				$grid[] = '<ul>';
				foreach ($items as $i => $s)
				{
					$clear = ($i % $optionsPerRow == 0) ? 'clear:left;' : '';
					$grid[] = '<li style="' . $clear . 'float:left;width:' . $w . '%;padding:0;margin:0;">' . $s . '</li>';
				}
				$grid[] = '</ul>';
			}
		}
		return $grid;
	}

	/**
	 * Run Joomla content plugins over text
	 *
	 * @param   string  &$text  Content
	 *
	 * @return  void
	 *
	 * @since   3.0.7
	 */

	public static function runConentPlugins(&$text)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$opt = $input->get('option');
		$view = $input->get('view');
		$input->set('option', 'com_content');
		$input->set('view', 'article');
		jimport('joomla.html.html.content');
		$text .= '{emailcloak=off}';
		$text = JHTML::_('content.prepare', $text);
		$text = preg_replace('/\{emailcloak\=off\}/', '', $text);
		$input->set('option', $opt);
		$input->set('view', $view);
	}

	/**
	* get content item template
	*
	* @since   3.0.7
	*
	* @param   int $contentTemplate
	*
	* @return  string  content item html
	*/

	public function getContentTemplate($contentTemplate)
	{
		$app = JFactory::getApplication();
		if ($app->isAdmin())
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('introtext, ' . $db->quoteName('fulltext'))->from('#__content')->where('id = ' . (int) $contentTemplate);
			$db->setQuery($query);
			$res = $db->loadObject();
		}
		else
		{
			JModel::addIncludePath(COM_FABRIK_BASE . 'components/com_content/models');
			$articleModel = JModel::getInstance('Article', 'ContentModel');
			$res = $articleModel->getItem($contentTemplate);
		}
		return $res->introtext . ' ' . $res->fulltext;
	}

	/**
	* read a template file
	*
	* @param   string  path to template
	*
	* @return   string  template content
	*/

	function getTemplateFile($templateFile)
	{
		jimport('joomla.filesystem.file');
		return JFile::read($templateFile);
	}


	/**
	* Run a PHP tmeplate as a require.  Return buffered output, or false if require returns false.
	*
	* @param   string  path to template
	*
	* @param   array  optional element data in standard format, for eval'ed code to use
	*
	* @param   object  optional model object, depending on context, for eval'ed code to use
	*
	* @return   mixed  email message or false
	*/

	public function getPHPTemplate($tmpl, $data = array(), $model = null)
	{
		// start capturing output into a buffer
		ob_start();
		$result = require $tmpl;
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
}
