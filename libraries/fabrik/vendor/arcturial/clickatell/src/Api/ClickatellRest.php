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

namespace Clickatell\Api;

use Clickatell\Clickatell;
use Clickatell\Diagnostic;

/**
 * The REST API usage class.
 *
 * @package  Clickatell\Api
 * @author   Chris Brand <chris@cainsvault.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/arcturial
 */
class ClickatellRest extends Clickatell
{
    private $token;

    /**
     * Construct a new REST API connection.
     *
     * @param string $token The auth token
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Return the headers that will be used with every REST
     * request.
     *
     * @return array
     */
    private function getHeaders()
    {
        return array(
            "Authorization: Bearer " . $this->token,
            "Content-Type: application/json",
            "X-Version: 1",
            "Accept: application/json"
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function get($uri, $args, $method = self::HTTP_GET)
    {
        $data = json_encode($args);
        $response = $this->curl($uri, $data, $this->getHeaders(), $method);
        return $response->decodeRest();
    }

    /**
     * {@inheritdoc}
     */
    public function sendMessage($to, $message, $extra = array())
    {
        $extra['to'] = (array) $to;
        $extra['text'] = $message;
        $args = $this->getSendDefaults($extra);

        // The "to" field only accepts strings as numbers. We will take all the
        // values and map them into strings.
        $args['to'] = array_map(function ($value) {
            return (string) $value;
        }, $args['to']);

        try {
            $response = $this->get('rest/message', $args, self::HTTP_POST);
        } catch (Exception $e) {

            $response = array(
                'error' => $e->getMessage(),
                'errorCode' => $e->getCode()
            );
        }

        $return = array();

        // According to the documentation, we can pretty much assume that
        // a response from "rest/message" will contain a "message" key with an
        // array of messages in it.
        foreach ($response['message'] as $entry) {

            $return[] = (object) array(
                'id'            => (isset($entry['apiMessageId'])) ? $entry['apiMessageId'] : false,
                'destination'   => (isset($entry['to'])) ? $entry['to'] : $args['to'],
                'error'         => (isset($entry['error'])) ? $entry['error']['description'] : false,
                'errorCode'     => (isset($entry['code'])) ? $entry['error']['code'] : false
            );
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function getBalance()
    {
        $response = $this->get('rest/account/balance', array());

        return (object) array(
            'balance' => (float) $response['balance']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function queryMessage($apiMsgId)
    {
        return $this->getMessageCharge($apiMsgId);
    }

    /**
     * {@inheritdoc}
     */
    public function routeCoverage($msisdn)
    {
        $response = $this->get('rest/coverage/' . $msisdn, array());

        return (object) array(
            'routable'      => $response['routable'],
            'destination'   => $response['destination'],
            'charge'        => (float) $response['minimumCharge']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageCharge($apiMsgId)
    {
        $response = $this->get('rest/message/' . $apiMsgId, array());

        return (object) array(
            'id'            => $response['apiMessageId'],
            'status'        => $response['messageStatus'],
            'description'   => Diagnostic::getStatus($response['messageStatus']),
            'charge'        => (float) $response['charge']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function stopMessage($apiMsgId)
    {
        $response = $this->get('rest/message/' . $apiMsgId, array(), self::HTTP_DELETE);

        return (object) array(
            'id'            => $response['apiMessageId'],
            'status'        => $response['messageStatus'],
            'description'   => Diagnostic::getStatus($response['messageStatus']),
        );
    }
}