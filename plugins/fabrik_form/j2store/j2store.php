<?php
/**
 * Creates a J2Store add to cart button
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.j2store
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Creates a J2Store add to cart button
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.j2store
 * @since       3.0
 */
class PlgFabrik_FormJ2Store extends PlgFabrik_Form
{
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $name = 'j2store';

	/**
	 * Have we loaded the list js code
	 *
	 * @var  bool
	 */
	protected static $listJs = null;

	/**
	 * Draws the html form element
	 *
	 * @param   array $data          to pre-populate element with
	 * @param   int   $repeatCounter repeat group counter
	 *
	 * @return  string    elements html
	 */
	public function render($data, $repeatCounter = 0)
	{
		$layoutData       = new stdClass;
		$name             = $this->getHTMLName($repeatCounter);
		$id               = $this->getHTMLId($repeatCounter);
		$layoutData->id   = $id;
		$layoutData->name = $name;
		$layout           = $this->getLayout('form');

		return $layout->render($layoutData);
	}

	/**
	 * Process the plugin, called when form is submitted
	 *
	 * @return  bool
	 */
	public function onAfterProcess()
	{
		if (!defined('F0F_INCLUDED'))
		{
			include_once JPATH_LIBRARIES . '/f0f/include.php';
		}

		$data     = array();
		$formData = $this->getProcessData();

		$productModel = F0FModel::getTmpInstance('Products', 'J2StoreModel');
		$productTable = F0FTable::getAnInstance('Product', 'J2StoreTable');
		$source       = $this->j2StoreSource();
		$productTable->load(array('product_source' => $source, 'product_source_id' => $this->getModel()->getInsertId()));
		$productId = $productTable->get('j2store_product_id');

		$props = array(
			'enabled',
			'product_type',
			'visibility',
			'sku',
			'upc',
			'price',
			'manufacturer_id',
			'vendor_id',
			'addtocart_text',
			'shipping',
			'length',
			'width',
			'height',
			'weight',
			'length_class_id',
			'weight_class_id',
			'taxprofile_id'
		);

		foreach ($props as $prop)
		{
			$this->appendProperty($data, $prop, $formData);
		}

		$productParams = array();
		$this->appendProperty($productParams, 'download_limit', $formData);
		$this->appendProperty($productParams, 'download_expiry', $formData);
		$data['params'] = json_encode($productParams);

		$productTypes = array(
			'downloadable',
			'simple'
		);

		if (!in_array($data['product_type'], $productTypes))
		{
			$data['product_type'] = 'simple';
		}

		if (!isset($data['enabled']))
		{
			$data['enabled'] = '1';
		}

		if (!isset($data['visibility']))
		{
			$data['visibility'] = '1';
		}

		$data['product_source']     = $source;
		$data['product_source_id']  = $this->getModel()->getInsertId();
		$data['pricing_calculator'] = 'standard';
		$data['j2store_product_id'] = $productId;

		// j2store save tosses a warning if this isn't there ...
		$data['productfilter_ids'] = array();

		$productModel->save($data);

		if (empty($productId))
		{
			$productId = $productModel->getId();
		}

		$this->storeImages($data, $productTable->get('j2store_productimage_id'));
		$this->storeVariant($data, $productId);
		$this->storeFiles($formData, $productId);
	}

	/**
	 * Store the product variant information
	 *
	 * @param array $data      Table data to bind
	 * @param int   $productId Product id
	 *
	 * @return bool
	 */
	private function storeVariant($data, $productId)
	{
		$table = F0FTable::getAnInstance('Variant', 'J2StoreTable');
		$table->load(array('product_id' => $productId));

		$data['product_id'] = $productId;
		$data['is_master'] = 1;

		return $table->save($data);
	}

