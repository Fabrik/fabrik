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
 * This is a wrapper class that is useful for when you want to track commands
 * for audit or debug purposes.
 *
 * @package  Clickatell
 * @author   Chris Brand <chris@cainsvault.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/arcturial
 */
class ClickatellEvent implements TransportInterface
{
    private $request = array();
    private $response = array();
    private $transport;

    /**
     * Construct a new event handler.
     *
     * @param TransportInterface $transport The adapter to use
     */
    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * Trigger a specific request event
     *
     * @param string $event The event name
     * @param object $args  The arguments
     *
     * @return ClickatellEvent
     */
    private function request($event, $args)
    {
        foreach ($this->request as $callable) {
            call_user_func_array($callable, array($event, $args));
        }

        return $this;
    }

    /**
     * Trigger a specific response event
     *
     * @param string $event The event name
     * @param object $obj   The response
     *
     * @return object
     */
    private function response($event, $obj)
    {
        foreach ($this->response as $callable) {
            call_user_func_array($callable, array($event, $obj));
        }

        return $obj;
    }

    /**
     * Add a new event listener for requests.
     *
     * @param callable $callable The callback
     *
     * @return ClickatellEvent
     */
    public function onRequest($callable)
    {
        $this->request[] = $callable;
        return $this;
    }

    /**
     * Register a response listener.
     *
     * @param callable $callable The callback
     *
     * @return ClickatellEvent
     */
    public function onResponse($callable)
    {
        $this->response[] = $callable;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function sendMessage($to, $message, $extra = array())
    {
        $event = __FUNCTION__;
        $args = (object) array(
            'to'        => $to,
            'message'   => $message,
            'extra'     => $extra
        );

        $res = $this->request($event, $args)->transport->sendMessage($args->to, $args->message, $args->extra);
        return $this->response($event, $res);
    }

    /**
     * {@inheritdoc}
     */
    public function getBalance()
    {
        $event = __FUNCTION__;
        $args = (object) array();

        $res = $this->request($event, $args)->transport->getBalance();
        return $this->response($event, $res);
    }

    /**
     * {@inheritdoc}
     */
    public function stopMessage($apiMsgId)
    {
        $event = __FUNCTION__;
        $args = (object) array(
            'apiMsgId' => $apiMsgId
        );

        $res = $this->request($event, $args)->transport->stopMessage($args->apiMsgId);
        return $this->response($event, $res);
    }

    /**
     * {@inheritdoc}
     */
    public function queryMessage($apiMsgId)
    {
        $event = __FUNCTION__;
        $args = (object) array(
            'apiMsgId' => $apiMsgId
        );

        $res = $this->request($event, $args)->transport->queryMessage($args->apiMsgId);
        return $this->response($event, $res);
    }

    /**
     * {@inheritdoc}
     */
    public function routeCoverage($msisdn)
    {
        $event = __FUNCTION__;
        $args = (object) array(
            'msisdn' => $msisdn
        );

        $res = $this->request($event, $args)->transport->routeCoverage($args->msisdn);
        return $this->response($event, $res);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageCharge($apiMsgId)
    {
        $event = __FUNCTION__;
        $args = (object) array(
            'apiMsgId' => $apiMsgId
        );

        $res = $this->request($event, $args)->transport->getMessageCharge($args->apiMsgId);
        return $this->response($event, $res);
    }
}