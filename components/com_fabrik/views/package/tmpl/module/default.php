<?php
/**
 * Module Package Template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

//$i = 0;
//foreach($this->blocks as $key => $block) {
//	echo "<div class='fabrik_block fabrik_block_col" . $i % 2 . "'>";
//	echo $block;
//	echo "</div>";
//
//}
echo "<div class='fabrik_block fabrik_block_col0'>";
echo current($this->blocks);
echo "</div>";
echo "<div class='fabrik_block fabrik_block_col1' style='display:none'>";
next($this->blocks);
echo current($this->blocks);
next($this->blocks);
echo current($this->blocks);
echo "</div>";
?>

