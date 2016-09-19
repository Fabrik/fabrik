<?php
/**
 * iwebkit Form Template - Default
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$document = JFactory::getDocument();
$document->setMetaData("apple-mobile-web-app-capable", "yes");
$document->setMetaData("viewport", "minimum-scale=1.0, width=device-width, maximum-scale=0.6667, user-scalable=no");
$document->addStyleSheet('components/com_fabrik/views/form/tmpl/iwebkit/css/style.css');
$document->addScript('components/com_fabrik/views/form/tmpl/iwebkit/javascript/functions.js');
$document->addHeadLink('components/com_fabrik/views/form/tmpl/iwebkit/homescreen.png', 'apple-touch-icon');
$document->addHeadLink('components/com_fabrik/views/form/tmpl/iwebkit/startup.png', 'apple-touch-startup-image');
?>
<script>
window.addEvent('fabrik.loaded', function() {

	document.getElements('legend').each(function(f){
		var s = new Element('span', {'class':'graytitle'}).set('text', f.get('text'));
		s.inject(f, 'after');
		f.dispose();
	});
	document.getElements('.date').each(function(f){
		f.getElement('input[id*=_cal').set('type', 'date');
	});
	document.getElements('.field, .date, .mode-auto-complete, .timer').each(function(f) {
		var l = f.getElement('label');
		var picker = f.getElement('.picker');
		if(picker){picker.dispose();}
		var li = new Element('li', {'class':'smallfield'});
		var span = new Element('span', {'class':'name'});
		if (typeOf(f.getElement('input')) !== 'null') {
			f.getElement('input').setProperty('placeholder', 'Enter text');

			if (typeOf(l) !== 'null'){
				var label = l.get('text');
				l.dispose();
			}else{
				label = '';
			}
			span.set('text', label);
			new Element('ul',{'class':'pageitem'}).adopt(li.adopt([span, f.getElement('input')])).injectInside(f);
		}else{
			new Element('ul',{'class':'pageitem'}).adopt(li.adopt(span)).injectInside(f);
		}
	});

	document.getElements('.radiobutton, .yesno').each(function(f) {
		var lis = [];
		var li;
		var x = f.clone();
		x.getElements('label').dispose();
		var span = new Element('span', {'class':'graytitle'});
		span.set('text', x.get('text'));

		f.getElements('label').each(function(s) {
			li = new Element('li', {'class':'radiobutton'}).adopt(s.clone());
			li.getElement('span').addClass('name');
			lis.push(li);
			if(typeOf(s) !== 'null'){
				s.dispose();
			}
		});
		f.empty();
		new Element('ul',{'class':'pageitem'}).adopt([span, lis]).injectInside(f);
	});

	document.getElements('.checkbox').each(function(f) {
		var lis = [];
		var li;
		f.getElement('.fabrikLabel').addClass('graytitle');
		f.getElements('.fabrik_subelement').each(function(s) {
			li = new Element('li', {'class':'checkbox'}).adopt([new Element('span', {'class':'name'}).set('text', s.getElement('span').get('text')), s.getElement('input')]);
			lis.push(li);
			s.dispose();
		});
		new Element('ul',{'class':'pageitem'}).adopt(lis
		).injectInside(f);
	});

	document.getElements('.cascadingdropdown, .dropdown, .mode-dropdown').each(function(f) {
		if(f.getElement('.fabrikLabel')){
			f.getElement('.fabrikLabel').addClass('graytitle');
		}
		var inputs = f.getElements('select', 'input');
		inputs.push(new Element('span', {'class':'arrow'}));
		var li = new Element('li', {'class':'select'}).adopt(inputs.push);
		f.getChildren().dispose();
		new Element('ul',{'class':'pageitem'}).adopt(li).injectInside(f);
	});

	document.getElements('.textarea').each(function(f) {
		var l = f.getElement('label').get('text');
		var span = new Element('span', {'class':'header'}).set('text', l);

		var li = new Element('li', {'class':'textbox'}).adopt([span, f.getElement('textarea')]);
		f.getChildren().dispose();
		new Element('ul',{'class':'pageitem'}).adopt(li).injectInside(f);
	});

	/*document.getElements('.fabrikLabel').each(function(l) {
		var s = new Element('span', {id:l.id, 'class':l.className}).adopt(l.getChildren());

		s.replaces(l);
	});*/
});

</script>


<?php $form = $this->form;
if ($this->params->get('show-title', 1)) {?>
<div id="topbar">
  <div id="title"><?php echo $form->label;?></div>
</div>
<?php }
echo $form->intro;
?>
<form method="post" <?php echo $form->attribs?>>
<?php
echo $this->plugintop;
$active = ($form->error != '') ? '' : ' fabrikHide';
echo "<div class=\"fabrikMainError fabrikError$active\">$form->error</div>";?>
	<?php
	if ($this->showEmail) {
		echo $this->emailLink;
	}
	if ($this->showPDF) {
		echo $this->pdfLink;
	}
	if ($this->showPrint) {
		echo $this->printLink;
	}
	echo $this->loadTemplate('relateddata');
	foreach ($this->groups as $group) {
		?>
		<fieldset class="fabrikGroup" id="group<?php echo $group->id;?>">
		<legend><?php echo $group->title;?></legend>
		<?php if ($group->intro !== '') {?>
		<div class="groupintro"><?php echo $group->intro ?></div>
		<?php }?>
		<?php if ($group->canRepeat) {
			foreach ($group->subgroups as $subgroup) {
			?>
				<div class="fabrikSubGroup">
					<div class="fabrikSubGroupElements">
						<?php
						$this->elements = $subgroup;
						echo $this->loadTemplate('group');
						?>
					</div>
					<?php if ($group->editable) { ?>
						<div class="fabrikGroupRepeater">
							<?php if ($group->canAddRepeat) {?>
							<a class="addGroup" href="#">
								<?php echo FabrikHelperHTML::image('add.png', 'form', $this->tmpl, FText::_('COM_FABRIK_ADD_GROUP'));?>
							</a>
							<?php }?>
							<?php if ($group->canDeleteRepeat) {?>
							<a class="deleteGroup" href="#">
								<?php echo FabrikHelperHTML::image('del.png', 'form', $this->tmpl, FText::_('COM_FABRIK_DELETE_GROUP'));?>
							</a>
							<?php }?>
						</div>
					<?php } ?>
				</div>
				<?php
			}
		} else {
			$this->elements = $group->elements;
			echo $this->loadTemplate('group');
		}	// Show the group outro
	if ($group->outro !== '') :?>
		<div class="groupoutro"><?php echo $group->outro ?></div>
	<?php
	endif;
	?>

	</fieldset>
<?php
	}
	echo $this->hiddenFields;
	?>
	<?php echo $this->pluginbottom; ?>
	<div class="fabrikActions">

	<ul class="pageitem">
	<?php $buttons = array('resetButton', 'submitButton', 'applyButton', 'copyButton', 'deleteButton', 'gobackButton');
	foreach($buttons as $b) {
	if (isset($form->$b) && trim($form->$b) !== '') {
		?>
		<li class="button">
    <?php echo $form->$b;?>
  </li>
		<?php
	}
	}?>
</ul>

	<?php echo $this->message ?>
</form>
<?php
echo $form->outro;
echo $this->pluginend;
echo FabrikHelperHTML::keepalive();
?>
</div>