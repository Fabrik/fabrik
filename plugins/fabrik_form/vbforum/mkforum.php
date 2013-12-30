<?php
/**
 * Make a VBulletin forum
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.vbulletin
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

error_reporting(E_ALL & ~E_NOTICE);

define('THIS_SCRIPT', 'mkforum');

require './global.php';

/*if (!is_member_of($vbulletin->userinfo, 5, 6, 7, 10))
{
	print_no_permission();
}*/

$forum_name = $vbulletin->input->clean_gpc('p', 'forum_name', TYPE_NOHTML);
$forum_parent = $vbulletin->input->clean_gpc('p', 'forum_parent', TYPE_UINT);

if ($forum_name && $forum_parent)
{
	require_once DIR . '/includes/functions_bigthree.php';
	$dataman = datamanager_init('Forum', $vbulletin, ERRTYPE_ARRAY);
	$dataman->set('title', $forum_name);
	$dataman->set('title_clean', $forum_name);
	$dataman->set('parentid', $forum_parent);
	$dataman->set('displayorder', 1);
	$dataman->set('daysprune', -1);
	$dataman->set_bitfield('options', 'cancontainthreads', true);
	$dataman->set_bitfield('options', 'active', true);
	$dataman->set_bitfield('options', 'allowposting', true);
	$dataman->set_bitfield('options', 'indexposts', true);
	$dataman->set_bitfield('options', 'allowbbcode', true);
	$dataman->set_bitfield('options', 'allowimages', true);
	$dataman->set_bitfield('options', 'allowsmilies', true);
	$dataman->set_bitfield('options', 'allowicons', true);
	$dataman->set_bitfield('options', 'allowratings', true);
	$dataman->set_bitfield('options', 'countposts', true);
	$dataman->set_bitfield('options', 'showonforumjump', true);

	$dataman->pre_save();

	if (count($dataman->errors) > 0)
	{
		echo "Forum not created because: ";

		foreach ($dataman->errors as $err)
		{
			echo "$err; ";
		}
	}
	else
	{
		$forumid = $dataman->save();
		echo "Forum created successfully";
	}
}
