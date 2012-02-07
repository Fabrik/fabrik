<?php if (!empty($this->linkedTables)) {?>
	<ul class='linkedTables'>
		<?php foreach ($this->linkedTables as $a) { ?>
		<li>
			<?php echo implode(" ", $a);?>
			</li>
		<?php }?>
	</ul>
<?php }?>