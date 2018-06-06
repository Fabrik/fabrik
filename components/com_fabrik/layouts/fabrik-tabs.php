<?php
/**
 * Tabs layout
 */

defined('JPATH_BASE') or die;

$d = $displayData;
$i = 0;
?>

<ul class="nav nav-tabs" role="tablist">
	<?php foreach ($d->tabs as $tab) :
		$style = array();
		$style[] = isset($tab->class) && $tab->class !== '' ? 'class="' . $tab->class . '"' : '';
		$style[] = isset($tab->css) && $tab->css !== '' ? 'style="' . $tab->css . '"': '';
		$href = isset($tab->href) ? $tab->href : $tab->id;
		?>
		<li role="presentation" data-role="fabrik_tab" <?php echo implode(' ', $style); ?>>

			<?php if (isset($tab->js) && $tab->js === false) : ?>

				<a href="<?php echo $href; ?>">
					<?php echo FText::_($tab->label); ?>
				</a>

			<?php
			else :
			?>
			<a href="#<?php echo $href; ?>"
				aria-controls="<?php echo $tab->id; ?>"
				role="tab"
				data-toggle="tab"
				class="mootools-noconflict">
				<?php echo FText::_($tab->label); ?>
			</a>
				<?php endif;
			?>
		</li>
		<?php
		$i++;
	endforeach;
	?>
</ul>

