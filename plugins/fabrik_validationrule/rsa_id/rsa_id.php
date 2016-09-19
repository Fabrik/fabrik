<?php
/**
 * RSA Id Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.akismet
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/validation_rule.php';

/**
 * RSA Id Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.akismet
 * @since       3.3.3
 */
class PlgFabrik_ValidationruleRsa_id extends PlgFabrik_Validationrule
{
	/**
	 * Plugin name
	 *
	 * @$string
	 */
	protected $pluginName = 'rsa_id';

	/**
	 * Attach js validation code - runs in addition to the main validation code.
	 */
	public function js()
	{
		JHtml::_('jquery.framework');
		$params = $this->getParams();
		$formModel = $this->elementModel->getFormModel();
		$htmlId = $this->elementModel->getHtmlId();
		$formJsRef = $formModel->jsKey();

		$displayGenderField = $formModel->getElement($params->get('displayGender_id'), true);
		$displayDateField = $formModel->getElement($params->get('displayDate_id'), true);
		$opts = new stdClass;

		if ($displayDateField !== false)
		{
			$opts->displayDate = true;
			$opts->displayDate_id = $displayDateField->getHtmlId();
		}
		else
		{
			$opts->displayDate =false;
			$opts->displayDate_id = '';
		}

		if ($displayGenderField !== false)
		{
			$opts->displayGender = true;
			$opts->displayGender_id = $displayGenderField->getHtmlId();
		} else {
			$opts->displayGender = false;
			$opts->displayGender_id = '';
		}


		$document = JFactory::getDocument();
		$id = $this->elementModel->getHTMLId();
		$document->addScript(COM_FABRIK_LIVESITE . '/plugins/fabrik_validationrule/rsa_id/jquery.rsa_id_validator.js');
		$script = 'jQuery().ready(function($) {
     $("#' . $id . '").rsa_id_validator({
     displayDate: [' . $opts->displayDate . ', ""],
     displayDate_id : "' . $opts->displayDate_id . '",
     displayGender: [' . $opts->displayGender . ', "Male", "Female"],
     displayGender_id: "' . $opts->displayGender_id . '",
     displayCitizenship: [false],
     "displayAge": [false],
     "displayValid": [true],
     "onSuccess": function (res, display) {
        var els = Fabrik.blocks["' . $formJsRef . '"].elements;
        var c = els["' . $htmlId . '"].getContainer();
        c.removeClass("error").addClass("success");
        c.getElement(".fabrikErrorMessage").hide();
         var err = c.getElement(".fabrikErrorMessage");
        err.removeClass("help-inline");
        err.innerHTML = "";
        els["' . $opts->displayGender_id . '"].update(display[3].join(", "));
        els["' . $opts->displayDate_id . '"].update(display[1].join(", "));
        },

     "onFailure": function (res) {
     var els = Fabrik.blocks["' . $formJsRef . '"].elements;
     var el = els["' . $htmlId . '"];
      var c = el.getContainer();
      c.removeClass("success").addClass("error");
      var err = c.getElement(".fabrikErrorMessage");
      err.addClass("help-inline");
      err.empty();
      var msg = new Element("span").set("text", res[2].join(" "));
      err.adopt(el.alertImage,  msg);
      err.show()
    els["' . $opts->displayGender_id . '"].clear();
    els["' . $opts->displayDate_id . '"].clear();
      }
     });
    }); ';
		$document->addScriptDeclaration($script);
	}
	/**
	 * Validate the elements data against the rule
	 *
	 * @param   string $data          To check
	 * @param   int    $repeatCounter Repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 */
	public function validate($data, $repeatCounter)
	{
		$errors      = array();
		$currentTime = JFactory::getDate();

		/* DO ID LENGTH TEST */
		if (strlen($data) == 13)
		{
			/* SPLIT ID INTO SECTIONS */
			$year      = substr($data, 0, 2);
			$month     = substr($data, 2, 2);
			$day       = substr($data, 4, 2);
			$gender    = substr($data, 6, 4) * 1;
			$citizen   = substr($data, 10, 2) * 1;
			$check_sum = substr($data, 12, 1) * 1;

			/* DO YEAR TEST */
			$nowYearNotCentury = $currentTime->format('Y');
			$nowYearNotCentury = substr($nowYearNotCentury, 2, 2);

			$year = $year <= $nowYearNotCentury ? '20' . $year :  '19' . $year;

			if (!(($year > 1900) && ($year < $currentTime->format('Y'))))
			{
				$errors[] = FText::_('PLG_VALIDATION_RSA_YEAR_IS_NOT_VALID');
			}

			/* DO MONTH TEST */
			if (!(($month > 0) && ($month < 13)))
			{
				$errors[] = FText::_('PLG_VALIDATION_RSA_MONTH_IS_NOT_VALID');
			}

			/* DO DAY TEST */
			if (!(($day > 0) && ($day < 32)))
			{
				$errors[] = 'Day is not valid';
			}

			/* DO DATE TEST */
			if (($month == 4 || $month == 6 || $month == 9 || $month == 11) && $day == 31)
			{
				$errors[] = FText::_('PLG_VALIDATION_RSA_MONTH_NOT_31_DAYS');
			}
			if ($month == 2)
			{ // check for february 29th
				$isLeap = ($year % 4 == 0 && ($year % 100 != 0 || $year % 400 == 0));
				if ($day > 29 || ($day == 29 && !$isLeap))
				{
					$errors[] = FText::sprintf('PLG_VALIDATION_RSA_FEB_CHECK', $day, $year);
				}
			}

			/* DO GENDER TEST */
			if (!(($gender >= 0) && ($gender < 10000)))
			{
				$errors[] = FText::sprintf('PLG_VALIDATION_RSA_GENDER_NOT_VALID');
			}

			/* DO CITIZEN TEST */
			//08 or 09 SA citizen
			//18 or 19 Not SA citizen but with residence permit
			if (!(($citizen == 8) || ($citizen == 9) || ($citizen == 18) || ($citizen == 19)))
			{
				$errors[] = FText::sprintf('PLG_VALIDATION_RSA_CITIZEN_NOT_VALID');
			}

			/* GET CHECKSUM VALUE */
			$checkSumOdd      = 0;
			$checkSumEven     = 0;
			$checkSumEvenTemp = '';

			// Get ODD Value
			for ($count = 0; $count < 11; $count += 2)
			{
				$checkSumOdd += substr($data, $count, 1);
			}

			// Get EVEN Value
			for ($count = 0; $count < 12; $count += 2)
			{
				$checkSumEvenTemp = $checkSumEvenTemp . substr($data, $count + 1, 1) + '';
			}

			$checkSumEvenTemp = $checkSumEvenTemp * 2;
			$checkSumEvenTemp = $checkSumEvenTemp + '';

			for ($count = 0; $count < strlen($checkSumEvenTemp); $count++)
			{
				$checkSumEven += (substr($checkSumEvenTemp, $count, 1)) * 1;
			}

			// GET Checksum Value
			$checkSumValue = ($checkSumOdd * 1) + ($checkSumEven * 1);
			$checkSumValue = $checkSumValue . 'xxx';
			$checkSumValue = (10 - (substr($checkSumValue, 1, 1) * 1));

			if ($checkSumValue == 10)
			{
				$checkSumValue = 0;
			}

			/* DO CHECKSUM TEST */
			if ($checkSumValue !== $check_sum)
			{
				$errors[] = FText::_('PLG_VALIDATION_RSA_CHECKSUM_NOT_VALID');
			}

		}
		else
		{
			$errors[] = FText::_('PLG_VALIDATION_RSA_ID_NOT_THE_RIGHT_LENGTH');
		}

		if (!empty($errors))
		{
			$this->errorMsg = implode('<br />', $errors);
			return false;
		}

		return true;
	}

	/**
	 * Create a random string
	 *
	 * @return string
	 */

	protected function _randomSring()
	{
		return preg_replace('/([ ])/e', 'chr(rand(97,122))', '     ');
	}
}
