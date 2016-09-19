<?php
/**
 * Renders a upload size field
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/helpers/element.php';

/**
 * Renders a upload size field
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */

class JFormFieldUploadsize extends JFormField
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $name = 'Uploadsize';

	/**
	 * Get the number of bytes for an ini setting
	 *
	 * @param   string  $val  ini settings can be in K, M or G
	 *
	 * @return  int  bytes
	 */
	protected function _return_bytes($val)
	{
		$val = trim($val);
		$last = JString::strtolower(JString::substr($val, -1));

		if ($last == 'g')
		{
			$val = $val * 1024 * 1024 * 1024;
		}
		elseif ($last == 'm')
		{
			$val = $val * 1024 * 1024;
		}
		elseif ($last == 'k')
		{
			$val = $val * 1024;
		}

		return $val;
	}

	/**
	 * Get input markup
	 *
	 * @return  string  HTML markup
	 */
	protected function getInput()
	{
		$size = $this->element['size'] ? 'size="' . $this->element['size'] . '"' : '';
		$class = $this->element['class'] ? 'class="' . $this->element['class'] . '"' : 'class="text_area"';
		$value = htmlspecialchars(html_entity_decode($this->value, ENT_QUOTES), ENT_QUOTES);

		if ($value == '')
		{
			$value = $this->getMax();
		}

		return '<input type="text" name="' . $this->name . '" id="' . $this->id . '" value="' . $value . '" ' . $class . ' ' . $size . ' />';
	}

	/**
	 * Method to get the field label markup.
	 *
	 * @return  string  The field label markup.
	 */
	protected function getLabel()
	{
		// Get the label text from the XML element, defaulting to the element name.
		$text = $this->element['label'] ? (string) $this->element['label'] : (string) $this->element['name'];
		$text = $this->translateLabel ? FText::_($text) : $text;
		$max = $this->getMax();
		$mb = $max / 1024;
		$this->description = FText::_($this->description) . $max . 'Kb / ' . $mb . 'Mb';

		return parent::getLabel();
	}

	/**
	 * Get the max upload size allowed by the server.
	 *
	 * @return	int	kilobyte upload size
	 */
	protected function getMax()
	{
		$post_value = $this->_return_bytes(ini_get('post_max_size'));
		$upload_value = $this->_return_bytes(ini_get('upload_max_filesize'));
		$value = min($post_value, $upload_value);
		$value = $value / 1024;

		return $value;
	}
}
