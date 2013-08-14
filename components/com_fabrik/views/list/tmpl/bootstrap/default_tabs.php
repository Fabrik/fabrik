<?php
if (is_array($this->tabs) && !empty($this->tabs)) :
	$uri = JURI::getInstance();
	$thisUri = $uri->toString(array('path', 'query'));
?>
<div>
	<ul class="nav nav-tabs">
	<?php foreach ($this->tabs as $i => $value) :
		list($label, $url) = $value;
	?>
    <li <?php if ($thisUri == $url) echo 'class="active"' ?>><a href="<?php echo $url?>"><?php echo $label?></a></li>
	<?php endforeach; ?>
	</ul>
</div>
<?php endif; ?>