	/**
	 * Store the product files. Presumes the file information will be in a repeat group
	 *
	 * @param array $placeholderData
	 * @param int   $productId
	 *
	 * @return void
	 */
	private function storeFiles($placeholderData, $productId)
	{
		$w      = new FabrikWorker;
		$params = $this->getParams();

		// Map Fabrik repeat data into rows ready for insertion into the database.
		$rows   = array();
		$fields = array('j2store_product_file_display_name', 'j2store_product_file_save_name');

		foreach ($fields as $field)
		{
			$key                   = str_replace('j2store_', '', $field);
			$fileDisplayName       = $params->get($field);
			$simpleFileDisplayName = str_replace(array('{', '}'), array('', ''), $fileDisplayName);
			$displayNames          = ArrayHelper::getValue($placeholderData, $simpleFileDisplayName, array($fileDisplayName));
			$i                     = 0;

			foreach ($displayNames as $displayName)
			{
				if (!array_key_exists($i, $rows))
				{
					$rows[$i] = array();
				}
				$rows[$i][$key] = $w->parseMessageForPlaceHolder($displayName, $placeholderData);
				$i++;
			}
		}

		$keep = array();

		foreach ($rows as $row)
		{
			$table             = F0FTable::getAnInstance('ProductFiles', 'J2StoreTable');
			$row['product_id'] = $productId;

			// Ensure we update the row if it exists by loading it first
			$table->load($row);
			$table->save($row);
			$keep[] = $table->get('j2store_productfile_id');
		}

		// Remove records that no longer exist
		$db    = $this->_db;
		$query = $db->getQuery(true);
		$query->delete('#__j2store_productfiles')->where('product_id = ' . $db->q($productId))
			->where('j2store_productfile_id NOT IN (' . implode(',', $keep) . ')');
		$db->setQuery($query)->execute();
	}

	/**
	 * Store images
	 *
	 * @param array $data
	 * @param int   $productImageId Id for product image table
	 *
	 * @return bool
	 */
	private function storeImages($data, $productImageId)
	{
		$formData = $this->getProcessData();
		$images   = array('main_image', 'thumb_image');

		foreach ($images as $prop)
		{
			$this->appendProperty($images, $prop, $formData);
		}

		$images['j2store_productimage_id'] = $productImageId;
		$table                             = F0FTable::getAnInstance('Productimages', 'J2StoreTable')->getClone();
		$table->load(array('product_id' => $data['j2store_product_id']));
		$table->bind($images);

		return $table->store();
	}

	/**
	 *
	 * @param array  $data            Data to append property data to
	 * @param string $propName        Fabrik j2store plugin property name (without j2store_ prefix)
	 * @param array  $placeholderData Form data to replace form plugin properties
	 *
	 * @return void
	 */
	private function appendProperty(&$data, $propName, $placeholderData)
	{
		$params          = $this->getParams();
		$w               = new FabrikWorker;
		$key             = 'j2store_' . $propName;
		$data[$propName] = trim($w->parseMessageForPlaceHolder($params->get($key), $placeholderData));
	}

	/**
	 * Get the component and list unique identifier
	 *
	 * @return string
	 */
	private function j2StoreSource()
	{
		return 'com_fabrik.' . $this->getModel()->getListModel()->getId();
	}

	/**
	 * Sets up any end html (after form close tag)
	 *
	 * @return  void
	 */
	public function getEndContent()
	{
		if ($this->app->isAdmin()  || !$this->showCartButtons())
		{
			return;
		}

		$this->html = '';
		$product    = F0FTable::getAnInstance('Product', 'J2StoreTable')->getClone();
		$id         = $this->getModel()->getRowId();

		if ($product->get_product_by_source($this->j2StoreSource(), $id))
		{
			$layout     = $this->getLayout('addtocart');
			$this->html = $layout->render((object) array('product' => $product));
		}
	}

	/**
	 * Get any html that needs to be written after the form close tag
	 *
	 * @return    string    html
	 */
	public function getEndContent_result()
	{
		return $this->html;
	}

