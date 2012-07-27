<div id="<?php echo $this->_row->id;?>" class="<?php echo $this->_row->class;?>">
	<ul>
	<?php foreach ($this->headings as $heading => $label) {	
		$style = empty($this->cellClass[$heading]['style']) ? '' : 'style="'.$this->cellClass[$heading]['style'].'"';?>
		<li class="<?php echo $this->cellClass[$heading]['class']?>" <?php echo $style?>>
			<span class="divlabel"><?php echo $label;?>:</span>
			<?php echo @$this->_row->data->$heading;?>
		</li>
	<?php }?>
	</ul>
</div>