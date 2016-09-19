<?php
/**
 * Default Form Template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/* The default template includes the following folder and files:

images - this is the folder for the form template's images
- plus-sign.png
- alert.png
- delete.png
default.php - this file controls the layout of the form
default_group.php - this file controls the layout of the individual form groups
default_relateddata.php - this file controls the layout of the forms related data
template_css.php - this file controls the styling of the form

CSS classes and id's included in this file are:

componentheading - used if you choose to display the page title
<h1> - used if you choose to show the form label
fabrikMainError -
fabrikError -
fabrikGroup -
groupintro -
fabrikSubGroup -
fabrikSubGroupElements -
fabrikGroupRepeater -
addGroup -
deleteGroup -
fabrikTip -
fabrikActions -

Other form elements that can be styled here are:

legend
fieldset

To learn about all the different elements in a basic form see http://www.w3schools.com/tags/tag_legend.asp.

If you have set to show the page title in the forms layout parameters, then the page title will show

*/

$form = $this->form;

if ($this->params->get('show_page_heading', 1)) : ?>
	<div class="componentheading<?php echo $this->params->get('pageclass_sfx')?>"><?php echo $this->escape($this->params->get('page_heading')); ?></div>
<?php
endif;

if ($this->params->get('show-title', 1)) :?>
	<h1>
		<?php echo $form->label;?>
	</h1>
<?php
endif;

// Form intro and start
echo $form->intro;
?>
<form method="post" <?php echo $form->attribs?>>
<?php
echo $this->plugintop;

// Error message
$active = ($form->error != '') ? '' : ' fabrikHide';
echo '<div class="fabrikMainError fabrikError' . $active . '">';
echo FabrikHelperHTML::image('alert.png', 'form', $this->tmpl);
echo "$form->error</div>";

// Buttons
if ($this->showEmail) :
	echo $this->emailLink;
endif;
if ($this->showPDF) :
	echo $this->pdfLink;
endif;
if ($this->showPrint) :
	echo $this->printLink;
endif;

// Related data template
echo $this->loadTemplate('relateddata');

// Loop over the form's groups rendering them
foreach ($this->groups as $group) :
	$this->group = $group;

	// Create the group fieldset ?>
	<fieldset class="fabrikGroup" id="group<?php echo $group->id;?>" style="<?php echo $group->css;?>">

	<?php
	// Do we add a legend?
	if (trim($group->title) !== '') :
	?>
		<legend class="legend">
			<span>
				<?php echo $group->title;?>
			</span>
		</legend>
	<?php
	endif;
	?>

	<?php
	// Show the group intro
	if ($group->intro !== '') :?>
		<div class="groupintro"><?php echo $group->intro ?></div>
	<?php
	endif;

	// Load the group template - this can be :
	//  * default_group.php - standard group non-repeating rendered as an unordered list
	//  * default_repeatgroup.php - repeat group rendered as an unordered list
	//  * default_repeatgroup_table.php - repeat group rendered in a table.

	$this->elements = $group->elements;
	echo $this->loadTemplate($group->tmpl);
	?>
</fieldset>
<?php
endforeach;

// Add the form's hidden fields
echo $this->hiddenFields;

// Add any content assigned by form plug-ins
echo $this->pluginbottom;

// Render the form's buttons
if ($this->hasActions) :?>
	<div class="fabrikActions"><?php echo $form->resetButton;?> <?php echo $form->submitButton;?>
	<?php echo $form->prevButton?> <?php echo $form->nextButton?>
	 <?php echo $form->applyButton;?>
	<?php echo $form->copyButton  . ' ' . $form->gobackButton . ' ' . $form->deleteButton . ' ' . $this->message ?>
	</div>
<?php
endif;
?>
</form>
<?php
echo $form->outro;
echo $this->pluginend;
echo FabrikHelperHTML::keepalive();
