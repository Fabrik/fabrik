<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.youtube
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Render an embedded youtube video play
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.youtube
 * @since       3.0
 */

class PlgFabrik_ElementYoutube extends PlgFabrik_Element
{

	var $_pluginName = 'youtube';

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
		$params = $this->getParams();
		// ------------------ Construct embedded player

		// Player size
		if (($params->get('display_in_table') == 2) || ($params->get('display_in_table') == 1))
		{ // Display in table = Normal
			if ($params->get('or_width_player') != NULL)
			{
				$width = $params->get('or_width_player');
				$height = $params->get('or_height_player');
			}
			elseif ($params->get('player_size') == 'small')
			{
				$width = '340';
				$height = '285';
			}
			elseif ($params->get('player_size') == 'medium')
			{
				$width = '445';
				$height = '364';
			}
			elseif ($params->get('player_size') == 'normal')
			{
				$width = '500';
				$height = '405';
			}
			elseif ($params->get('player_size') == 'big')
			{
				$width = '660';
				$height = '525';
			}
		}
		elseif ($params->get('display_in_table') == 0)
		{ // Display in table = Mini
			$width = '170';
			$height = '142';
		}

		// Include related videos
		if ($params->get('include_related') == 0)
		{
			$rel = '&rel=0';
		}
		else
		{
			$rel = '';
		}

		// Show border
		if (($params->get('show_border') == 1) && ($params->get('display_in_table') != 0))
		{ // Don't show borders if display in table = Mini
			$border = '&border=1';
		}
		else
		{
			$border = '';
		}

		// Enable delayed cookies
		if ($params->get('enable_delayed_cookies') == 1)
		{
			$url = 'http://www.youtube-nocookie.com/v/';
		}
		else
		{
			$url = 'http://www.youtube.com/v/';
		}

		// Colors
		$color1 = JString::substr($params->get('color1'), -6);
		$color2 = JString::substr($params->get('color2'), -6);

		$bits = explode("/", $data);
		$vid = array_pop($bits);
		//$$$tom: if one copies an URL from youtube, the URL has the "watch?v=" which barfs the player
		if (strstr($vid, 'watch'))
		{
			$vid = explode("=", $vid);
			unset($vid[0]); // That's the watch?v=
			$vid = implode('', $vid);
		}
		if ($vid == '')
		{
			//$$$ rob perhaps they just added in the code???
			$vid = $data;
		}
		if ($data != NULL)
		{
			if ($params->get('display_in_table') == 1)
			{ // Display link
				if ($params->get('display_link') == 0)
				{
					$object_vid = $data;
				}
				else
				{
					if ($params->get('display_link') == 1)
					{
						$dlink = $data;
					}
					else
					{
						if ($params->get('text_link') != NULL)
						{
							$dlink = JText::_( $params->get('text_link'));
						}
						else
						{
							$dlink = 'Watch Video';
						}
					}
					if ($params->get('target_link') == 1)
					{
						$object_vid = '<a href="' . $data . '" target="blank">' . $dlink . '</a>';
					}
					elseif ($params->get('target_link') == 2)
					{

						$element = &$this->getElement();
						$object_vid = "<a href='" . $data . "' rel='lightbox[social " . $width . " " . $height . "]' title='" . $element->label
							. "'>" . $dlink . "</a>";

					}
					else
					{
						$object_vid = '<a href="' . $data . '">' . $dlink . '</a>';
					}
				}
			}
			else
			{
				$object_vid = '<object width="' . $width . '" height="' . $height . '"><param name="movie" value="' . $url . $vid . '&hl=en&fs=1'
					. $rel . '&color1=0x' . $color1 . '&color2=0x' . $color2 . $border
					. '"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="'
					. $url . $vid . '&hl=en&fs=1' . $rel . '&color1=0x' . $color1 . '&color2=0x' . $color2 . $border
					. '" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="' . $width . '" height="'
					. $height . '"></embed></object>';
			}
		}
		else
		{
			$object_vid = '';
		}

