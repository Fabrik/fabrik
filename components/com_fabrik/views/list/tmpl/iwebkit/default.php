<?php
  	$document = JFactory::getDocument();
  	$document->setMetaData("apple-mobile-web-app-capable", "yes");
  	$document->setMetaData("viewport", "minimum-scale=1.0, width=device-width, maximum-scale=0.6667, user-scalable=no");
  	$document->addStyleSheet('components/com_fabrik/views/list/tmpl/iwebkit/css/style.css');
  	$document->addScript('components/com_fabrik/views/list/tmpl/iwebkit/javascript/functions.js');
		$document->addHeadLink('components/com_fabrik/views/list/tmpl/iwebkit/homescreen.png', 'apple-touch-icon');
		$document->addHeadLink('components/com_fabrik/views/list/tmpl/iwebkit/startup.png', 'apple-touch-startup-image');

		$script = "head.ready(function() {
			document.getElement('body').addClass('list');
		});";
		$document->addScriptDeclaration($script);

		?>
		<div id="topbar">
  <div id="title">

		<?php
if ($this->params->get('show_page_title', 1)) { ?>
	<div class="componentheading<?php echo $this->params->get('pageclass_sfx')?>"><?php echo $this->escape($this->params->get('page_title')); ?></div>
<?php } ?>

<?php if ($this->params->get('show-title', 1)) {?>
	<?php echo $this->table->label;?>
<?php }?>
</div>
</div>
<?php echo $this->table->intro;?>
<form class="fabrikForm" action="<?php echo $this->table->action;?>" method="post" id="<?php echo $this->formid;?>" name="fabrikList">

<?php echo $this->loadTemplate('buttons');

if ($this->showFilters) {
//	echo $this->loadTemplate('filter');
}?>

<div class="emptyDataMessage" style="<?php echo $this->emptyStyle?>"><?php echo $this->emptyDataMessage; ?></div>
<div class="fabrikDataContainer content" style="<?php echo $this->tableStyle?>">
<ul class="fabrikList" id="list_<?php echo $this->table->renderid;?>" >
<?php
	foreach ($this->rows as $groupedby => $group) {
		if ($this->isGrouped) {
			echo "<li class=\"title\">".$this->grouptemplates[$groupedby]."</li>";
		}

		foreach ($group as $this->_row) {
			echo $this->loadTemplate('row');
	 	}
	 }
		?>
		</ul>
	<?php
	echo $this->nav;
	print_r($this->hiddenFields);
?>
</div>
</form>
<?php echo $this->table->outro;?>

<?php $doc = JFactory::getDocument();
//$style = $this->params->get('mobile_image') == '' ? 'musiclist' : 'list';
$style = 'list';
$doc->addScriptDeclaration("head.ready(function(){
document.body.addClass('$style');
})");

if ($this->params->get('mobile_image') == '') {
	FabrikHelperHTML::addStyleDeclaration('body.list li.withimage .comment,
	body.list li.withimage .name{
	margin-left:10px !important
	}');
}
?>