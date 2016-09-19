<?php
/**
 * Contacts Custom Form Template: Group
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>

<?php
/*
 * This is where you will do your main template modifications.
 *
 */
?>

<?php
/*
 * This code sets up your first group.
 */
	reset($this->groups);
	$this->group = current($this->groups);
	$this->elements = $this->group->elements;
?>

<?php
/*
 * Now we output the first group.  First a standard frameset, with id and
 * style info.
 */
?>
	<div class="fabrikGroup" id="group<?php echo $this->group->id;?>" style="<?php echo $this->group->css;?>">
	<h3 class="legend">
		<span>
			<?php echo $group->title;?>
		</span>
	</h3>

	<?php if ($this->group->intro !== '') {?>
	<div class="groupintro"><?php echo $this->group->intro ?></div>
	<?php }?>
<?php
/*
 * This is the meat of the customization, that allows you to place and
 * format your elements on the page.  In this example, we're pretty much
 * just duplicating the standard 'default' template layout, but doing it
 * by placing each individual element, one by one.  You can get as creative
 * as you want in your HTML formatting.
 *
 * The important thing is the two PHP lines for each element:
 *
 *    $this->element = $this->elements['short_element_name'];
 *    echo $this->loadTemplate('element');
 *
 * ... which is what actually renders each individual element.  Note
 * that this is one of the few places in Fabrik where you use the short
 * element name (like 'first_name') instead of the full element name
 * (like 'jos__fb_contact_sample___first_name').
 */
?>
		<div class="example">
			<?php
			$this->element = $this->elements['first_name'];
			echo $this->loadTemplate('element');
			?>
		</div>

		<div class="example">
			<?php
			$this->element = $this->elements['last_name'];
			echo $this->loadTemplate('element');
			?>
		</div>

		<div class="example">
			<?php
			$this->element = $this->elements['email'];
			echo $this->loadTemplate('element');
			?>
		</div>

	</div>

<?php
/*
 * This chunk of code selects the next (in this case second) group ... for
 * each group you want to work with, you need to put this chunk of code
 * to set up $this->group for the display code.
 */
	$this->group = next($this->groups);
	$this->elements = $this->group->elements;
?>

	<div class="fabrikGroup" id="group<?php echo $this->group->id;?>" style="<?php echo $this->group->css;?>">
        <h3 class="legend">
			<span>
				<?php echo $group->title;?>
			</span>
		</h3>
		<div class="example">
			<?php
			$this->element = $this->elements['message'];
			echo $this->loadTemplate('element');
			?>
		</div>
	</div>

<?php
/* This must be the last thing that happens in this template.  It adds
 * all hidden elements to the form, and also finds any non-hidden elements
 * which haven't been displayed, and adds them as hidden elements (this
 * prevents JavaScript errors where element handler code can't find the actual
 * DOM structures for their elements)
 */
	echo $this->loadTemplate('group_hidden');
?>
