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
use Exception;

/**
 * The HTTP API usage class.
 *
 * @package  Clickatell\Api
 * @author   Chris Brand <chris@cainsvault.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/arcturial
 */
class ClickatellHttp extends Clickatell
{
    private $username;
    private $password;
    private $apiId;

    /**
     * Construct a new HTTP API connection.
     *
     * @param string $username The username
     * @param string $password The password
     * @param int    $apiId    The clickatell API ID
     */
    public function __construct($username, $password, $apiId)
    {
        $this->username = $username;
        $this->password = $password;
        $this->apiId = $apiId;
    }

    /**
     * {@inheritdoc}
     */
    protected function get($uri, $args, $method = self::HTTP_GET)
    {
        $args = array_merge(
            $args,
            array(
                'user'      => $this->username,
                'password'  => $this->password,
                'api_id'    => $this->apiId
            )
        );

        $query = http_build_query($args);
        return $this->curl($uri, $query, array(), $method)->unwrapLegacy();
    }

    /**
     * {@inheritdoc}
     */
    public function sendMessage($to, $message, $extra = array())
    {
        $extra['to'] = implode(",", (array) $to);
        $extra['text'] = $message;
        $args = $this->getSendDefaults($extra);

        try {
            $response = $this->get('http/sendmsg', $args);
        } catch (Exception $e) {

            $response = array(
                'error' => $e->getMessage(),
                'errorCode' => $e->getCode()
            );
        }

        !is_int(key($response)) && $response = array($response);
        $return = array();

        // We won't throw any exceptions if an error occurs since we could have
        // multiple messages in the packet and not all of them might have failed.
        foreach ($response as $entry) {

            $return[] = (object) array(
                'id'            => (isset($entry['ID'])) ? $entry['ID'] : false,
                'destination'   => (isset($entry['To'])) ? $entry['To'] : $args['to'],
                'error'         => (isset($entry['error'])) ? $entry['error'] : false,
                'errorCode'     => (isset($entry['code'])) ? $entry['code'] : false
            );
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function getBalance()
    {
        $response = $this->get('http/getbalance', array());

        return (object) array(
            'balance' => (float) $response['Credit']
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
        try {

            $response = $this->get('utils/routeCoverage', array('msisdn' => $msisdn));

            return (object) array(
                'routable'      => true,
                'destination'   => $msisdn,
                'charge'        => $response['Charge']
            );

        } catch (Exception $exception) {

            return (object) array(
                'routable'      => false,
                'destination'   => $msisdn,
                'charge'        => 0
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageCharge($apiMsgId)
    {
        $response = $this->get('http/getmsgcharge', array('apimsgid' => $apiMsgId));

        return (object) array(
            'id'            => $apiMsgId,
            'status'        => $response['status'],
            'description'   => Diagnostic::getStatus($response['status']),
            'charge'        => (float) $response['charge']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function stopMessage($apiMsgId)
    {
        $response = $this->get('http/delmsg', array('apimsgid' => $apiMsgId));

        return (object) array(
            'id'            => $response['ID'],
            'status'        => $response['Status'],
            'description'   => Diagnostic::getStatus($response['Status']),
        );
    }
}