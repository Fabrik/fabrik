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
 * This interface defines the required function for Transport handlers.
 * It also specifies the supported API calls.
 *
 * @package  Clickatell
 * @author   Chris Brand <chris@cainsvault.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/arcturial
 */
interface TransportInterface
{
    /**
     * API call for "sendMessage".
     *
     * Response format:
     *      id      => string|false
     *      to      => string
     *      error   => string|false
     *      code    => string|false
     *
     * @param array   $to       The recipient list
     * @param string  $message  Message
     * @param array   $extra    Extra parameters (based on Clickatell documents)
     *
     * @return array
     */
    public function sendMessage($to, $message, $extra = array());

    /**
     * API call for "getBalance".
     *
     * Response format:
     *      balance => int
     *
     * @throws Exception
     *
     * @return int
     */
    public function getBalance();

    /**
     * API call for "stop message".
     *
     * @param string $apiMsgId ApiMsgId to query
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function stopMessage($apiMsgId);

    /**
     * API call for "queryMsg".
     *
     * @param string $apiMsgId ApiMsgId to query
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function queryMessage($apiMsgId);

    /**
     * API call for "routeCoverage".
     *
     * @param int $msisdn Number to check for coverage
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function routeCoverage($msisdn);

    /**
     * API call for "getMsgCharge".
     *
     * @param string $apiMsgId ApiMsgId to query
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function getMessageCharge($apiMsgId);
}