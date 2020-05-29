<?php
/**
 * Required for Fabrik's J2Store implementation to work
 *
 * @package     Joomla.Plugin
 * @subpackage  System
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
defined('_JEXEC') or die();

class plgSystemFabrikj2store extends JPlugin
{

	/**
	 * Decorates the J2Store product row with the related Fabrik data
	 *
	 * @param object &$product
	 *
	 * @return void
	 */
	public function onJ2StoreAfterGetProduct(&$product)
	{
		if (isset($product->product_source) && strstr($product->product_source, 'com_fabrik'))
		{
			static $sets;

			if (!is_array($sets))
			{
				$sets = array();
			}

			$content = $this->getFabrikItem($product);

			if (isset($content->id))
			{
				$product->source           = $content;
				$product->product_name     = $content->title;
				$product->product_edit_url = $content->editLink;
				$product->product_view_url = $content->viewLink;

				if ($content->published == 1)
				{
					$product->exists = 1;
				}
				else
				{
					$product->exists = 0;
				}

				$sets[$product->product_source][$product->product_source_id] = $content;
			}
			else
			{
				$product->exists = 0;
			}
		}
	}

	/**
	 * Decorate the J2Store product information with its related Fabrik record
	 *
	 * @param $product
	 *
	 * @return object
	 */
	private function getFabrikItem(&$product)
	{
		list($component, $listId) = explode('.', $product->product_source);
		$key = $listId . '.' . $product->product_source_id;
		static $sets;

		if (!is_array($sets))
		{
			$sets = array();
		}
		if (!isset ($sets[$key]))
		{
			JModelLegacy::addIncludePath(COM_FABRIK_FRONTEND . '/models', 'FabrikFEModel');

			/** @var FabrikFEModelList $listModel */
			$listModel = JModelLegacy::getInstance('List', 'FabrikFEModel');
			$listModel->setId($listId);
			$formModel = $listModel->getFormModel();
			$formModel->setRowId($product->product_source_id);
			$row = $formModel->getData();

			$params = $formModel->getParams();
			$index  = array_search('j2store', (array) $params->get('plugins', array(), 'array'));

			$w      = new FabrikWorker;
			$plugIn = FabrikWorker::getPluginManager()->loadPlugIn('j2store', 'form');

			// Set params relative to plugin render order
			$plugInParams = $plugIn->setParams($params, $index);

			$context            = new stdClass;
			$context->title     = $w->parseMessageForPlaceHolder($plugInParams->get('j2store_product_name'), $row);
			$context->published = $w->parseMessageForPlaceHolder($plugInParams->get('j2store_enabled'), $row);
			$objectRow          = JArrayHelper::toObject($row);
			$context->viewLink  = $listModel->viewDetailsLink($objectRow);
			$context->editLink  = $listModel->editLink($objectRow);
			$context->id        = $objectRow->__pk_val;
			$sets[$key]         = $context;
		}
//echo "<pre>";print_r($sets);echo "</pre>";
		return $sets [$key];
	}
}