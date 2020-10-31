<?php

namespace Videoroom\App;


use Katzgrau\KLogger\Logger;

class AppFactory
{

    /**
     * @return App
     */
    public function __invoke() : App
    {
        $app = new App();

        $config = require(__DIR__ . '/../config/Common.php');
        $logger = new Logger($config['logs_dir']);


        $app->setConfig($config);
        $app->setLogger($logger);

        return $app;
    }
}