	/**
	 * Run from list model when deleting rows
	 * Should delete any j2store products
	 *
	 * @param   array &$groups List data for deletion
	 *
	 * @return  bool
	 */
	public function onDeleteRowsForm(&$groups)
	{
		$return = true;

		foreach ($groups as $group)
		{
			foreach ($group as $rows)
			{
				foreach ($rows as $row)
				{
					$id = isset ($row->__pk_val) ? $row->__pk_val : 0;

					if ($id)
					{
						$productModel = F0FModel::getTmpInstance('Products', 'J2StoreModel');
						$itemList     = $productModel->getProductsBySource($this->j2StoreSource(), $id);

						foreach ($itemList as $item)
						{
							$return = $return && $productModel->setId($item->j2store_product_id)->delete();
						}
					}
				}
			}
		}

		return $return;
	}

	/**
	 * Add the add to cart list layout to the Fabrik list's data
	 *
	 * @param array $opts
	 *
	 * @return void
	 */
	public function onLoadListData($opts)
	{
		if ($this->app->isAdmin()  || !$this->showCartButtons())
		{
			return;
		}

		$lang = JFactory::getLanguage();
		$lang->load('com_j2store', JPATH_SITE . '/administrator', null, false, true);
		$data = $opts[0]->data;

		foreach ($data as $group)
		{
			foreach ($group as $row)
			{
				$product = F0FTable::getAnInstance('Product', 'J2StoreTable')->getClone();
				$id      = $row->__pk_val;
				$source  = $this->j2StoreSource();
				$helper  = new J2Product();

				if ($product->get_product_by_source($source, $id))
				{
					$helper->getCheckoutLink($product);
					$layout       = $this->getLayout('addtocart-list');
					$row->j2store = $layout->render((object) array('product' => $product));
				}
				else
				{
					$row->j2store = FText::sprintf('PLG_FORM_J2STORE_PRODUCT_NOT_FOUND', $source, $id);
				}
			}
		}

		$this->listJs();
	}

	/**
	 * Add the list JS code.
	 *
	 * @return void
	 */
	private function listJs()
	{
		// Add JS once
		if (is_null(self::$listJs))
		{
			self::$listJs = true;

			// Includes the ajax add to cart js.
			require_once (JPATH_ADMINISTRATOR.'/components/com_j2store/helpers/strapper.php');
			J2StoreStrapper::addJs();

			// Watch quantity input and update add to cart button data.
			$doc = JFactory::getDocument();
			$doc->addScriptDeclaration('jQuery(document).ready(function ($) {
			$(document).on(\'change\', \'input[name=product_qty]\', function () {
				var productId = $(this).data(\'product_id\'),
				q = $(this).val();
				$(\'a[data-product_id=\' + productId + \']\').data(\'product_qty\', q);
			});
			$(\'body\').on(\'adding_to_cart\', function(e, btn, data) {
				Fabrik.loader.start(btn.closest(\'.fabrikForm\'), Joomla.JText._(\'COM_FABRIK_LOADING\'));
			});
			$(\'body\').on(\'after_adding_to_cart\', function(e, btn, response, type) {
				Fabrik.loader.stop(btn.closest(\'.fabrikForm\'));
			});
		});
		');
		}
	}

	/**
	 * Add the heading information to the Fabrik list, so as to include a column for the add to cart link
	 *
	 * @param array $args
	 *
	 * @return void
	 */
	public function onGetPluginRowHeadings($args)
	{
		if ($this->app->isAdmin() || !$this->showCartButtons())
		{
			return;
		}

		$args[0]['tableHeadings']['j2store'] = '';
		$args[0]['headingClass']['j2store']  = array('class' => '', 'style' => '');
		$args[0]['cellClass']['j2store']     = array('class' => '', 'style' => '');
	}

	/**
	 * Determine if we use the plugin or not
	 * both location and event criteria have to be match when form plug-in
	 *
	 * @param   string $location Location to trigger plugin on
	 * @param   string $event    Event to trigger plugin on
	 *
	 * @return  bool  true if we should run the plugin otherwise false
	 */
	public function showCartButtons($location = null, $event = null)
	{
		$params = $this->getParams();
		$groups = $this->user->getAuthorisedViewLevels();

		return in_array($params->get('j2store_access', '1'), $groups);
	}

}
