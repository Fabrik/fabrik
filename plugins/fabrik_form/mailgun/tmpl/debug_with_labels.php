<?php
/**
 * This is a sample email template. It will just print out all of the request data:
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.email
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Alter these settings to limit what is shown in the email:

// Set this to show the raw element values.
$raw = false;

// Set this to true to show non element field values in the email e.g. "option: com_fabrik"
$info = false;


/**
 * Will attempt to get the element for the posted key
 *
 * @param   object  $formModel  Form model
 * @param   string  $key        POST key value
 *
 * @return  array(label, is the key a raw element, should we show the element)
 */
function tryForLabel($formModel, $key, $raw, $info)
{

	$elementModel = $formModel->getElement($key);
	$label = $key;
	$thisRaw = false;
	if ($elementModel)
	{
		$label = $elementModel->getElement()->label;
	}
	else
	{
		if (substr($key, -4) == '_raw')
		{
			$thisRaw = true;
			$key = substr($key, 0, strlen($key) - 4);
			$elementModel = $formModel->getElement($key);
			if ($elementModel)
			{
				$label = $elementModel->getElement()->label . ' (raw)';
			}
		}
	}
	$show = true;
	if (($thisRaw && !$raw) || (!$elementModel && !$info))
	{
		$show = false;
	}
	return array($label, $thisRaw, $show);
}
?>
<table>
<?php
foreach ($this->emailData as $key => $val)
{
	// Lets see if we can get the element name:
	list($label, $thisRaw, $show) = tryForLabel($formModel, $key, $raw, $info);

	if (!$show)
	{
		continue;
	}
	echo '<tr><td>' . $label . '</td><td>';
	if (is_array($val)) :
		foreach ($val as $v):
			if (is_array($v)) :
				echo implode("<br>", $v);
			else:
				echo implode("<br>", $val);
			endif;
		endforeach;
	else:
		echo $val;
	endif;
	echo "</td></tr>";
}
?>
</table>
