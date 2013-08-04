<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.kaltura
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';
jimport('kaltura.kaltura_client');

/**
 * Plugin element to render a kaltura video
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.kaltura
 * @since       3.0
 */

class PlgFabrik_ElementKaltura extends PlgFabrik_Element
{

	/**
	 * Db table field type
	 *
	 * @var string
	 */
	protected $fieldDesc = 'TEXT';

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string  $data      elements data
	 * @param   object  &$thisRow  all the data in the lists current row
	 *
	 * @return  string	formatted value
	 */

	public function renderListData($data, &$thisRow)
	{
		$id = $this->getHTMLId();
		$id .= "_" . $thisRow->__pk_val;
		FabrikHelperHTML::script('media/com_fabrik/js/lib/swfobject.js');
		$params = $this->getParams();
		$partnerid = $params->get('kaltura_partnerid');
?>

<script type="text/javascript">
	var params = {
		allowscriptaccess: "always",
		allownetworking: "all",
		allowfullscreen: "true",
		wmode: "opaque"
	};

	var flashVars = {
		entryId: "<?php echo $data ?>"
	};

	swfobject.embedSWF("http://www.kaltura.com/kwidget/wid/_<?php echo $partnerid ?>", "<?php echo $id ?>", "400", "360", "9.0.0", false, flashVars, params);
</script>
		<?php
		return '<div id="' . $id . '"></div>';
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to preopulate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$return = "<input id=\"$id\" type=\"hidden\" value=\"$data\" name=\"$name\" />";
		$return .= '<div id="kcw"></div>';
		return $return;
	}

	/**
	 * Get flash var
	 *
	 * @return multitype:number string boolean NULL unknown
	 */
	private function getKalturaFlashVars()
	{
		if (!isset($this->kalturaFlashVars))
		{
			$params = $this->getParams();
			$partnerid = $params->get('kaltura_partnerid');
			$subpartnerid = $params->get('kaltura_sub_partnerid');
			$user = $this->getKalturaUser();
			$ksession = $this->getKalturaSession();

			$flashVars = array();
			$flashVars["partnerId"] = $partnerid;
			$flashVars["subpId"] = $subpartnerid;
			$flashVars["uid"] = $user->userId;
			$flashVars["ks"] = JArrayHelper::getValue($ksession["result"], "ks");
			$flashVars["kshowId"] = -2;
			$flashVars["afterAddEntry"] = "onContributionWizardAfterAddEntry";
			$flashVars["showCloseButton"] = $params->get('kaltura_show_closebutton') ? true : false;
			$this->kalturaFlashVars = $flashVars;
		}
		return $this->kalturaFlashVars;
	}

	/**
	 * Get session
	 *
	 * @return  Kaltura session
	 */

	private function getKalturaSession()
	{
		if (!isset($this->kalturaSession))
		{
			$params = $this->getParams();
			$secret = $params->get('kaltura_webservice_secret');
			$client = $this->getKalturaClient();
			$user = $this->getKalturaUser();
			$this->kalturaSession = $client->startSession($user, $secret, false);
		}
		return $this->kalturaSession;
	}

	/**
	 * Get the kaltura config
	 *
	 * @return object
	 */

	private function getKalturaConfig()
	{
		if (!isset($this->kalturaConfig))
		{
			$params = $this->getParams();
			$partnerid = $params->get('kaltura_partnerid');
			$subpartnerid = $params->get('kaltura_sub_partnerid');
			$secret = $params->get('kaltura_webservice_secret');
			$this->kalturaConfig = new KalturaConfiguration($partnerid, $subpartnerid);
		}
		return $this->kalturaConfig;
	}

	/**
	 * Get the curent kaltura user
	 *
	 * @return  object
	 */
	private function getKalturaUser()
	{
		if (!isset($this->kalturaUser))
		{
			$this->kalturaUser = new KalturaSessionUser(2);
		}
		return $this->kalturaUser;
	}

	/**
	 * Get the kaltura client
	 *
	 * @return object
	 */

	private function getKalturaClient()
	{
		if (!isset($this->kalturaClient))
		{
			$conf = $this->getKalturaConfig();
			$this->kalturaClient = new KalturaClient($conf);
		}
		return $this->kalturaClient;
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */

	public function elementJavascript($repeatCounter)
	{
		$app = JFactory::getApplication();
		$id = $this->getHTMLId($repeatCounter);
		FabrikHelperHTML::addScriptDeclaration(
			'
		function onContributionWizardAfterAddEntry(entries) {
	alert("Added " + entries.length + " entries");
	var res = [];
	for(var i = 0; i < entries.length; i++) {

		alert("entries["+i+"] = " + entries[i].entryId);
		res.push(entries[i].entryId);
	}
	$("' . $id . '").value = JSON.encode(res);
}');

		$params = $this->getParams();
		$element = $this->getElement();
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->id = $element->id;

		$uploader = new stdClass;
		$uploader->allowScriptAccess = "always";
		$uploader->allowNetworking = "all";
		$uploader->wmode = "opaque";
		$opts->uploader = $uploader;
		$opts->flash = $this->getKalturaFlashVars();
		return array('FbKaltura', $id, $opts);
	}

}
