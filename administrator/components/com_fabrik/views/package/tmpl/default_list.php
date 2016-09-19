<?php
/**
 * Admin Package list Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<ul class="adminformlist">
<?php foreach ($this->listform->getFieldset('details') as $field): ?>
<li>
	<?php echo $field->label . $field->input; ?>
</li>
<?php endforeach; ?>
</ul>