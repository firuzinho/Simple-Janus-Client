<?php

class JanusClient {

    const
        METHOD_GET = "GET",
        METHOD_POST = "POST",
        METHOD_PUT = "PUT",
        METHOD_DELETE = "DELETE";


    const PLUGIN_VIDEOROOM = 'janus.plugin.videoroom';

    protected $serverAddress = '';

    protected $apiSecret = '';

    protected $pluginHandleId = '';

    protected $sessionId = '';

    protected $transaction = '';



    public function __construct($serverAddress, $apiSecret = '')
    {
        $this->serverAddress = $serverAddress;
        $this->apiSecret = $apiSecret;

        $this->transaction = $this->generateTransactionId();
    }

    public function generateTransactionId() {
        return md5(uniqid("jvtid_"));
    }

    public function sendQuery($method, array $requestParams, $url = '') {

        if ($this->apiSecret !== '') {
            $requestParams = array_merge(array(
                'apiSecret' => $this->apiSecret,
            ), $requestParams);
        }

        $apiEndpoint = $url;

        if (empty($url)) {
            $apiEndpoint = $this->serverAddress;

            if (!empty($this->sessionId)) {
                $apiEndpoint .= '/' . $this->sessionId;
            }

            if (!empty($this->pluginHandleId)) {
                $apiEndpoint .= '/' . $this->pluginHandleId;
            }
        }



        $curl = curl_init();

        $requestParams = json_encode($requestParams);

        switch ($method) {
            case self::METHOD_POST:
                curl_setopt($curl, CURLOPT_POST, 1);

                if ($requestParams) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $requestParams);
                }
                break;
            default:
                if ($requestParams) {
                    $apiEndpoint .= http_build_query($requestParams);
                }
        }
        
        curl_setopt($curl, CURLOPT_URL, $apiEndpoint);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);


        $response = json_decode(curl_exec($curl), true);

        curl_close($curl);


        return $response;

    }

    private function _sendMessage($message, $jsep = '')
    {
        $data = array(
            'janus' => 'message',
            'body' => $message,
            'transaction' => $this->transaction,
            'apisecret' => $this->apiSecret,
        );

        if ($jsep) {
            $data['jsep'] = $jsep;
        }

        return $this->sendQuery(self::METHOD_POST, $data);
    }

    public function connect() {

        $params = array(
            'janus' => 'create',
            'transaction' => $this->transaction
        );

        $response = $this->sendQuery(self::METHOD_POST, $params);

        if (isset($response['data']['id'])) {
            $this->sessionId = (string)$response['data']['id'];
        } else {
            $this->sessionId = '';
        }

        return $this->sessionId;
    }

    public function disconnect() {

        if ($this->sessionId == '') {
            return;
        }

        $params = array(
            'janus' => 'destroy',
            'transaction' => $this->transaction
        );

        $response = $this->sendQuery(self::METHOD_POST, $params);

        if ($response)
            $this->sessionId = '';


        return $this->sessionId;
    }

    public function attach()
    {
        if (!$this->isLoggedIn()) {
            // session not created
            echo "not logged in";
            return null;
        }

        $params = array(
            'janus' => 'attach',
            'plugin' => self::PLUGIN_VIDEOROOM,
            'transaction' => $this->transaction,
        );

        $response = $this->sendQuery(self::METHOD_POST, $params);

        if (isset($response['data']['id'])) {
            $this->pluginHandleId = $response['data']['id'];
        } else {
            $this->pluginHandleId = '';
        }

        return $this->pluginHandleId;
    }

    public function detach()
    {
        $params = array(
            'janus' => 'detach',
            'transaction' => $this->transaction,
        );

        $response = $this->sendQuery(self::METHOD_POST, $params);

        $result = false;
        if ($response) {
            $this->pluginHandleId = '';

            $result = true;
        }

        return $result;
    }


    public function isLoggedIn()
    {
        return $this->sessionId !== '';
    }

    public function createRoom($desc, $recordDir, $room = 0, $bitrate = 0, $countOfPublishers = 6, $secret = '')
    {

        if (empty($this->pluginHandleId)) {
            echo 'plugin not attached';
            return false;
        }

        $params = array(
            'description' => $desc,
            'secret' => $secret,
            'publishers' => $countOfPublishers,
        );

        if ($bitrate > 0) {
            $params['bitrate'] = $bitrate;
        }

        if (!empty($recordDir)) {
            $params['record'] = true;
            $params['rec_dir'] = $recordDir;
        }

        return $this->_createRoom($room, $params);
    }

    public function destroyRoom($roomId, $secret) {
        $data = array(
            'request' => 'destroy',
            'room' => $roomId,
            'secret' => $secret,
        );

        $result = $this->_sendMessage($data);

    }

    private function _createRoom($room, $params=array())
    {
        if (!$this->isLoggedIn()) {
            return false;
        }
        $data = array(
            'request' => 'create',
            'transaction' => $this->transaction
        );

        if ($room) {
            $data['room'] = $room;
        }

        $data = array_merge($data, $params);

        $result = $this->_sendMessage($data);

        if (isset($result['plugindata']['data']['videoroom']) &&
            $result['plugindata']['data']['videoroom'] == 'created')
            return $result['plugindata']['data']['room'];
        else
            return false;

    }
}