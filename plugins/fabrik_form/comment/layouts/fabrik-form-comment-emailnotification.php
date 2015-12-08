<?php
defined('JPATH_BASE') or die;
$d = $displayData;

echo FText::_('PLG_FORM_COMMENT_NEW_COMMENT_ADDED') . "<br /><a href=\"{$d->row->url}\">" . FText::_('PLG_FORM_COMMENT_VIEW_COMMENT') . "</a>";
?>