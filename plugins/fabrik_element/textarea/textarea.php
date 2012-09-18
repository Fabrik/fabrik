<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.textarea
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Plugin element to render text area or wysiwyg editor
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.textarea
 * @since       3.0
 */

class plgFabrik_ElementTextarea extends plgFabrik_Element
{

	/** @var  string  db table field type */
	protected $fieldDesc = 'TEXT';

	/**
	 * Tagify a string
	 *
	 * @param   string  $data  tagify
	 *
	 * @return  string	tagified string
	 */

	protected function tagify($data)
	{
		$name = $this->getFullName(false, true, false);
		$params = $this->getParams();
		$listModel = $this->getlistModel();
		$filters = $listModel->getFilterArray();
		$fkeys = JArrayHelper::getValue($filters, 'key', array());
		$data = explode(",", strip_tags($data));
		$tags = array();
		$url = $params->get('textarea_tagifyurl');
		if ($url == '')
		{
			$url = $_SERVER['REQUEST_URI'];
			$bits = explode('?', $url);
			$bits = JArrayHelper::getValue($bits, 1, '', 'string');
			$bits = explode("&", $bits);
			foreach ($bits as $bit)
			{
				$parts = explode("=", $bit);
				if (count($parts) > 1)
				{
					$key = FabrikString::ltrimword(FabrikString::safeColNameToArrayKey($parts[0]), '&');
					if ($key == $this->getFullName(false, true, false))
					{
						$url = str_replace($key . '=' . $parts[1], '', $url);
					}
				}
			}
		}
		// $$$ rob 24/02/2011 remove duplicates from tags
		$data = array_unique($data);
		$icon = FabrikHelperHTML::image('tag.png', 'form', @$this->tmpl, array('alt' => 'tag'));
		foreach ($data as $d)
		{
			$d = trim($d);
			if ($d != '')
			{
				if (trim($params->get('textarea_tagifyurl')) == '')
				{
					$qs = strstr($url, '?');
					if (substr($url, -1) === '?')
					{
						$thisurl = "$url$name=$d";
					}
					else
					{
						$thisurl = strstr($url, '?') ? "$url&$name=" . urlencode($d) : "$url?$name=" . urlencode($d);
					}
				}
				else
				{
					$thisurl = str_replace('{tag}', urlencode($d), $url);
				}
				$tags[] = '<a href="' . $thisurl . '" class="fabrikTag">' . $icon . $d . '</a>';
			}
		}
		return implode(' ', $tags);
	}

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
		$data = parent::renderListData($data, $thisRow);
		$params = $this->getParams();
		if ($params->get('textarea-tagify') == true)
		{
			$data = $this->tagify($data);
		}
		// $$$rob dont strip slashes here - this is done when saving to db now
		if (!$this->useWysiwyg())
		{
			if (is_array($data))
			{
				for ($i = 0; $i < count($data); $i++)
				{
					$data[$i] = nl2br($data[$i]);
				}
			}
			else
			{
				if (is_object($data))
				{
					$this->convertDataToString($data);
				}
				$data = nl2br($data);
			}
		}
		if (!$params->get('textarea-tagify') && $data !== '' && (int) $params->get('textarea-truncate', 0) !== 0)
		{
			$opts = array();
			$opts['wordcount'] = (int) $params->get('textarea-truncate', 0);
			$opts['tip'] = $params->get('textarea-hover');
			$opts['position'] = $params->get('textarea_hover_location', 'top');
			$data = fabrikString::truncate($data, $opts);
		}
		return $data;
	}

	/**
	 * Does the element use the WYSWYG editor
	 *
	 * @return  bool	use wysiwyg editor
	 */

	public function useEditor()
	{
		$params = $this->getParams();
		$element = $this->getElement();
		if ($params->get('use_wysiwyg', 0) && JRequest::getInt('ajax') !== 1)
		{
			return preg_replace("/[^A-Za-z0-9]/", "_", $element->name);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Determines if the element can contain data used in sending receipts,
	 * e.g. fabrikfield returns true
	 *
	 * @return  bool
	 */

	public function isReceiptElement()
	{
		return true;
	}

	/**
	 * Should the element use the WYSIWYG editor
	 *
	 * @since   3.0.6.2
	 *
	 * @return  bool
	 */

	protected function useWysiwyg()
	{
		$params = $this->getParams();
		if (JRequest::getVar('format') == 'raw')
		{
			return false;
		}
		if (JRequest::getVar('ajax') == '1') {
			return false;
		}
		return (bool) $params->get('use_wysiwyg', 0);
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
		$element = $this->getElement();
		if ($element->hidden == '1')
		{
			if( isset( $data[$name] )) {
				return $this->getHiddenField($name, $data[$name], $id);
			}
		}
		$params = $this->getParams();
		$cols = $element->width;
		$rows = $element->height;
		$value = $this->getValue($data, $repeatCounter);
		$bits = array();
		$wysiwyg = $this->useWysiwyg();
		if (!$this->_editable)
		{
			if (!$wysiwyg)
			{
				$value = nl2br($value);
			}
			if ($params->get('textarea-tagify') == true)
			{
				$value = $this->tagify($value);
			}
			return $value;
		}
		if ($params->get('textarea_placeholder', '') !== '')
		{
			$bits['placeholder'] = $params->get('textarea_placeholder');
		}
		$bits['class'] = "fabrikinput inputbox";
		if (isset($this->_elementError) && $this->_elementError != '')
		{
			$bits['class'] .= " elementErrorHighlight";
		}
		if ($wysiwyg)
		{
			if (JRequest::getVar('ajax') == 1)
			{
				// $bits['class'] .= " mce_editable";
				$str = "<textarea ";
				foreach ($bits as $key => $val)
				{
					$str .= $key . '="' . $val . '" ';
				}
				$str .= 'name="' . $name . '" id="' . $id . '" cols="' . $cols . '" rows="' . $rows . '">' . $value . '</textarea>';
			}
			else
			{
				$editor = JFactory::getEditor();
				$str = $editor->display($name, $value, $rows, $rows, $cols, $rows, true, $id);
			}
		}
		else
		{
			if ($params->get('disable'))
			{
				$bits['class'] .= " disabled";
				$bits['disabled'] = 'disabled';
			}
			if ($params->get('textarea-showmax') && $params->get('textarea_limit_type', 'char') === 'char')
			{
				$bits['maxlength'] = $params->get('textarea-maxlength');
			}

			$str = "<textarea ";
			foreach ($bits as $key => $val)
			{
				$str .= $key . '="' . $val . '" ';
			}
			$str .= "name=\"$name\" id=\"" . $id . "\" cols=\"$cols\" rows=\"$rows\">" . $value . "</textarea>\n";
		}
		if ($params->get('textarea-showmax'))
		{
			if ($params->get('textarea_limit_type', 'char') === 'char')
			{
				$label = JText::_('PLG_ELEMENT_TEXTAREA_CHARACTERS_LEFT');
				$charsLeft = $params->get('textarea-maxlength') - JString::strlen($value);
			}
			else
			{
				$label = JText::_('PLG_ELEMENT_TEXTAREA_WORDS_LEFT');
				$charsLeft = $params->get('textarea-maxlength') - count(explode(' ', $value));
			}

			$str .= '<div class="fabrik_characters_left"><span>' . $charsLeft . '</span> ' . $label . '</div>';
		}
		return $str;
	}

	/**
	 * Used to format the data when shown in the form's email
	 *
	 * @param   mixed  $value          element's data
	 * @param   array  $data           form records data
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	formatted value
	 */

	public function getEmailValue($value, $data, $repeatCounter)
	{
		$groupModel = $this->getGroup();
		if ($groupModel->isJoin() && $groupModel->canRepeat())
		{
			$value = $value[$repeatCounter];
		}
		return $this->renderListData($value, new stdClass);
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  repeat group counter
	 *
	 * @return  string
	 */

	public function elementJavascript($repeatCounter)
	{
		$params = $this->getParams();
		if ($this->useWysiwyg())
		{
			// $$$ rob need to use the NAME as the ID when wysiwyg end in joined group
			$id = $this->getHTMLName($repeatCounter);
			if ($this->_inDetailedView)
			{
				$id .= "_ro";
			}
		}
		else
		{
			$id = $this->getHTMLId($repeatCounter);
		}
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->max = $params->get('textarea-maxlength');
		$opts->maxType = $params->get('textarea_limit_type', 'char');
		$opts->wysiwyg = $this->useWysiwyg();
		$opts->deleteOverflow = $params->get('delete_overflow', true) ? true : false;
		$opts = json_encode($opts);
		return "new FbTextarea('$id', $opts)";
	}

	/**
	 * Internal element validation
	 *
	 * @param   array  $data           form data
	 * @param   int    $repeatCounter  repeeat group counter
	 *
	 * @return bool
	 */

	public function validate($data, $repeatCounter = 0)
	{
		$params = $this->getParams();
		if (!$params->get('textarea-showmax', false))
		{
			return true;
		}
		if ($params->get('delete_overflow', true))
		{
			return true;
		}
		if (JString::strlen($data) > (int) $params->get('textarea-maxlength'))
		{
			return false;
		}
		return true;
	}

	/**
	 * Get validation error - run through JText
	 *
	 * @return  string
	 */

	public function getValidationErr()
	{
		return JText::_('PLG_ELEMENT_TEXTAREA_CONTENT_TOO_LONG');
	}

	/**
	 * Get Joomfish translation type
	 *
	 * @deprecated
	 *
	 * @return  string	joomfish translation type e.g. text/textarea/referenceid/titletext
	 */

	public function getJoomfishTranslationType()
	{
		return 'textarea';
	}

	/**
	 * Get Joomfish translation options
	 *
	 * @deprecated
	 *
	 * @return  array	key=>value options
	 */

	public function getJoomfishOptions()
	{
		$params = $this->getParams();
		$return = array();
		if ($params->get('textarea-showmax'))
		{
			$return['maxlength'] = $params->get('textarea-maxlength');
		}
		return $return;
	}

	/**
	 * Can the element plugin encrypt data
	 *
	 * @return  bool
	 */

	public function canEncrypt()
	{
		return true;
	}
}
