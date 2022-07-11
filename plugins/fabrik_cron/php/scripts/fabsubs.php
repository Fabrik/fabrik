<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

error_reporting(E_ALL);
ini_set('max_execution_time', 300);

jimport('joomla.mail.helper');
JTable::addIncludePath(JPATH_ROOT . '/plugins/fabrik_form/subscriptions/tables');
//JTable::addIncludePath(JPATH_ROOT . '/plugins/fabrik_cron/php/scripts/tables');
//require_once JPATH_ROOT . '/plugins/fabrik_cron/php/scripts/ipn.php';
require_once JPATH_ROOT . '/plugins/fabrik_form/subscriptions/scripts/ipn.php';


$db = FabrikWorker::getDbo();

$db->setQuery("SELECT *,
CASE
	WHEN timeunit = 'Y' THEN 365 * time_value
	WHEN timeunit = 'M' THEN 30 * time_value
	WHEN timeunit = 'W' THEN 7 * time_value
	WHEN timeunit = 'D' THEN time_value
  END
  as emailday
   FROM #__fabrik_subs_cron_emails WHERE event_type = 'auto_renewal'");
$auto_renewal_mails = $db->loadObjectList('emailday');
//echo $db->getQuery();

$db->setQuery("SELECT *,
CASE
	WHEN timeunit = 'Y' THEN 365 * time_value
	WHEN timeunit = 'M' THEN 30 * time_value
	WHEN timeunit = 'W' THEN 7 * time_value
	WHEN timeunit = 'D' THEN time_value
  END
  as emailday
   FROM #__fabrik_subs_cron_emails WHERE event_type = 'expiration'");
$expiration_mails = $db->loadObjectList('emailday');


$config = JFactory::getConfig();
$sitename = $config->get('sitename');
$mailfrom = $config->get('mailfrom');
$fromname = $config->get('fromname');
$url = str_replace('/administrator', '', JURI::base());

	$db->setQuery("SELECT s.id AS subid, p.id AS planid, pbc.duration, p.plan_name AS subscription, pbc.period_unit,
u.username, u.email, u.name, s.userid,
s.signup_date, '$sitename' AS sitename, '$mailfrom' AS mailfrom, '$url' AS url, '$fromname' AS fromname, s.recurring,
s.lastpay_date,
CASE
	WHEN pbc.period_unit = 'Y' THEN date_add(lastpay_date , interval pbc.duration year)
	WHEN pbc.period_unit = 'M' THEN date_add(lastpay_date , interval pbc.duration month)
	WHEN pbc.period_unit = 'W' THEN date_add(lastpay_date , interval pbc.duration week)
	WHEN pbc.period_unit = 'D' THEN date_add(lastpay_date , interval pbc.duration day)
  END
  AS renew_date
,
CASE
	WHEN pbc.period_unit = 'Y' THEN datediff(date_add(lastpay_date , interval pbc.duration year), now())
	WHEN pbc.period_unit = 'M' THEN datediff(date_add(lastpay_date , interval pbc.duration month), now())
	WHEN pbc.period_unit = 'W' THEN datediff(date_add(lastpay_date , interval pbc.duration week), now())
	WHEN pbc.period_unit = 'D' THEN datediff(date_add(lastpay_date , interval pbc.duration day), now())
  END
  AS daysleft
FROM `#__fabrik_subs_subscriptions` AS s
LEFT JOIN #__users AS u ON u.id = s.userid
INNER JOIN #__fabrik_subs_plans AS p ON p.id = s.plan
INNER JOIN #__fabrik_subs_plan_billing_cycle AS pbc ON pbc.id = s.billing_cycle_id
INNER JOIN #__fabrik_subs_payment_gateways AS g ON g.id = s.type
 WHERE s.status = 'Active' AND s.lifetime = 0 and p.free = 0
ORDER BY daysleft ");

$res = $db->loadObjectList();

//var_dump($db->getQuery(), $res);exit;
foreach ($res as $row) {
	if (array_key_exists($row->daysleft, $auto_renewal_mails) && $row->recurring == 1) {
		$mail = clone($auto_renewal_mails[$row->daysleft]);
		foreach ($row as $k=>$v) {
			$mail->subject = str_replace('{'.$k.'}', $v, $mail->subject);
			$mail->body = str_replace('{'.$k.'}', $v, $mail->body);
		}
		echo "would mail: " . $row->email;
		//$res = JUtility::sendMail($mailfrom, $fromname, $row->email, $mail->subject, $mail->body, true);
	}

	if (array_key_exists($row->daysleft, $expiration_mails) && $row->recurring == 0)
	{
		$mail = clone($expiration_mails[$row->daysleft]);
		foreach ($row as $k=>$v)
		{
			$mail->subject = str_replace('{'.$k.'}', $v, $mail->subject);
			$mail->body = str_replace('{'.$k.'}', $v, $mail->body);
		}
		echo "would mail: " . $row->email;
		//$res = JUtility::sendMail($mailfrom, $fromname, $row->email, $mail->subject, $mail->body, true);
	}
}

// get list of valid subs users

$db->setQuery("SELECT s.userid AS userid, u.username, u.name, u.email, pbc.plan_name, s.recurring
FROM `#__fabrik_subs_subscriptions` AS s
INNER JOIN #__fabrik_subs_plans AS p ON p.id = s.plan
INNER JOIN #__fabrik_subs_plan_billing_cycle AS pbc ON pbc.id = s.billing_cycle_id
INNER JOIN #__users AS u ON u.id = s.userid
 WHERE s.status = 'Active' AND s.lifetime = 0
 AND (
 CASE
	WHEN pbc.period_unit = 'Y' THEN datediff(date_add(lastpay_date , interval pbc.duration year), now())
	WHEN pbc.period_unit = 'M' THEN datediff(date_add(lastpay_date , interval pbc.duration month), now())
	WHEN pbc.period_unit = 'W' THEN datediff(date_add(lastpay_date , interval pbc.duration week), now())
	WHEN pbc.period_unit = 'D' THEN datediff(date_add(lastpay_date , interval pbc.duration day), now())
  END > 0

  OR ( expiration != '0000-00-00 00:00:00' AND
  CASE
	WHEN pbc.period_unit = 'Y' THEN datediff(date_add(expiration , interval pbc.duration year), now())
	WHEN pbc.period_unit = 'M' THEN datediff(date_add(expiration , interval pbc.duration month), now())
	WHEN pbc.period_unit = 'W' THEN datediff(date_add(expiration , interval pbc.duration week), now())
	WHEN pbc.period_unit = 'D' THEN datediff(date_add(expiration , interval pbc.duration day), now())
  END > 0
  )
)
 ");
$validSubsUserIds = $db->loadObjectList('userid');
//var_dump($validSubsUserIds);

//expire subs that have expired. Create fall back plan if required

	$db->setQuery("SELECT s.userid, u.username, s.lastpay_date, s.id AS subid, pbc.plan_name
FROM `#__fabrik_subs_subscriptions` AS s
INNER JOIN #__fabrik_subs_plans AS p ON p.id = s.plan
INNER JOIN #__fabrik_subs_plan_billing_cycle AS pbc ON pbc.id = s.billing_cycle_id
INNER JOIN #__users AS u ON u.id = s.userid
 WHERE s.status = 'Active' AND s.lifetime = 0 AND (
  CASE
	WHEN pbc.period_unit = 'Y' THEN datediff(date_add(lastpay_date , interval pbc.duration year), now())
	WHEN pbc.period_unit = 'M' THEN datediff(date_add(lastpay_date , interval pbc.duration month), now())
	WHEN pbc.period_unit = 'W' THEN datediff(date_add(lastpay_date , interval pbc.duration week), now())
	WHEN pbc.period_unit = 'D' THEN datediff(date_add(lastpay_date , interval pbc.duration day), now())
  END <= 0

  OR ( expiration != '0000-00-00 00:00:00' AND
  CASE
	WHEN pbc.period_unit = 'Y' THEN datediff(date_add(expiration , interval pbc.duration year), now())
	WHEN pbc.period_unit = 'M' THEN datediff(date_add(expiration , interval pbc.duration month), now())
	WHEN pbc.period_unit = 'W' THEN datediff(date_add(expiration , interval pbc.duration week), now())
	WHEN pbc.period_unit = 'D' THEN datediff(date_add(expiration , interval pbc.duration day), now())
  END <= 0
  )
)
ORDER BY s.lastpay_date
 ");

	$recalibratedUserIds = array();


	$ipn = new FabrikSubscriptionsIPN();
	$rows = $db->loadObjectList();
//var_dump($db->getQuery(), $rows);exit;
	$now = JFactory::getDate()->toSql();
	$sub = FabTable::getInstance('Subscription', 'FabrikTable');
	foreach ($rows as $row) {
		$sub->load($row->subid);
		$sub->status = 'Expired';
		$sub->eot_date = $now;
		echo "store Expired sub: " . $sub->id . " : " . $row->userid . " : " . $row->plan_name . "<br />\n";
		$sub->store();
	}

	foreach ($rows as $row)
	{
			echo "recalibrate user: " . $row->username . ' : ' . $row->lastpay_date;
			echo "<br />\n";
			$ipn->recalibrateUser($row->userid);
	}

	/*
	echo "<br />\nActive Subs<br />\n";
	foreach ($validSubsUserIds as $v)
	{
		//$ipn->recalibrateUser($v->userid);
		echo $v->username . "(" . $v->name . " - " . $v->email . ") : " . $v->plan_name;
		if ($v->recurring == 1)
		{
			echo " (recurring)";
		}
		echo "<br />\n";
	}

	echo "<br />\nValid subs total: " . count($validSubsUserIds) . "<br />\n";
	echo "Recalibrated total: " . count($recalibratedUserIds) . "<br />\n";

$db->setQuery("SELECT s.userid, u.username, s.lastpay_date, s.id AS subid, pbc.plan_name
FROM `#__fabrik_subs_subscriptions` AS s
INNER JOIN #__fabrik_subs_plans AS p ON p.id = s.plan
INNER JOIN #__fabrik_subs_plan_billing_cycle AS pbc ON pbc.id = s.billing_cycle_id
INNER JOIN #__users AS u ON u.id = s.userid
 WHERE s.status = 'Expired' AND s.lifetime = 0
 GROUP BY s.userid
 LIMIT 700,100
 ");
$rows = $db->loadObjectList();
foreach ($rows as $row) {
	echo "expired: " . $row->username . "<br />\n";
	$ipn->recalibrateUser($row->userid);
}
echo "total: " . count($rows);
	*/
	exit;
