<?php
/**
 * Fabrik List Template: Custom Example Row
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$class = 'badge';
$hits = @$this->_row->data->downloads___hits;
if ($hits > 400)
{
	$class .= ' badge-important';
}
?>
<div class="row-fluid">
	<div class="span12">
		<div class="row-fluid">
			<div class="span12">
				<h3>
					<?php echo @$this->_row->data->downloads___title; ?>
				</h3>
				<div class="version">
					<span class="label label-info"><?php echo @$this->_row->data->downloads___version; ?>
				</span>
			</div>
		</div>
		</div>
		<div class="row-fluid">
			<div class="span3">
				<ul>
					<li>
						<small><?php echo @$this->_row->data->downloads___joomla_version;?></small>
					</li>
					<li>
						<small>Type: <?php echo @$this->_row->data->downloads___type;?></small>
					</li>
					<li>
						<small>Hits: <span class="<?php echo $class ?>"><?php echo $hits; ?></span></small></li>
					<li>
						<small>Updated: <?php echo @$this->_row->data->downloads___create_date;?></small>
					</li>
				</ul>
			</div>
			<div class="span6">
				<?php echo @$this->_row->data->downloads___description;?>
			</div>
			<div class="span3">
				<ul>
					<li class="pull-right"><?php echo @$this->_row->data->downloads___like;?></li>
					<li style="clear:right" class="pull-right"><?php echo @$this->_row->data->downloads___download;?></li>
				</ul>
			</div>
		</div>
	</div>
</div>
<hr />