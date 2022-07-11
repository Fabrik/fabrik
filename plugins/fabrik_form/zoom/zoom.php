<?php
/**
 * Submit or update data to a REST service
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.rest
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\ArrayHelper;
use Fabrik\Helpers\StringHelper;
use \Firebase\JWT\JWT;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use GuzzleHttp\Client;
use Fabrik\Helpers\Worker;

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';
require_once JPATH_ROOT . '/plugins/fabrik_form/zoom/vendor/autoload.php';

/**
 * Submit or update data to a REST service
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.rest
 * @since       3.0
 */
class PlgFabrik_FormZoom extends PlgFabrik_Form
{
	/**
	 * @var GuzzleHttp\Client
	 */
	private $client;

    /**
     * @var string
     */
	private $endpoint = 'https://api.zoom.us/v2/';

    /**
     * @var null 
     */
	private $attendingTableName = null;

	/**
	 * Constructor
	 *
	 * @param   object &$subject The object to observe
	 * @param   array  $config   An array that holds the plugin configuration
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

        $this->client = new Client(
            [
                'base_uri' => $this->endpoint
            ]
        );
	}

    private function generateJWT () {
	    static $jwt;

	    if (!isset($jwt)) {
            $params = $this->getParams();
            //Zoom API credentials from https://developer.zoom.us/me/
            $key = $params->get('zoom_api_key', '');
            $secret = $params->get('zoom_api_secret', '');
            $token = array(
                "iss" => $key,
                // The benefit of JWT is expiry tokens, we'll set this one to expire in 1 minute
                "exp" => time() + 60
            );

            $jwt = JWT::encode($token, $secret);
        }

	    return $jwt;
    }

    /**
     * Get the zoom key element
     *
     * @return  object  Fabrik element
     */
    protected function zkElement()
    {
        $params    = $this->getParams();
        $formModel = $this->getModel();
        $which = $params->get('zoom_which', 'users');

        return $formModel->getElement($params->get('zoom_' . $which . '_id_element'), true);
    }

    protected static function formatZoomPayload($value, $format)
    {
        switch ($format)
        {
            case 'datetime':
                if (is_array($value) && array_key_exists('date', $value))
                {
                    $value = $value['date'];
                }

                if (FabrikWorker::isDate($value))
                {
                    $date = new \DateTime($value);
                    $value = $date->format("Y-m-d\TH:i:s");
                }
                break;
            case 'array':
                $value = is_array($value) ? $value[0] : $value;
                break;
            case 'string':
            case 'integer':
            default:
                break;
        }

        return $value;
    }

    protected function getZkElementValue($dataName = 'formDataWithTableName')
    {
        $params    = $this->getParams();
        $formModel = $this->getModel();
        $which = $params->get('zoom_which', 'users');

        $elementKey = StringHelper::safeColNameToArrayKey(
            $params->get('zoom_' . $which . '_id_element')
        );

        $zkValue = ArrayHelper::getValue(
            $formModel->$dataName,
            $elementKey . '_raw',
            ArrayHelper::getValue(
                $formModel->$dataName,
                $elementKey,
                ''
            )
        );

        return is_array($zkValue) ? $zkValue[0] : $zkValue;
    }

	/**
	 * Run right before the form is processed
	 * form needs to be set to record in database for this to hook to be called
	 * If we need to update the records fk then we should run process(). However means we don't have access to the
	 * row's id.
	 *
	 * @return    bool
	 */
	public function onBeforeStore()
	{
        $params = $this->getParams();

        if (!$this->shouldProcess('zoom_condition', null, $params))
        {
            return true;
        }

        $which = $params->get('zoom_which', 'users');
        $result = true;

        switch ($which)
        {
            case 'users':
                $result = $this->processUser();
                break;
            case 'webinars':
                $result = $this->processWebinar();
                break;
            case 'meetings':
                $result = $this->processMeeting();
                break;
        }

        return $result;
	}

    private function getUsers ($status = 'active') {
	    $users = [];
	    $done = false;
	    $pageCount = 1;

	    do {
            $response = $this->client->request(
                'GET',
                'users',
                [
                    'query' => [
                        'status' => $status,
                        'page_size' => 30,
                        'page_count' => $pageCount
                    ],
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->generateJWT(),
                        'Accept' => 'application/json'
                    ]
                ]
            );

            if ($response->getStatusCode() === 200) {
                $payload = json_decode($response->getBody()->getContents());
                $users = array_merge($users, $payload->users);

                if ($payload->total_records > count($users))
                {
                    $pageCount++;
                }
                else
                {
                    $done = true;
                }
            }
        } while (!$done);

