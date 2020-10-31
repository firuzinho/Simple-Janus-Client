<?php

namespace Videoroom\App;

/**
 * Class app
 * @package Videoroom\app
 */
class App
{
    /**
     * @var array
     */
    protected $_config = [];

    /**
     * @var null
     */
    protected $_logger = null;

    /**
     * @return null
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    /**
     * @param null $logger
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }


    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->_config;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->_config = $config;
    }

    public function run() {

        $janus_connection = new \JanusClient($this->_config['server_address']);
        $janus_connection->connect();
        $handle = $janus_connection->attach();


        if ($handle) {
            $room = 4204451700000;

            $createRes = $janus_connection->createRoom("test room $room", "/tmp", $room);

            if ($createRes) {
                echo "Room $room created";
            } else {
                echo "Could not create room";
            }
        }

        $janus_connection->detach();
        $janus_connection->disconnect();


        unset($janus_connection);


    }
}