<?php
/**
 * Plugin element to render text area or wysiwyg editor
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.textarea
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

/**
 * Plugin element to render text area or wysiwyg editor
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.textarea
 * @since       3.0
 */
class PlgFabrik_ElementTextarea extends PlgFabrik_Element
{
	/**
	 * Db table field type
	 *
	 * @var string
	 */
	protected $fieldDesc = 'TEXT';

	/**
	 * Tagify a string
	 *
	 * @param   string  $data  Tagify
	 *
	 * @return  string	Tagified string
	 */
	protected function tagify($data)
	{
		$name = $this->getFullName(true, false);
		$params = $this->getParams();
		$data = explode(',', strip_tags($data));
		$url = $params->get('textarea_tagifyurl');
		$listId = $this->getListModel()->getId();

		if ($url == '')
		{
			if ($this->app->isAdmin())
			{
				$url = 'index.php?option=com_fabrik&amp;task=list.view&amp;listid=' . $listId;
			}
			else
			{
				$url = 'index.php?option=com_' . $this->package . '&view=list&listid=' . $listId;
			}
		}

		// $$$ rob 24/02/2011 remove duplicates from tags
		// $$$ hugh - strip spaces first, account for "foo,bar, baz, foo"
		$data = array_map('trim', $data);
		$data = array_unique($data);
		$img = FabrikWorker::j3() ? 'bookmark.png' : 'tag.png';
		$icon = FabrikHelperHTML::image($img, 'form', @$this->tmpl, array('alt' => 'tag'));
		$tmplData = new stdClass;
		$tmplData->tags = array();

		foreach ($data as $d)
		{
			$d = trim($d);

			if ($d != '')
			{
				if (trim($params->get('textarea_tagifyurl')) == '')
				{
					if (substr($url, -1) === '?')
					{
						$thisurl = $url . $name . '[value]=' . $d;
					}
					else
					{
						$thisurl = strstr($url, '?') ? $url . '&' . $name . '[value]=' . urlencode($d) : $url . '?' . $name . '[value]=' . urlencode($d);
					}

					$thisurl .= '&' . $name . '[condition]=CONTAINS';
					$thisurl .= '&resetfilters=1';
				}
				else
				{
					$thisurl = str_replace('{tag}', urlencode($d), $url);
				}

				$o = new stdClass;
				$o->url = $thisurl;
				$o->icon = $icon;
				$o->label = $d;
				$tmplData->tags[] = $o;
			}
		}

		$layout = $this->getLayout('tags');

		return $layout->render($tmplData);
	}

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string    $data      Elements data
	 * @param   stdClass  &$thisRow  All the data in the lists current row
	 * @param   array     $opts      Rendering options
	 *
	 * @return  string	formatted value
	 */
	public function renderListData($data, stdClass &$thisRow, $opts = array())
	{
		$data = parent::renderListData($data, $thisRow, $opts);
		$params = $this->getParams();

		if ($params->get('textarea-tagify') == true)
		{
			$data = $this->tagify($data);
		}
		else
		{
			if (!$this->useWysiwyg(false))
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

			$truncateWhere = (int) $params->get('textarea-truncate-where', 0);

			if ($data !== '' && ($truncateWhere === 1 || $truncateWhere === 3))
			{
				$opts = $this->truncateOpts();
				$data = fabrikString::truncate($data, $opts);
				$listModel = $this->getListModel();

				if (ArrayHelper::getValue($opts, 'link', 1))
				{
					$data = $listModel->_addLink($data, $this, $thisRow);
				}
			}
		}

		return $data;
	}

	/**
	 * Get the truncate text options. Can be used for both list and details views.
	 *
	 * @return array
	 */
	private function truncateOpts()
	{
		$opts = array();
		$params = $this->getParams();
		$opts['html_format'] = $params->get('textarea-truncate-html', '0') === '1';
		$opts['wordcount'] = (int) $params->get('textarea-truncate', 0);
		$opts['tip'] = $params->get('textarea-hover');
		$opts['position'] = $params->get('textarea_hover_location', 'top');

		return $opts;
	}

	/**
	 * Get the element's HTML label
	 *
	 * @param   int     $repeatCounter  Group repeat counter
	 * @param   string  $tmpl           Form template
	 *
	 * @return  string  label
	 */
	public function getLabel($repeatCounter = 0, $tmpl = '')
	{
		$params = $this->getParams();
		$element = $this->getElement();

		if ($params->get('textarea_showlabel') == '0')
		{
			$element->label = '';
		}

		return parent::getLabel($repeatCounter, $tmpl);
	}

