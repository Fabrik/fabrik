<?php
/**
 * User ajax example
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * @TODO - rewrite example JS with jQuery AJAX
 *
 * This is an example file.  To use userAjax, copy this file to user_ajax.php,
 * and insert your function into the userAjax class, as per the example
 * userExists() function.  To call your AJAX method, use a URL of this format from
 * your custom JS code:
 *
 * index.php?option=com_fabrik&format=raw&task=plugin.userAjax&method=userExists&username=" + myUsername;
 *
 * Fabrik will automatically try and call the function name specified in your 'method='.
 * You are responsible for grabbing any other parameters, using:
 *
 *  $app = JFactory::getApplication();
 *  $input = $app->input;
 *  $input->getString('variablename');
 *
 * as per the $myUsername example in userExists() below.
 *
 * The userExists() example is designed to test if a username given in a text element
 * exists.  If it does, an alert will pop up, then the field will be cleared and the cursor re-focused to it.
 *
 * The easiest way to call AJAX from your JS is to use the Mootools Ajax class, for instance:
 *
 * function userExists(myUsername,refocus) {
 *	 var url = "index.php?option=com_fabrik&format=raw&task=plugin.userAjax&method=userExists&username=" + myUsername;
 *	 new Request({url:url,
 *		onComplete: function(response) {
 *			if (response != '') {
 *				alert(response);
 *				refocus.value = '';
 *				refocus.focus();
 *			}
 *		}
 *	 }).send();
 *}
 *
 * In this case, the above code is called from the 'onchange' trigger
 * of a text element like this:
 *
 * var thisElement = Fabrik.getBlock('form_1').elements.get('jos_fabrik_formdata_13___username');
 * var myUsername = thisElement.get('value');
 * userExists(myUsername,thisElement);
 *
 * Note that there may be better ways of doing this, the above is just the way I found
 * to get it done.  The element JS grabs the content of the text field, and also supplies
 * the element object, so the userExists() function can then empty and refocus if the
 * specified username already exists.
 *
 * Another example of using Mootools Ajax might be something like this, which assumes a function
 * in this file called buildStateDropDown() (not shown here), which would build the dropdown
 * menu for a list of states which you want to update on the fly (for instance if you
 * have a "Country" dropdown, and wish to repopulate the State menu when it changes):
 *
 * function ajaxTest() {
 *	 var url = "index.php?option=com_fabrik&format=raw&task=plugin.userAjax&method=etStateDropDown";
 *	 new Request({url:url,
 *		method: 'get',
 *		update: document.id('jos_fabrik_formdata_13___states')
 *	 }).send();
 * }
 *
 * The important note here is the 'update' parameter, which tells Mootools the ID of the
 * form element you want to replace with the AJAX response.
 *
 */

/**
 * Define your userAjax class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class UserAjax
{
	/**
	 * This is the method that is run. You should echo out the result you which to return to the browser
	 *
	 * @return  void
	 */

	public function userExists()
	{
		$db = FabrikWorker::getDbo();
		$query = $db->getQuery(true);
		$retStr = '';
		$app = JFactory::getApplication();
		$input = $app->input;
		$myUsername = $input->get('username', '');
		$query->select('name')->from('#__users')->where('username = ' . $db->quote($myUsername));
		$db->setQuery($query, 1, 0);
		$result = $db->loadResult();

		if ($thisName = $result)
		{
			$retStr = "The username $myUsername is already in use by $thisName";
		}

		echo $retStr;
	}
}
