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
 * This event class is used by the ClickatellEvent handler.
 *
 * @package  Clickatell
 * @author   Chris Brand <chris@cainsvault.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/arcturial
 */
class Event
{
    const SEND_MESSAGE        = 0;
    const GET_BALANCE         = 1;
    const STOP_MESSAGE        = 2;
    const QUERY_MESSAGE       = 3;
    const ROUTE_COVERAGE      = 4;
    const GET_MESSAGE_CHARGE  = 5;

    /**
     * Construct a new event.
     *
     * @param string $event The event name
     * @param array  $args  Associative array of arguments
     */
    public function __construct($event, $args)
    {
        $this->event = $event;

        foreach ($args as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }
}