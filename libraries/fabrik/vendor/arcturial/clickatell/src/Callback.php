<?php
/**
 * The Clickatell SMS Library provides a standardised way of talking to and
 * receiving replies from the Clickatell API's.
 *
 * PHP Version 5.3
 *
 * @package  Clickatell
 * @author   Chris Brand <chris@cainsvault.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/arcturial
 */

namespace Clickatell;

/**
 * This class provides helper functionality to parse Clickatell MT and MO callbacks.
 * The callback functional will only trigger if the callback contains the relevant parameters.
 *
 * @package  Clickatell
 * @author   Chris Brand <chris@cainsvault.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/arcturial
 */
class Callback
{
    /**
     * Triggers if a clickatell MT callback has been received by the page.
     *
     * @param callable $callable The callable function
     *
     * @return boolean
     */
    public static function parseCallback($callable)
    {
        $required = array_flip(
            array(
                'apiMsgId',
                'cliMsgId',
                'to',
                'timestamp',
                'from',
                'status',
                'charge'
            )
        );

        $values = array_intersect_key($_GET, $required);
        $diff = array_diff_key($required, $values);

        // If there are no difference, then it means the callback
        // passed all the required values.
        if (empty($diff)) {
            return call_user_func_array($callable, array($values));
        }

        return false;
    }
}