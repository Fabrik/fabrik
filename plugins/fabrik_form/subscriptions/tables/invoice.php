<?php
/**
 *  JTable For Subscriptions Invoices
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.subscriptions
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/tables/fabtable.php';

/**
 *  JTable For Subscriptions Invoices
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.subscriptions
 * @since       3.0.7
 */

class FabrikTableInvoice extends JTable
{
	/**
	 * Constructor
	 *
	 * @param   object  &$db  database object
	 */

	public function __construct(&$db)
	{
		parent::__construct('#__fabrik_subs_invoices', 'id', $db);
	}

	/**
	 * Update the invoice based on the request data
	 *
	 * @param   array  $request  posted invoice data
	 *
	 * @return  void
	 */

	public function update($request)
	{
		$now = JFactory::getDate()->toSQL();
		$this->transaction_date = $now;
		$this->pp_txn_id = $request['txn_id'];
		$this->pp_payment_status = $request['payment_status'];
		$this->pp_payment_amount = $request['mc_gross'];
		$this->pp_txn_type = $request['txn_type'];
		$this->pp_fee = $request['mc_fee'];
		$this->pp_payer_email = $request['payer_email'];
		$this->paid = 1;
		$this->store();
	}
}
