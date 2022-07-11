<?php
/**
 * Slideshow vizualization: bootstrap template
 *
 * @package		Joomla.Plugin
 * @subpackage	Fabrik.visualization.slideshow
 * @copyright	Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license		GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$row = $this->row;

if($this->params->get('slideshow_viz_width', '400') === '0')
{
	$width = 'width:auto;';
	$height = 'max-height:'. $this->params->get('slideshow_viz_height', '300').'px;';
}
else if($this->params->get('slideshow_viz_height', '300') === '0')
{
	$width = 'max-width:'. $this->params->get('slideshow_viz_width', '400').'px;';
	$height = 'height:auto;';
}
else
{
	$width  = 'width:' . $this->params->get('slideshow_viz_width', '400') . 'px;';
	$height = 'height:' . $this->params->get('slideshow_viz_height', '300') . 'px;';
}

?>

<style>
    /**
     * These widths are set in the template default.php, as they need to derive values dynamically
     * from the plugin params.  All other CSS is in the viz's template.css, which can be overridden with a custom.css
     */
    .slider img {
        <?php echo $height; ?>
        <?php echo $width; ?>
    }

    .slider_loading {
        width: <?php echo $this->params->get('slideshow_viz_loader_width', '400');?>px;
        height: <?php echo $this->params->get('slideshow_viz_loader_height', '300');?>px;
    }
</style>

<div id="<?php echo $this->containerId;?>" class="fabrik_visualization">
	<?php if ($this->params->get('show-title', 1)) :?>
        <h1>
			<?php echo $row->label;?>
        </h1>
	<?php endif;?>
	<?php echo $this->loadTemplate('filter'); ?>
    <div>
		<?php echo $row->intro_text;?>
    </div>


    <div class="slideshow" id="slideshow_viz_<?php echo $row->id; ?>">
        <div class="slider_loading">
            <div class="slider_loader">
                <?php
                echo FabrikHelperHTML::image(
                    'ajax-loader.gif',
                    'form',
                    '',
                    array(
                        'alt' => FText::_('PLG_VISUALIZATION_SLIDESHOW_LOADING_MSG'),
                        'class' => 'slider_gif'
                    )
                );
                ?>
                <p />
                <?php echo FText::_('PLG_VISUALIZATION_SLIDESHOW_LOADING_MSG'); ?>
            </div>
        </div>

        <div class="slider" style="margin:auto;">
			<?php
			foreach ($this->slideData as $slide):
				?>
                <figure class="image">
                    <?php if ($this->params->get('slideshow_viz_links', '0') === '1'):
                        echo '<a target="_blank" href="' . $slide['fabrik_edit_url'] . '"/>';
                    elseif ($this->params->get('slideshow_viz_links', '0') === '2'):
                        echo '<a target="_blank" href="' . $slide['fabrik_view_url'] . '"/>';
                    endif; ?>
                    <img src="<?php echo $slide['href']; ?>" />
                    <?php if ($this->params->get('slideshow_viz_links', '0') !== '0'):
                        echo '</a>';
                    endif; ?>
                    <figcaption>
						<?php echo $slide['caption']; ?>
                    </figcaption>
                </figure>
			<?php
			endforeach;
			?>
        </div>

		<?php
		if ($this->params->get('slideshow_viz_thumbnails', false)):
			?>
            <div class="slider-nav">
				<?php
				foreach ($this->slideData as $slide):
					?>
                    <div class="image">
                        <img src="<?php echo $slide['thumbnail']; ?>" />
                    </div>
				<?php
				endforeach;
				?>
            </div>
		<?php
		endif;
		?>
    </div>
</div>
