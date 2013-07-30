<?php
/**
 * This is a sample email template. It will just print out all of the request data:
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.email
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
?>
<table border="1">
<?php
foreach ($this->data as $key => $val)
{

	echo '<tr><td>' . $key . '</td><td>';
	if (is_array($val)) :
		foreach ($val as $v):
			if (is_array($v)) :
				echo implode("<br>", $v);
			else:
				echo implode("<br>", $val);
			endif;
		endforeach;
	else:
		echo $val;
	endif;
	echo "</td></tr>";
}
?>
</table>
