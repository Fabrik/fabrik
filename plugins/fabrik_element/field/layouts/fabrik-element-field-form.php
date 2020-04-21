<?php

defined('JPATH_BASE') or die;

// Add span with id so that element fxs work.
$d = $displayData;

if ($d->scanQR) :
    $d->attributes['class'] .= ' qrcode-text';
	?>
    <style>
        .qrcode-text-btn {
            display: inline-block;
            height: 1em;
            width: 1em;
            background: url(<?php echo COM_FABRIK_LIVESITE; ?>media/com_fabrik/images/qr_icon.svg) 50% 50% no-repeat;
            cursor: pointer!important;
        }

        .qrcode-text-btn > input[type=file] {
            position: absolute;
            overflow: hidden;
            width: 1px;
            height: 1px;
            opacity: 0;
        }

        .qrcode-text {
            padding-right: 1.7em;
            margin-right: 0;
            vertical-align: middle;
        }

        .qrcode-text + .qrcode-text-btn {
            width: 1.7em;
            margin-left: -1.7em;
            vertical-align: middle;
        }
    </style>
<?php
endif;
?>

<input
	<?php foreach ($d->attributes as $key => $value) :
	echo $key . '="' . $value . '" ';
endforeach;
	?> /><?php
if ($d->scanQR) :
?><label class=qrcode-text-btn>
		<input type=file
		       accept="image/*"
		       capture=environment
		       tabindex=-1
		       id="<?php echo $d->attributes['id'] . '_qr_upload'; ?>"
		>
	</label>
<?php
	endif;