		return $object_vid;
		//}
	}

	/**
	 * Do we need to include the lighbox js code
	 *
	 * @return  bool
	 */

	public function requiresLightBox()
	{
		return true;
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
	 * Draws the html form element
	 *
	 * @param   array  $data           to preopulate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$params = $this->getParams();
		$element = $this->getElement();
		$data = $this->getFormModel()->_data;
		$value = $this->getValue($data, $repeatCounter);
		if ($input->get('view') != 'details')
		{
			$name = $this->getHTMLName($repeatCounter);
			$id = $this->getHTMLId($repeatCounter);
			$size = $params->get('width');
			$maxlength = 255;
			$bits = array();
			$type = "text";
			if (isset($this->_elementError) && $this->_elementError != '')
			{
				$type .= " elementErrorHighlight";
			}
			if (!$this->isEditable())
			{
				return ($element->hidden == '1') ? '<!-- ' . $value . ' -->' : $value;
			}
			$bits['class'] = "fabrikinput inputbox $type";
			$bits['type'] = $type;
			$bits['name'] = $name;
			$bits['id'] = $id;

			// Stop "'s from breaking the content out of the field.
			$bits['value'] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
			$bits['size'] = $size;
			$bits['maxlength'] = $maxlength;
			$str = "<input ";
			foreach ($bits as $key => $val)
			{
				$str .= $key . ' = "' . $val . '" ';
			}
			$str .= " />\n";
			return $str;
		}
		else
		{
			$params = $this->getParams();
			$element = &$this->getElement();
			$data = &$this->_form->_data;
			$value = $this->getValue($data, $repeatCounter);
			// ------------------ Construct embedded player

			// Player size
			if ($params->get('or_width_player') != NULL)
			{
				$width = $params->get('or_width_player');
				$height = $params->get('or_height_player');
			}
			else
			{
				if ($params->get('player_size') == 'small')
				{
					$width = '340';
					$height = '285';
				}
				elseif ($params->get('player_size') == 'medium')
				{
					$width = '445';
					$height = '364';
				}
				elseif ($params->get('player_size') == 'normal')
				{
					$width = '500';
					$height = '405';
				}
				else
				{
					$width = '660';
					$height = '525';
				}
			}

			// Include related videos
			if ($params->get('include_related') == 0)
			{
				$rel = '&rel=0';
			}
			else
			{
				$rel = '';
			}

			// Show border
			if ($params->get('show_border') == 1)
			{
				$border = '&border=1';
			}
			else
			{
				$border = '';
			}

			// Enable delayed cookies
			if ($params->get('enable_delayed_cookies') == 1)
			{
				$url = 'http://www.youtube-nocookie.com/v/';
			}
			else
			{
				$url = 'http://www.youtube.com/v/';
			}

			// Colors
			$color1 = JString::substr($params->get('color1'), -6);
			$color2 = JString::substr($params->get('color2'), -6);
			// $$$ rob - barfed if url entered was like http://www.youtube.com/v/zD8XclVc3DQ&hl=en
			//$vid = JString::substr($value, 31);
			//this seems more sturdy a method:
			$bits = explode("/", $value);
			$vid = array_pop($bits);
			//$$$tom: if one copies an URL from youtube, the URL has the "watch?v=" which barfs the player
			if (strstr($vid, 'watch'))
			{
				$vid = explode("=", $vid);
				unset($vid[0]); // That's the watch?v=
				$vid = implode('', $vid);
			}
			if ($vid == '')
			{
				//$$$ rob perhaps they just added in the code???
				$vid = $value;
			}
			if ($value != NULL)
			{
				$object_vid = '<object width="' . $width . '" height="' . $height . '"><param name="movie" value="' . $url . $vid . '&hl=en&fs=1'
					. $rel . '&color1=0x' . $color1 . '&color2=0x' . $color2 . $border
					. '"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="'
					. $url . $vid . '&hl=en&fs=1' . $rel . '&color1=0x' . $color1 . '&color2=0x' . $color2 . $border
					. '" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="' . $width . '" height="'
					. $height . '"></embed></object>';
			}
			else
			{
				$object_vid = '';
			}

			return $object_vid;
		}
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
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);
		return array('FbYouTube', $id, $opts);
	}

	/**
	 * get the class to manage the form element
	 * if a plugin class requires to load another elements class (eg user for dbjoin then it should
	 * call FabrikModelElement::formJavascriptClass('plugins/fabrik_element/databasejoin/databasejoin.js', true);
	 * to ensure that the file is loaded only once
	 *
	 * @param   array   &$srcs   scripts previously loaded (load order is important as we are loading via head.js
	 * and in ie these load async. So if you this class extends another you need to insert its location in $srcs above the
	 * current file
	 * @param   string  $script  script to load once class has loaded
	 *
	 * @return void
	 */

	function formJavascriptClass(&$srcs, $script = '')
	{
		plgFabrik_Element::formJavascriptClass($srcs, 'plugins/fabrik_element/youtube/youtube.js');
		parent::formJavascriptClass($srcs);
	}

	/**
	 * Get database field description
	 *
	 * @return  string  db field type
	 */

	public function getFieldDescription()
	{
		$p = $this->getParams();
		if ($this->encryptMe())
		{
			return 'BLOB';
		}
		$objtype = "VARCHAR(" . $p->get('maxlength', 255) . ")";
		return $objtype;
	}

}
