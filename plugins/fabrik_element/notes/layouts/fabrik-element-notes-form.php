<?php
defined('JPATH_BASE') or die;

$d = $displayData;
?>
<?php

/*
 * If detail view, we'll get a div with the ID wrapped around us automagically, so don't want to dupe the ID.
* In form view, the ID element would usually be an input, but we don't actually submit anything with the form
* in the notes plugin, we just need something with the ID on it to keep the addElements() form init happy
*/

foreach ($d->rows as $row) :
	$d->labels[] = $d->model->getDisplayLabel($row);
endforeach;

if ($d->editable) :
	?>
	<div id="<?php echo $d->id;?>">
<?php
endif;
?>
	<div style="overflow:auto;height:150px;" class="well well-small row-striped">
		<?php
		foreach ($d->labels as $label) :
            echo $label;
		endforeach;
		?>
	</div>
	<div class="noteHandle" style="height:3px;"></div>

<?php
if ($d->canUse) :
    // Jaanus - Submitting notes before saving form data results with the notes belonging to nowhere but new, not submitted forms.
    if ($d->primaryKey > 0) :
        if ($d->fieldType == 'field') :?>
            <input class="fabrikinput inputbox text span12" name="<?php echo $d->name; ?>" />
        <?php
        else:
            ?>
            <textarea class="fabrikinput inputbox text span12" name="<?php echo $d->name; ?>" cols="50" rows="3" /></textarea>
        <?php
        endif;
        ?>

        <input type="button" class="button btn" value="<?php echo FText::_('PLG_ELEMENT_NOTES_ADD');?>" />
    <?php
    else :
        echo FText::_('PLG_ELEMENT_NOTES_SAVEFIRST');
    endif;
endif;
?>

<?php
if ($d->editable) :
	?>
	</div>
<?php
endif;
