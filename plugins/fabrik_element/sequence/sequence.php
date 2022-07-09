<?php
/**
 * Plugin element to store the user's IP address
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.ip
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

/**
 * Plugin element to store the user's IP address
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.ip
 * @since       3.0
 */
class PlgFabrik_ElementSequence extends PlgFabrik_Element
{
	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to pre-populate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */
	public function render($data, $repeatCounter = 0)
	{
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();
		$value = $this->getValue($data, $repeatCounter);

		$layoutData = new stdClass;
		$layoutData->id = $id;
		$layoutData->name = $name;
		$layoutData->value = $value;

		if ($this->canView())
		{
			if (!$this->isEditable())
			{
				return $value;
			}
			else
			{
				$layoutData->type = 'text';
			}
		}
		else
		{
			// Make a hidden field instead
			$layoutData->type = 'hidden';
		}

		$layout = $this->getLayout('form');

		return $layout->render($layoutData);
	}

	/**
	 * Trigger called when a row is stored.
	 * If we are creating a new record, and the element was set to readonly
	 * then insert the users data into the record to be stored
	 *
	 * @param   array  &$data          Data to store
	 * @param   int    $repeatCounter  Repeat group index
	 *
	 * @return  bool  If false, data should not be added.
	 */
	public function onStoreRow(&$data, $repeatCounter = 0)
	{
		$element = $this->getElement();

		if (!$element->published)
		{
			return false;
		}

		if ($this->encryptMe())
		{
			$shortName = $element->name;
			$listModel = $this->getListModel();
			$listModel->encrypt[] = $shortName;
		}


		$element = $this->getElement();
		$formModel = $this->getFormModel();
		$params = $this->getParams();
		$method = $params->get('sequence_method', 'load');

		if ($formModel->isNewRecord() && $method === 'submit')
		{
			$formData = json_decode(json_encode($formModel->formDataWithTableName));
			$this->swapValuesForLabels($formData);
			$this->setStoreDatabaseFormat($formData, $repeatCounter);
			$data[$element->name] = $data[$element->name . '_raw'] = $this->getSequence($formData);
		}
		else if ($method !== 'pk')
		{
			$name  = $this->getFullName(true, false);
			$formModel = $this->getFormModel();
			$data[$element->name] = FArrayHelper::getValue(
				$formModel->formDataWithTableName, $name . '_raw',
				FArrayHelper::getValue($data, $name, '')
			);
		}

		return true;
	}

	private function getSequence($data = array())
	{
		$params = $this->getParams();
		$w = new FabrikWorker();
		$db    = JFactory::getDbo();

		$position = $params->get('sequence_position', 'prefix');
		$padding = $params->get('sequence_padding', '4');
		$placeholderQuery = $params->get('placeholder_query', '');
		$placeholders = array();

		if (!empty($placeholderQuery))
		{
			$placeholderQuery = $w->parseMessageForPlaceHolder($placeholderQuery, $data);
			$db->setQuery($placeholderQuery);

			try
			{
				$placeholders = $db->loadAssoc();
			}
			catch (Exception $e)
			{
				// meh
				$this->app->enqueueMessage("There was an error assigning the sequence number, please contact the site admins.");
			}
		}

		$data = array_merge($data, $placeholders);
		$affix = $params->get('sequence_affix', '');
		$affix = $w->parseMessageForPlaceHolder($affix, $data);

		$tableName = $this->getlistModel()->getTable()->db_table_name;
		$elementId = $this->getElement()->id;

		// create the table if it doesn't exist ... should get created on install, but ... eh ...
		$this->createSequenceTable();

		$method = $params->get('sequence_method', 'load');

		if ($method !== 'pk')
		{
            $sequenceQuery = $params->get('sequence_query', '');

            if (!empty($sequenceQuery))
            {
                $sequenceQuery = $w->parseMessageForPlaceHolder($sequenceQuery, $data);
                $db->setQuery($sequenceQuery);

	            try
	            {
		            $sequence = $db->loadResult();
	            }
	            catch (Exception $e)
	            {
		            // meh
		            $this->app->enqueueMessage("There was an error assigning the sequence number, please contact the site admins.");
	            }

            }

            if (empty($sequence)) {
                $query = $db->getQuery(true);
                $query->select('sequence')
                    ->from('#__fabrik_sequences')
                    ->where($db->quoteName('table_name') . ' = ' . $db->quote($tableName))
                    ->where($db->quoteName('affix') . ' = ' . $db->quote($affix))
                    ->where($db->quoteName('element_id') . ' = ' . $db->quote($elementId));
                $db->setQuery($query);
                $row = $db->loadObject();

                if (empty($row)) {
                    $start = (int)$params->get('sequence_start', '1');
                    $columns = array(
                        $db->quoteName('table_name'),
                        $db->quoteName('affix'),
                        $db->quoteName('element_id'),
                        $db->quoteName('sequence')
                    );
                    $values = array(
                        $db->quote($tableName),
                        $db->quote($affix),
                        $db->quote($elementId),
                        $db->quote($start)
                    );

                    $query->clear()
                        ->insert('#__fabrik_sequences')
                        ->columns($columns)
                        ->values(implode(',', $values));
                    $db->setQuery($query);
                    $db->execute();

                    $sequence = $start;
                } else {
                    $sequence = (int)$row->sequence;
                    $sequence++;
                    $query->clear()
                        ->update('#__fabrik_sequences')
                        ->set($db->quoteName('sequence') . ' = ' . $sequence)
                        ->where($db->quoteName('table_name') . ' = ' . $db->quote($tableName))
                        ->where($db->quoteName('affix') . ' = ' . $db->quote($affix))
                        ->where($db->quoteName('element_id') . ' = ' . $db->quote($elementId));
                    $db->setQuery($query);
                    $db->execute();
                }
            }
		}
		else
		{
			$sequence = (int) ArrayHelper::getValue($data, 'rowid', '');
		}

		$sequence = sprintf('%0' . $padding . 'd', $sequence);

		if ($position === 'prefix')
		{
			$sequence = $affix . $sequence;
		}
		else
		{
			$sequence = $sequence . $affix;
		}

		return $sequence;
	}