	    return $users;
    }

    protected function getElementValue($paramKey, $dataName = 'formDataWithTableName')
    {
        $params = $this->getParams();
        $formModel = $this->getModel();
        $elementKey = StringHelper::safeColNameToArrayKey($params->get($paramKey));

        $value = ArrayHelper::getValue(
            $formModel->$dataName,
            $elementKey . '_raw',
            ArrayHelper::getValue(
                $formModel->$dataName,
                $elementKey,
                ''
            )
        );

        return is_array($value) ? $value[0] : $value;
    }

    /*
     * Create or update the Zoom user
     */
    private function processUser()
    {
        $params = $this->getParams();
        $formModel = $this->getModel();
        $newZoomUser = false;
        $zoomUserId = $this->getZkElementValue();
        $checkExisting = $params->get('zoom_users_check_existing', '0') === '1';
        $status = $this->getElementValue('zoom_users_status');

        if ($status === '')
        {
            $status = false;
        }

        if ($status !== false && $status === '0')
        {
            return;
        }

        // CREATE / UPDATE USER
        if ($status === false || $status === '1') {
            $userInfo = [
                'type' => (int)$params->get('zoom_users_user_type', '1')
            ];

            $dataMap = [
                'email' => [
                    'param' => 'zoom_users_email_elemant',
                    'format' => 'string'
                ],
                'first_name' => [
                    'param' => 'zoom_users_first_name_element',
                    'format' => 'string'
                ],
                'last_name' => [
                    'param' => 'zoom_users_last_name_element',
                    'format' => 'string'
                ]
            ];


            foreach ($dataMap as $zoomKey => $map) {
                $elementKey = StringHelper::safeColNameToArrayKey($params->get($map['param']));

                $value = ArrayHelper::getValue(
                    $formModel->formDataWithTableName,
                    $elementKey . '_raw',
                    ArrayHelper::getValue(
                        $formModel->formDataWithTableName,
                        $elementKey,
                        ''
                    )
                );

                $userInfo[$zoomKey] = self::formatZoomPayload($value, $map['format']);
            }

            if (empty($zoomUserId)) {
                $newZoomUser = true;

                if ($checkExisting) {
                    $users = $this->getUsers('active');
                    $users = array_merge($users, $this->getUsers('pending'));

                    foreach ($users as $user) {
                        if ($user->email === $userInfo['email']) {
                            $zoomUserId = $user->id;
                            $newZoomUser = false;
                            break;
                        }
                    }
                }
            }
            else
            {

            }

            $payload = [
                'action' => $params->get('zoom_users_user_create_method', 'create'),
                'user_info' => (object)$userInfo
            ];

            try {
                if ($newZoomUser) {
                    $response = $this->client->post(
                        'users',
                        [
                            'body' => json_encode((object)$payload),
                            'headers' => [
                                'Authorization' => 'Bearer ' . $this->generateJWT(),
                                'Accept' => 'application/json',
                                'Content-type' => 'application/json'
                            ]
                        ]
                    );

                    $user = json_decode($response->getBody()->getContents());
                    $zoomUserId = $user->id;
                } else {
                    $response = $this->client->patch(
                        'users/' . $zoomUserId,
                        [
                            'body' => json_encode((object)$payload),
                            'headers' => [
                                'Authorization' => 'Bearer ' . $this->generateJWT(),
                                'Accept' => 'application/json',
                                'Content-type' => 'application/json'
                            ]
                        ]
                    );
                }
            } catch (RequestException $e) {
                return $this->handleException($e);
            } catch (Exception $e) {
                $formModel->setFormErrorMsg(JText::_('PLG_FORM_ZOOM_API_ERROR_GENERAL'));
                return false;
            }

            $zkElement = $this->zkElement();
            $zkElementKey = $zkElement->getFullName();
            $formModel->updateFormData($zkElementKey, $zoomUserId, true, true);

            $usergroup = $params->get('zoom_users_usergroup', '');

            if (!empty($usergroup)) {
                $userId = $this->getElementValue('zoom_users_userid_elemant');

                if (!empty($userId)) {
                    JUserHelper::addUserToGroup($userId, $usergroup);
                }
            }
        }

        // SUSPEND USER
        else if ($status !== false && $status === '2')
        {
            $payload = [
                'action' => 'deactivate'
            ];

            try {
                    $response = $this->client->put(
                        'users/' . $zoomUserId . '/status',
                        [
                            'body' => json_encode((object)$payload),
                            'headers' => [
                                'Authorization' => 'Bearer ' . $this->generateJWT(),
                                'Accept' => 'application/json',
                                'Content-type' => 'application/json'
                            ]
                        ]
                    );
            } catch (RequestException $e) {
                return $this->handleException($e);
            } catch (Exception $e) {
                $formModel->setFormErrorMsg(JText::_('PLG_FORM_ZOOM_API_ERROR_GENERAL'));
                return false;
            }
        }

        // DELETE USER
        else if ($status !== false && $status === '3')
        {
            $payload = [
                'action' => 'delete'
            ];

            try {
                $response = $this->client->delete(
                    'users/' . $zoomUserId,
                    [
                        'query' => json_encode((object)$payload),
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->generateJWT(),
                            'Accept' => 'application/json',
                            'Content-type' => 'application/json'
                        ]
                    ]
                );
            } catch (RequestException $e) {
                return $this->handleException($e);
            } catch (Exception $e) {
                $formModel->setFormErrorMsg(JText::_('PLG_FORM_ZOOM_API_ERROR_GENERAL'));
                return false;
            }

        }
    }

    private function deleteUser($zoomUserId)
    {
        $payload = [
            'action' => 'delete'
        ];

        try {
            $response = $this->client->delete(
                'users/' . $zoomUserId,
                [
                    'query' => json_encode((object)$payload),
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->generateJWT(),
                        'Accept' => 'application/json',
                        'Content-type' => 'application/json'
                    ]
                ]
            );
        } catch (Exception $e) {
            return false;
        }

        return true;
    }


    private function deleteWebinar($webinarId)
    {
        try {
            $response = $this->client->delete(
                'webinars/' . $webinarId,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->generateJWT(),
                        'Accept' => 'application/json',
                        'Content-type' => 'application/json'
                    ]
                ]
            );
        }
        catch (Exception $e)
        {
            return false;
        }

        return true;
    }

    /*
     * Create or update the Zoom user
     */
    private function processWebinar()
    {
        $params = $this->getParams();
        $formModel = $this->getModel();

        $newZoomWebinar = false;
        $zoomWebinarId = $this->getZkElementValue();

        $payload = [
            'type' => 5,
        ];

        $settings = [
            'approval_type' => (int)$params->get('zoom_webinars_approval_type', '0'),
            'host_video' => true,
            'close_registration' => true,
            'registrants_confirmation_email' => $params->get('zoom_webinars_attending_confirmation_email', '1') === '1'
        ];

        $dataMap = [
            'topic' => [
                'param' => 'zoom_webinars_topic_element',
                'format' => 'string'
            ],
            'agenda' => [
                'param' => 'zoom_webinars_agenda_element',
                'format' => 'string'
            ],
            'start_time' => [
                'param' => 'zoom_webinars_start_time_element',
                'format' => 'datetime'
            ],
            'duration' => [
                'param' => 'zoom_webinars_duration_element',
                'format' => 'array'
            ],
            'password' => [
                'param' => 'zoom_webinars_password_element',
                'format' => 'string'
            ],
        ];

        $responseMap = [
	        'start_url' => [
		        'param' => 'zoom_webinars_start_url_element',
		        'format' => 'string'
	        ],
	        'join_url' => [
		        'param' => 'zoom_webinars_join_url_element',
		        'format' => 'string'
	        ],
        ];

        foreach ($dataMap as $zoomKey => $map) {
            $elementKey = StringHelper::safeColNameToArrayKey($params->get($map['param']));

            $value = ArrayHelper::getValue(
                $formModel->formDataWithTableName,
                $elementKey . '_raw',
                ArrayHelper::getValue(
                    $formModel->formDataWithTableName,
                    $elementKey,
                    ''
                )
            );

            $payload[$zoomKey] = self::formatZoomPayload($value, $map['format']);
        }

        $payload['timezone'] = JFactory::getConfig()->get('offset');

        if ($params->get('zoom_webinars_create_as', 'per') === 'per')
        {
            $hostUserId = $this->getElementValue('zoom_webinars_host_user_id_element');
        }
        else
        {
        	$hostUserId = $params->get('zoom_webinars_create_as_user'. '');
        	$settings['alternative_hosts'] = $this->getElementValue('zoom_webinars_host_user_id_element');
        }

        if (empty($hostUserId))
        {
            $formModel->setFormErrorMsg(JText::_('PLG_FORM_ZOOM_API_ERROR_NO_HOST_USER'));
            return false;
        }

        if (empty($zoomWebinarId))
        {
            $newZoomWebinar = true;
        }

	    $payload['settings'] = (object)$settings;

	    try {
            if ($newZoomWebinar) {
                $response = $this->client->post(
                    'users/' . $hostUserId . '/webinars',
                    [
                        'body' => json_encode((object)$payload),
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->generateJWT(),
                            'Accept' => 'application/json',
                            'Content-type' => 'application/json'
                        ]
                    ]
                );

                $webinar = json_decode($response->getBody()->getContents());
                $webinarId = $webinar->id;

                $zkElement = $this->zkElement();
                $zkElementKey = $zkElement->getFullName();
                $formModel->updateFormData($zkElementKey, $webinarId, true, true);
            } else {
                $response = $this->client->patch(
                    'webinars/' . $zoomWebinarId,
                    [
                        'body' => json_encode((object)$payload),
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->generateJWT(),
                            'Accept' => 'application/json',
                            'Content-type' => 'application/json'
                        ]
                    ]
                );

	            $response = $this->client->get(
	                'webinars/' . $zoomWebinarId,
	                [
		                'headers' => [
			                'Authorization' => 'Bearer ' . $this->generateJWT(),
			                'Accept' => 'application/json',
			                'Content-type' => 'application/json'
		                ]
	                ]
                );

	            $webinar = json_decode($response->getBody()->getContents());
            }
        } catch (RequestException $e) {
            return $this->handleException($e);
        } catch (Exception $e) {
            $formModel->setFormErrorMsg(JText::_('PLG_FORM_ZOOM_API_ERROR_GENERAL'));
            return false;
        }

	    foreach ($responseMap as $zoomKey => $map)
	    {
		    $elementKey = StringHelper::safeColNameToArrayKey($params->get($map['param']));

		    if (!empty($elementKey))
		    {
			    $formModel->updateFormData($elementKey, $webinar->$zoomKey, true);
		    }
	    }

	    $code = trim($params->get('zoom_php_webinars_post', ''));

	    if (!empty($code))
	    {
		    @trigger_error('');
		    FabrikHelperHTML::isDebug() ? eval($code) : @eval($code);
		    FabrikWorker::logEval(false, 'Eval exception : zoom webinars post : %s');
	    }

	    return true;
    }


    private function deleteMeeting($meetingId)
    {
        try {
            $response = $this->client->delete(
                'meetings/' . $meetingId,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->generateJWT(),
                        'Accept' => 'application/json',
                        'Content-type' => 'application/json'
                    ]
                ]
            );
        }
        catch (Exception $e)
        {
            return false;
        }

        return true;
    }

    /**
     * Create or update the Zoom meeting
     */
    private function processMeeting()
    {
        $params = $this->getParams();
        $formModel = $this->getModel();

        $newZoomMeeting = false;
        $zoomMeetingId = $this->getZkElementValue();

        $payload = [
            'type' => 2,
        ];

        $settings = [
            'approval_type' => (int)$params->get('zoom_meetings_approval_type', '0'),
            'host_video' => true
        ];

        $dataMap = [
            'topic' => [
                'param' => 'zoom_meetings_topic_element',
                'format' => 'string'
            ],
            'agenda' => [
                'param' => 'zoom_meetings_agenda_element',
                'format' => 'string'
            ],
            'password' => [
                'param' => 'zoom_meetings_password_element',
                'format' => 'string'
            ],
            'start_time' => [
                'param' => 'zoom_meetings_start_time_element',
                'format' => 'datetime'
            ],
            'duration' => [
                'param' => 'zoom_meetings_duration_element',
                'format' => 'array'
            ]
        ];

	    $responseMap = [
		    'start_url' => [
			    'param' => 'zoom_meetings_start_url_element',
			    'format' => 'string'
		    ],
		    'join_url' => [
			    'param' => 'zoom_meetings_join_url_element',
			    'format' => 'string'
		    ],
	    ];

        foreach ($dataMap as $zoomKey => $map) {
            $elementKey = StringHelper::safeColNameToArrayKey($params->get($map['param']));

            $value = ArrayHelper::getValue(
                $formModel->formDataWithTableName,
                $elementKey . '_raw',
                ArrayHelper::getValue(
                    $formModel->formDataWithTableName,
                    $elementKey,
                    ''
                )
            );

            $payload[$zoomKey] = self::formatZoomPayload($value, $map['format']);
        }

        $hostUserId = $this->getElementValue('zoom_meetings_host_user_id_element');

        if (empty($hostUserId))
        {
            $formModel->setFormErrorMsg(JText::_('PLG_FORM_ZOOM_API_ERROR_NO_HOST_USER'));
            return false;
        }

        if (empty($zoomMeetingId))
        {
            $newZoomMeeting = true;
        }

	    $payload['settings'] = (object)$settings;

        try {
            if ($newZoomMeeting) {
                $response = $this->client->post(
                    'users/' . $hostUserId . '/meetings',
                    [
                        'body' => json_encode((object)$payload),
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->generateJWT(),
                            'Accept' => 'application/json',
                            'Content-type' => 'application/json'
                        ]
                    ]
                );

                $meeting = json_decode($response->getBody()->getContents());
                $meetingId = $meeting->id;

                $zkElement = $this->zkElement();
                $zkElementKey = $zkElement->getFullName();
                $formModel->updateFormData($zkElementKey, $meetingId, true, true);
            } else {
                $response = $this->client->patch(
                    'meetings/' . $zoomMeetingId,
                    [
                        'body' => json_encode((object)$payload),
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->generateJWT(),
                            'Accept' => 'application/json',
                            'Content-type' => 'application/json'
                        ]
                    ]
                );

	            $response = $this->client->get(
		            'meetings/' . $zoomMeetingId,
		            [
			            'headers' => [
				            'Authorization' => 'Bearer ' . $this->generateJWT(),
				            'Accept' => 'application/json',
				            'Content-type' => 'application/json'
			            ]
		            ]
	            );

	            $meeting = json_decode($response->getBody()->getContents());
            }
        } catch (RequestException $e) {
            return $this->handleException($e);
        } catch (Exception $e) {
            $formModel->setFormErrorMsg(JText::_('PLG_FORM_ZOOM_API_ERROR_GENERAL'));
            return false;
        }

	    foreach ($responseMap as $zoomKey => $map)
	    {
		    $elementKey = StringHelper::safeColNameToArrayKey($params->get($map['param']));

		    if (!empty($elementKey))
		    {
			    $formModel->updateFormData($elementKey, $meeting->$zoomKey, true);
		    }
	    }

	    $code = trim($params->get('zoom_php_meetings_post', ''));

	    if (!empty($code))
	    {
		    @trigger_error('');
		    FabrikHelperHTML::isDebug() ? eval($code) : @eval($code);
		    FabrikWorker::logEval(false, 'Eval exception : zoom meetings post : %s');
	    }

	    return true;
    }

    /**
	 * Run after the form is processed
	 * form needs to be set to record in database for this to hook to be called
	 * If we don't need to update the records fk then we should run process() as we now have access to the row's id.
	 *
	 * @return    bool
	 */
	public function onAfterProcess()
	{
	}

    /**
     * Run from list model when deleting rows
     *
     * @param   array &$groups List data for deletion
     *
     * @return    bool
     */
    public function onDeleteRowsForm(&$groups)
    {
        $params = $this->getParams();
        $which = $params->get('zoom_which', 'users');
        $zkElement = $this->zkElement();
        $zkElementKey = $zkElement->getFullName() . '_raw';

        foreach ($groups as $group)
        {
            foreach ($group as $rows)
            {
                foreach ($rows as $row)
                {
                    if (isset($row->$zkElementKey)) {
                        switch ($which) {
                            case 'users':
                                $this->deleteUser($row->$zkElementKey);
                                break;
                            case 'webinars':
                                $this->deleteWebinar($row->$zkElementKey);
                                break;
                            case 'meetings':
                                $this->deleteMeeting($row->$zkElementKey);
                                break;
                        }
                    }
                }
            }
        }

        return true;
    }


    private function handleException($e)
    {
        $formModel = $this->getModel();

        if ($e->hasResponse())
        {
            $response = json_decode((string)$e->getResponse()->getBody());
            $formModel->setFormErrorMsg(JText::sprintf('PLG_FORM_ZOOM_API_ERROR_MESSAGE', $response->message));
        }
        else
        {
            $formModel->setFormErrorMsg(JText::_('PLG_FORM_ZOOM_API_ERROR_GENERAL'));
        }

        return false;
    }

    private function showJoinButtons()
    {
        $formModel = $this->getModel();
        $view = $this->app->input->get('view', 'form');

        if ($view !== 'list' && $formModel->isEditable())
        {
            return false;
        }

        $params = $this->getParams();
        $which = $params->get('zoom_which', 'meetings');
        $enable = $params->get('zoom_' . $which . '_attending_enable', '0');

        if ($enable === '0')
        {
            return false;
        }

        if ($enable === '2' && $view === 'list')
        {
            return false;
        }

        if ($enable === '3' && $view !== 'list')
        {
            return false;
        }

        $groups = $this->user->getAuthorisedViewLevels();

        return in_array($params->get('zoom_'. $which . '_attending_access', '1'), $groups);
    }

    /**
     * Sets up any end html (after form close tag)
     *
     * @return  void
     */
    public function getBottomContent()
    {
        $this->html = '';

        if (!$this->showJoinButtons())
        {
            return;
        }

        $params = $this->getParams();
        $which = $params->get('zoom_which', 'meetings');
        $formModel = $this->getModel();
        $tableName = $this->getAttendingTableName();

        if (!$tableName)
        {
            return;
        }

        $userIdField = StringHelper::shortColName($params->get('zoom_' . $which . '_attending_user_id'));
        $zoomIdField = StringHelper::shortColName($params->get('zoom_' . $which . '_attending_zoom_id'));
        $thingIdField = StringHelper::shortColName($params->get('zoom_' . $which . '_attending_thing_id'));

        $db = Worker::getDbo(false, $params->get('zoom_' . $which . '_attending_connection'));
        $query = $db->getQuery(true);

        $query->select('*')
            ->from($db->quoteName($tableName))
            ->where($db->quoteName($userIdField) . ' = ' . $this->user->get('id'))
            ->where($db->quoteName($thingIdField) . ' = ' . $formModel->getRowId());
        $db->setQuery($query);
        $result = $db->loadObject();

        if (empty($result))
        {
            $joinCondition = $params->get('zoom_webinars_attending_join_condition', '');

            if (!empty($joinCondition))
            {
                error_clear_last();

                if (FabrikHelperHTML::isDebug())
                {
                    $showJoin = eval($joinCondition);
                }
                else
                {
                    $showJoin = @eval($joinCondition);
                }

                Worker::logEval($showJoin, 'Caught exception on eval in zoom join condition eval : %s');

                if ($showJoin === false)
                {
                    $joinAltText = $params->get('zoom_webinars_attending_join_alt_text', '');
                }
                else
                {
                    $joinAltText = '';
                }
            }
            else
            {
                $showJoin = true;
                $joinAltText = '';
            }

            $showLeave = true;
            $leaveAltText = '';
        }
        else
        {
            $leaveCondition = $params->get('zoom_webinars_attending_leave_condition', '');

            if (!empty($leaveCondition))
            {
                error_clear_last();

                if (FabrikHelperHTML::isDebug())
                {
                    $showLeave = eval($leaveCondition);
                }
                else
                {
                    $showLeave = @eval($leaveCondition);
                }

                Worker::logEval($showLeave, 'Caught exception on eval in zoom join condition eval : %s');

                if ($showLeave === false)
                {
                    $leaveAltText = $params->get('zoom_webinars_attending_leave_alt_text', '');
                }
                else
                {
                    $leaveAltText = '';
                }
            }
            else
            {
                $showLeave = true;
                $leaveAltText = '';
            }

            $showJoin = true;
            $joinAltText = '';
        }

        $joinButtonLabel = $params->get('zoom_webinars_attending_join_button_label', '');

        if (empty($joinButtonLabel))
        {
            $joinButtonLabel = 'PLG_FORM_ZOOM_WEBINARS_ATTENDING_JOIN';
        }

        $leaveButtonLabel = $params->get('zoom_webinars_attending_leave_button_label', '');

        if (empty($leaveButtonLabel))
        {
            $leaveButtonLabel = 'PLG_FORM_ZOOM_WEBINARS_ATTENDING_LEAVE';
        }

        $zkElement = $this->zkElement();
        $zkElementKey = $zkElement->getFullName();

        $layoutData = new \stdClass();
        $layoutData->joinButtonLabel = $joinButtonLabel;
        $layoutData->leaveButtonLabel = $leaveButtonLabel;
        $layoutData->showJoin = $showJoin;
        $layoutData->joinAltText = $joinAltText;
        $layoutData->showLeave = $showLeave;
        $layoutData->leaveAltText = $leaveAltText;
        $layoutData->attending = !empty($result);
        $layoutData->userId = $this->user->get('id');
        $layoutData->thingId = $formModel->getRowId();
        $layoutData->zoomId = ArrayHelper::getValue($formModel->data, $zkElementKey, '');

        $layout     = $this->getLayout('join-' . $which);
        $this->html = $layout->render($layoutData);

        $opts = new \StdClass();
        $opts->renderOrder = $this->renderOrder;
        $opts->formid  = $formModel->getId();
        $opts->userId = $this->user->get('id');
        $opts->thingId = $formModel->getRowId();
        $opts->editable = $formModel->isEditable();
        $opts->attending = !empty($result);
        $opts->zoomId = ArrayHelper::getValue($formModel->data, $zkElementKey, '');
        $opts = json_encode($opts);

        $this->formJavascriptClass($params, $formModel);
        $formModel->formPluginJS['Zoom' . $this->renderOrder] = 'new Zoom(' . $opts . ')';

    }

    /**
     * Get any html that needs to be written after the form close tag
     *
     * @param   int  $c  Plugin counter
     *
     * @return    string    html
     */
    public function getBottomContent_result($c)
    {
        return $this->html;
    }

    private function addParticipant($zoomId, $userId)
    {
        $params = $this->getParams();
        $which = $params->get('zoom_which', '');
        $payload = new \stdClass();
        $user = JFactory::getUser($userId);
        $userProfile = JUserHelper::getProfile( $user->id );

        if (isset($userProfile->profile['first_name'])) {
            $payload->first_name = $userProfile->profile['first_name'];
        }
        else
        {
            $payload->first_name = $user->get('name');
        }

        if (isset($userProfile->profile['last_name'])) {
            $payload->last_name = $userProfile->profile['last_name'];
        }
        else
        {
            $payload->last_name = $user->get('name');
        }

        if (isset($userProfile->profile['phone'])) {
            $payload->phone = $userProfile->profile['phone'];
        }

        $payload->email = $user->get('email');

        $json = new \stdClass();

        try {
            $response = $this->client->post(
                $which . '/' . $zoomId . '/registrants',
                [
                    'body' => json_encode((object)$payload),
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->generateJWT(),
                        'Accept' => 'application/json',
                        'Content-type' => 'application/json'
                    ]
                ]
            );

            $participant = json_decode($response->getBody()->getContents());
            $participantId = $participant->id;
        } catch (RequestException $e) {
            if ($e->hasResponse())
            {
                $response = json_decode((string)$e->getResponse()->getBody());
                throw new \RuntimeException(JText::sprintf('PLG_FORM_ZOOM_API_ERROR_MESSAGE', $response->message));
            }
            else
            {
                throw new \RuntimeException(JText::_('PLG_FORM_ZOOM_API_ERROR_GENERAL'));
            }

        } catch (Exception $e) {
            throw new \RuntimeException(JText::_('PLG_FORM_ZOOM_API_ERROR_GENERAL'));
        }

        return $participant;
    }

    private function removeParticipant($zoomId, $participantId, $userId)
    {
        $user = JFactory::getUser($userId);
        $params = $this->getParams();
        $which = $params->get('zoom_which', '');
        $payload = new \stdClass();
        $payload->action = 'cancel';
        $payload->registrants = array();
        $registrant = new \stdClass();
        $registrant->id = $participantId;
        $registrant->email = $user->get('email');
        $payload->registrants[] = $registrant;
        $statusCode = '';


        try {
            $response = $this->client->put(
                $which . '/' . $zoomId . '/registrants/status',
                [
                    'body' => json_encode((object)$payload),
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->generateJWT(),
                        'Accept' => 'application/json',
                        'Content-type' => 'application/json'
                    ]
                ]
            );

            $statusCode = $response->getStatusCode();
        } catch (RequestException $e) {
            if ($e->hasResponse())
            {
                $response = json_decode((string)$e->getResponse()->getBody());
                throw new \Exception(JText::sprintf('PLG_FORM_ZOOM_API_ERROR_MESSAGE', $response->message));
            }
            else
            {
                throw new \Exception(JText::_('PLG_FORM_ZOOM_API_ERROR_GENERAL'));
            }

        } catch (Exception $e) {
            throw new \RuntimeException(JText::_('PLG_FORM_ZOOM_API_ERROR_GENERAL'));
        }

        return $statusCode;
    }

    /**
     * Gets the options for the drop down - used in package when forms update
     *
     * @return  void
     */
    public function onAjax_attending()
    {
        $input       = $this->app->input;
        $thingId      = $input->get('thingId', '', 'string');
        $userId      = $input->get('userId', '', 'string');
        $formId      = $input->get('formid', '', 'string');
        $zoomId      = $input->get('zoomId', '', 'string');
        $attending   = $input->get('attending', '', 'string');
        $renderOrder = $input->get('renderOrder', '', 'string');
        $formModel   = JModelLegacy::getInstance('Form', 'FabrikFEModel');
        $formModel->setId($formId);
        $params         = $formModel->getParams();
        $params         = $this->setParams($params, $renderOrder);
        $which = $params->get('zoom_which', 'meetings');
        $code = '';

        $tableName = $this->getAttendingTableName();

        if (!$tableName)
        {
            return;
        }

        $userIdField = StringHelper::shortColName($params->get('zoom_' . $which . '_attending_user_id'));
        $zoomIdField = StringHelper::shortColName($params->get('zoom_' . $which . '_attending_zoom_id'));
        $thingIdField = StringHelper::shortColName($params->get('zoom_' . $which . '_attending_thing_id'));
	    $joinUrlField = StringHelper::shortColName($params->get('zoom_' . $which . '_attending_join_url'));

        $db = Worker::getDbo(false, $params->get('zoom_' . $which . '_attending_connection'));
        $query = $db->getQuery(true);

        $query->select('*')
            ->from($db->quoteName($tableName))
            ->where($db->quoteName($userIdField) . ' = ' . $db->quote($userId))
            ->where($db->quoteName($thingIdField) . ' = ' . $db->quote($thingId));
        $db->setQuery($query);
        $result = $db->loadObject();

        $response = new \stdClass();
        $response->err = '';

        if ($result && $attending === '1')
        {
            try {
                $this->removeParticipant($zoomId, $result->$zoomIdField, $userId);
                $query->clear()
                    ->delete($db->quoteName($tableName))
                    ->where($db->quoteName($userIdField) . ' = ' . $db->quote($userId))
                    ->where($db->quoteName($thingIdField) . ' = ' . $db->quote($thingId));
                $db->setQuery($query);
                $db->execute();

	            $code = trim($params->get('zoom_php_attending_remove', ''));
            }
            catch (\Exception $e)
            {
                $response->err = $e->getMessage();
            }
        }
        else if (empty($result) && $attending === '0')
        {
            try
            {
                $participant = $this->addParticipant($zoomId, $userId);

                $query->insert($db->quoteName($tableName))
                    ->set($db->quoteName($userIdField) . ' = ' . $db->quote($userId))
                    ->set($db->quoteName($thingIdField) . ' = ' . $db->quote($thingId))
                    ->set($db->quoteName($zoomIdField) . ' = ' . $db->quote($participant->id))
	                ->set($db->quoteName($joinUrlField) . ' = ' . $db->quote($participant->join_url));
                $db->setQuery($query);
                $db->execute();

	            $code = trim($params->get('zoom_php_attending_add', ''));
            }
            catch (\RuntimeException $e)
            {
                $response->err = $e->getMessage();
            }
        }

	    if (!empty($code))
	    {
		    @trigger_error('');
		    FabrikHelperHTML::isDebug() ? eval($code) : @eval($code);
		    FabrikWorker::logEval(false, 'Eval exception : zoom attending add : %s');
	    }

        echo json_encode($response);
    }

    /**
     * Get the attending table name
     *
     * @return  string  db table name
     */
    protected function getAttendingTableName()
    {
        if (isset($this->attendingTableName))
        {
            return $this->attendingTableName;
        }

        $params = $this->getParams();
        $which = $params->get('zoom_which', 'meetings');
        $attendingTable = (int) $params->get('zoom_' . $which . '_attending_table', '');

        if (empty($attendingTable))
        {
            $this->attendingTableName = false;

            return false;
        }

        $db = Worker::getDbo();
        $query = $db->getQuery(true);
        $query->select('db_table_name')
            ->from('#__{package}_lists')
            ->where('id = ' . (int)$attendingTable);
        $db->setQuery($query);
        $db_table_name = $db->loadResult();

        if (!isset($db_table_name))
        {
            $this->attendingTableName = false;

            return false;
        }

        $this->attendingTableName = $db_table_name;

        return $this->attendingTableName;
    }

    public function onBeforeLoad()
    {
    	$params = $this->getParams();

	    $which = $params->get('zoom_which', 'users');
	    $result = true;
	    $formModel = $this->getModel();

	    switch ($which)
	    {
		    case 'webinars':
		    	$ownerId = $this->getElementValue('zoom_webinars_user_id_element', 'data');

		    	if (!empty($ownerId) && (int)$ownerId === (int)$this->user->get('id'))
			    {
				    $responseMap = [
					    'start_url' => [
						    'param'  => 'zoom_webinars_start_url_element',
						    'format' => 'string'
					    ],
					    'join_url'  => [
						    'param'  => 'zoom_webinars_join_url_element',
						    'format' => 'string'
					    ],
				    ];

				    $zoomWebinarId = $this->getZkElementValue('data');

				    if (!empty($zoomWebinarId))
				    {
					    try
					    {
						    $response = $this->client->get(
							    'webinars/' . $zoomWebinarId,
							    [
								    'headers' => [
									    'Authorization' => 'Bearer ' . $this->generateJWT(),
									    'Accept'        => 'application/json',
									    'Content-type'  => 'application/json'
								    ]
							    ]
						    );
					    }
					    catch (Exception $e)
					    {
						    return;
					    }

					    $webinar = json_decode($response->getBody()->getContents());

					    foreach ($responseMap as $zoomKey => $map)
					    {
						    $elementKey = StringHelper::safeColNameToArrayKey($params->get($map['param']));

						    if (!empty($elementKey))
						    {
							    $formModel->data[$elementKey]          = $webinar->$zoomKey;
							    $formModel->data[$elementKey . '_raw'] = $webinar->$zoomKey;
						    }
					    }
				    }
			    }

			    break;
		    case 'meetings':
			    $ownerId = $this->getElementValue('zoom_meetings_user_id_element', 'data');

			    if (!empty($ownerId) && (int)$ownerId === (int)$this->user->get('id'))
			    {
				    $responseMap = [
					    'start_url' => [
						    'param'  => 'zoom_meetings_start_url_element',
						    'format' => 'string'
					    ],
					    'join_url'  => [
						    'param'  => 'zoom_meetings_join_url_element',
						    'format' => 'string'
					    ],
				    ];

				    $zoomMeetingId = $this->getZkElementValue('data');

				    if (!empty($zoomMeetingId))
				    {
					    try
					    {
						    $response = $this->client->get(
							    'meetings/' . $zoomMeetingId,
							    [
								    'headers' => [
									    'Authorization' => 'Bearer ' . $this->generateJWT(),
									    'Accept'        => 'application/json',
									    'Content-type'  => 'application/json'
								    ]
							    ]
						    );
					    }
					    catch (Exception $e)
					    {
						    return;
					    }

					    $meeting = json_decode($response->getBody()->getContents());

					    foreach ($responseMap as $zoomKey => $map)
					    {
						    $elementKey = StringHelper::safeColNameToArrayKey($params->get($map['param']));

						    if (!empty($elementKey))
						    {
							    $formModel->data[$elementKey]          = $meeting->$zoomKey;
							    $formModel->data[$elementKey . '_raw'] = $meeting->$zoomKey;
						    }
					    }
				    }
			    }
			    break;
	    }

    }

    /**
     * Add the add to cart list layout to the Fabrik list's data
     *
     * @param array $opts
     *
     * @return void
     */
    public function onLoadListData($opts)
    {
        if ($this->app->isAdmin()  || !$this->showJoinButtons())
        {
            return;
        }

        $tableName = $this->getAttendingTableName();

        if (!$tableName)
        {
            return;
        }

        $params = $this->getParams();
        $which = $params->get('zoom_which', 'meetings');
        $data = $opts[0]->data;
        $formModel = $this->getModel();
        $zkElement = $this->zkElement();
        $zkElementKey = $zkElement->getFullName();
        $userIdField = StringHelper::shortColName($params->get('zoom_' . $which . '_attending_user_id'));
        $zoomIdField = StringHelper::shortColName($params->get('zoom_' . $which . '_attending_zoom_id'));
        $thingIdField = StringHelper::shortColName($params->get('zoom_' . $which . '_attending_thing_id'));
        $db = Worker::getDbo(false, $params->get('zoom_' . $which . '_attending_connection'));
        $query = $db->getQuery(true);

        foreach ($data as $group)
        {
            foreach ($group as &$row) {
                $query->clear()
                    ->select('*')
                    ->from($db->quoteName($tableName))
                    ->where($db->quoteName($userIdField) . ' = ' . $this->user->get('id'))
                    ->where($db->quoteName($thingIdField) . ' = ' . $db->quote($row->__pk_val));
                $db->setQuery($query);
                $result = $db->loadObject();

                $layout = $this->getLayout('join-' . $which . '-list');
                $layoutData = new \stdClass();
                $layoutData->attending = !empty($result);
                $layoutData->userId = $this->user->get('id');
                $layoutData->thingId = $row->__pk_val;
                $layoutData->zoomId = $row->$zkElementKey;
                $layoutData->formId = $formModel->getId();
                $layoutData->renderOrder = $this->renderOrder;
                $row->zoom_attending = $layout->render($layoutData);
            }
        }

        $this->listJs();
    }

    private function listJs()
    {
        static $done;

        if (!isset($done))
        {
            $done = true;

            // Watch quantity input and update add to cart button data.
            $doc = JFactory::getDocument();
            $doc->addScriptDeclaration('jQuery(document).ready(function ($) {
            $(\'.zoom\').on(\'click\', function (e) {
            	var attend = $(this).data(\'attending\');
                Fabrik.loader.start($(this).closest(\'.fabrikForm\'), Joomla.JText._(\'COM_FABRIK_LOADING\'));
    
                $.ajax({
                    url     : Fabrik.liveSite + \'index.php\',
                    method  : \'post\',
                    dataType: \'json\',
                    context: this,
                    \'data\'  : {
                        \'option\'               : \'com_fabrik\',
                        \'format\'               : \'raw\',
                        \'task\'                 : \'plugin.pluginAjax\',
                        \'plugin\'               : \'zoom\',
                        \'method\'               : \'ajax_attending\',
                        \'userId\'               : $(this).data(\'user-id\'),
                        \'thingId\'				: $(this).data(\'thing-id\'),
                        \'zoomId\'				: $(this).data(\'zoom-id\'),
                        \'attending\'				: attend,
                        \'g\'                    : \'form\',
                        \'formid\'               : $(this).data(\'form-id\'),
                        \'renderOrder\'          : $(this).data(\'render-order\')
                    }
    
                }).always(function () {
                    Fabrik.loader.stop($(this).closest(\'.fabrikForm\'));
                }).fail(function (jqXHR, textStatus, errorThrown) {
                    window.alert(textStatus);
                }).done(function (json) {
                    if (json.err === \'\') {
                        jQuery(\'.zoomAttendingError\').addClass(\'fabrikHide\');
                        if (this.options.attending) {
                            jQuery(\'button.zoomAttending\').parent().addClass(\'fabrikHide\');
                            jQuery(\'button.zoomNotAttending\').parent().removeClass(\'fabrikHide\');
                            this.options.attending = false;
                        }
                        else {
                            jQuery(\'button.zoomAttending\').parent().removeClass(\'fabrikHide\');
                            jQuery(\'button.zoomNotAttending\').parent().addClass(\'fabrikHide\');
                            this.options.attending = true;
                        }
                    }
                    else {
                        jQuery(\'.zoomAttendingError\').html(\'<p>\' + json.err + \'</p>\');
                        jQuery(\'.zoomAttendingError\').removeClass(\'fabrikHide\');
                    }
                });
            });
		});
		');
        }
    }

}