	/**
	 * Does the element use the WYSIWYG editor
	 *
	 * @return  mixed	False if not using the wysiwyg editor. String (element name) if it is
	 */
	public function useEditor()
	{
		$element = $this->getElement();

		if ($this->useWysiwyg())
		{
			return preg_replace("/[^A-Za-z0-9]/", "_", $element->name);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Should the element use the WYSIWYG editor
	 *
	 * @bool  checkFormat  check the formats (ajax, format=raw), or only check param setting
	 *
	 * @since   3.0.6.2
	 *
	 * @return  bool
	 */
	protected function useWysiwyg($checkFormat = true)
	{
		$params = $this->getParams();
		$input = $this->app->input;

		if ($checkFormat && $input->get('format') == 'raw')
		{
			return false;
		}

		if ($checkFormat && $input->get('ajax') == '1')
		{
			return false;
		}

		return (bool) $params->get('use_wysiwyg', 0);
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           To pre-populate element with
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  string	Elements html
	 */
	public function render($data, $repeatCounter = 0)
	{
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$element = $this->getElement();

		if ($element->hidden == '1')
		{
			return $this->getHiddenField($name, $this->getValue($data, $repeatCounter), $id);
		}

		$params = $this->getParams();
		$cols = $params->get('width', $element->width);
		$rows = $params->get('height', $element->height);
		$value = $this->getValue($data, $repeatCounter);
		$bits = array();
		$bits['class'] = "fabrikinput inputbox " . $params->get('bootstrap_class');
		$wysiwyg = $this->useWysiwyg();

		if (!$this->isEditable())
		{
			if ($params->get('textarea-tagify') == true)
			{
				$value = $this->tagify($value);
			}
			else
			{
				if (!$this->useWysiwyg(false))
				{
					$value = nl2br($value);
				}

				if ($value !== ''
					&&
					((int) $params->get('textarea-truncate-where', 0) === 2 || (int) $params->get('textarea-truncate-where', 0) === 3))
				{
					$opts = $this->truncateOpts();
					$value = fabrikString::truncate($value, $opts);
				}
			}

			return $value;
		}

		if ($params->get('textarea_placeholder', '') !== '')
		{
			$bits['placeholder'] = FText::_($params->get('textarea_placeholder'));
		}

		if ($this->elementError != '')
		{
			$bits['class'] .= ' elementErrorHighlight';
		}

		$layoutData = new stdClass;
		$this->charsLeft($value, $layoutData);

		if ($wysiwyg)
		{
			$editor = JEditor::getInstance($this->config->get('editor'));
			$buttons = (bool) $params->get('wysiwyg_extra_buttons', true);
			$layoutData->editor = $editor->display($name, $value, $cols * 10, $rows * 15, $cols, $rows, $buttons, $id);
			$layout = $this->getLayout('wysiwyg');
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

			$bits['name'] = $name;
			$bits['id'] = $id;
			$bits['cols'] = $cols;
			$bits['rows'] = $rows;
			$layoutData->attributes = $bits;
			$layoutData->value = $value;

			$layout = $this->getLayout('form');
		}

		return $layout->render($layoutData);
	}

	/**
	 * Create the 'characters left' interface when the element is rendered in the form view
	 *
	 * @param   string    $value  Value
	 * @param   stdClass  &$data  Layout data
	 *
	 * @return  array $data
	 */
	protected function charsLeft($value, stdClass &$data)
	{
		$params = $this->getParams();
		$data->showCharsLeft = false;

		if ($params->get('textarea-showmax'))
		{
			if ($params->get('textarea_limit_type', 'char') === 'char')
			{
				$label = FText::_('PLG_ELEMENT_TEXTAREA_CHARACTERS_LEFT');
				$charsLeft = $params->get('textarea-maxlength') - JString::strlen($value);
			}
			else
			{
				$label = FText::_('PLG_ELEMENT_TEXTAREA_WORDS_LEFT');
				$charsLeft = $params->get('textarea-maxlength') - count(explode(' ', $value));
			}

			$data->showCharsLeft = true;
			$data->charsLeft = $charsLeft;
			$data->charsLeftLabel = $label;
		}

		return $data;
	}

	/**
	 * Used to format the data when shown in the form's email
	 *
	 * @param   mixed  $value          Element's data
	 * @param   array  $data           Form records data
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  string	formatted value
	 */
	public function getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		$groupModel = $this->getGroup();

		if (is_array($value) && $groupModel->isJoin() && $groupModel->canRepeat())
		{
			$value = $value[$repeatCounter];
		}

		$oData = FArrayHelper::toObject($data);

		return $this->renderListData($value, $oData);
	}

	/**
	 * Used by radio and dropdown elements to get a dropdown list of their unique
	 * unique values OR all options - based on filter_build_method
	 *
	 * @param   bool    $normal     Do we render as a normal filter or as an advanced search filter
	 * @param   string  $tableName  Table name to use - defaults to element's current table
	 * @param   string  $label      Field to use, defaults to element name
	 * @param   string  $id         Field to use, defaults to element name
	 * @param   bool    $incjoin    Include join
	 *
	 * @return  array  text/value objects
	 */
	public function filterValueList($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		$params = $this->getParams();

		if ($params->get('textarea-tagify') == true)
		{
			return $this->getTags();
		}
		else
		{
			return parent::filterValueList($normal, $tableName, $label, $id, $incjoin);
		}
	}

	/**
	 * Used for filter lists - get distinct array of all recorded tags
	 *
	 * @since   3.0.7
	 *
	 * @return   array
	 */
	protected function getTags()
	{
		$listModel = $this->getListModel();
		$id = $this->getElement()->id;
		$cols = $listModel->getColumnData($id);
		$tags = array();

		foreach ($cols as $col)
		{
			$col = explode(',', $col);

			foreach ($col as $word)
			{
				$word = strtolower(trim($word));

				if ($word !== '')
				{
					$tags[$word] = JHTML::_('select.option', $word, $word);
				}
			}
		}

		$tags = array_values($tags);

		return $tags;
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
		$params = $this->getParams();

		if ($this->useWysiwyg())
		{
			// $$$ rob need to use the NAME as the ID when wysiwyg end in joined group
			//$id = $this->getHTMLName($repeatCounter);

			// Testing not using name as duplication of group does not trigger clone()
			$id = $this->getHTMLId($repeatCounter);

			if ($this->inDetailedView)
			{
				$id .= '_ro';
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
		$opts->htmlId = $this->getHTMLId($repeatCounter);

		return array('FbTextarea', $id, $opts);
	}

	/**
	 * Internal element validation
	 *
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  Repeat group counter
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
		return FText::_('PLG_ELEMENT_TEXTAREA_CONTENT_TOO_LONG');
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
	 * @return  array	Key=>value options
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