	/**
	 * This really does get just the default value (as defined in the element's settings)
	 *
	 * @param   array  $data  form data
	 *
	 * @return mixed
	 */
	public function getDefaultValue($data = array())
	{
		if (!isset($this->default))
		{
			$params = $this->getParams();
			if ($params->get('sequence_method', 'load') === 'load')
			{
				$formModel = $this->getFormModel();

				if ($formModel->failedValidation())
				{
					$name  = $this->getFullName(true, false);
					$this->default = FArrayHelper::getValue($data, $name . '_raw', FArrayHelper::getValue($data, $name, ''));
				}
				else
				{
					if ($formModel->isNewRecord())
					{
						$this->default = $this->getSequence($data);
					}
					else
					{
						$name  = $this->getFullName(true, false);
						$this->default = FArrayHelper::getValue($data, $name . '_raw', FArrayHelper::getValue($data, $name, ''));
					}
				}
			}
			else
			{
				$this->default = JText::_('PLG_ELEMENT_SEQUENCE_ASSIGNED_AFTER_SUBMIT');
			}
		}

		return $this->default;
	}

	/**
	 * Determines the value for the element in the form view
	 *
	 * @param   array  $data           form data
	 * @param   int    $repeatCounter  when repeating joined groups we need to know what part of the array to access
	 * @param   array  $opts           options
	 *
	 * @return  string	value
	 */
	public function getValue($data, $repeatCounter = 0, $opts = array())
	{
		// Kludge for 2 scenarios
		if (array_key_exists('rowid', $data))
		{
			// When validating the data on form submission
			$key = 'rowid';
		}
		else
		{
			// When rendering the element to the form
			$key = '__pk_val';
		}

		if (empty($data) || !array_key_exists($key, $data) || (array_key_exists($key, $data) && empty($data[$key])))
		{
			$value = $this->getDefaultOnACL($data, $opts);

			return $value;
		}

		$res = parent::getValue($data, $repeatCounter, $opts);

		return $res;
	}

	public function onAfterProcess()
	{
		$params = $this->getParams();

		if ($params->get('sequence_method', 'load') === 'pk')
		{
			$formModel = $this->getFormModel();
			$rowid = ArrayHelper::getValue($formModel->formData, 'rowid', '');
			if (!empty($rowid))
			{
				$this->getListModel()->storeCell(
					$rowid,
					$this->getElement()->name,
					$this->getSequence($formModel->formData)
				);
			}
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

		return array('FbSequence', $id, $opts);
	}

	/**
	 * Create the rating table if it doesn't exist.
	 *
	 * @return  void
	 */
	private function createSequenceTable()
	{
		$db = FabrikWorker::getDbo(true);
		$db
			->setQuery(
				"
			CREATE TABLE IF NOT EXISTS  `#__fabrik_sequences` (
	`table_name` VARCHAR( 64 ) NOT NULL,
	`affix` VARCHAR( 32 ) NOT NULL,
	`sequence` INT( 6 ) NOT NULL,
	`date_created` DATETIME NOT NULL,
	`element_id` INT( 6 ) NOT NULL,
	 PRIMARY KEY ( `table_name` , `affix`, `sequence`, `element_id` )
);");
		$db->execute();
	}

}
