<?php

namespace App\Zalando;

use App\Connect;
use App\ProjectConfig;
use Monolog\Logger;

class ZalandoDI
{
    public function getStagingDb(): Connect
    {
        return new Connect(ProjectConfig::getValue('stagingDbCredentials'));
    }

    public function getTestDb(): Connect
    {
        return new Connect(ProjectConfig::getValue('testDbCredentials'));
    }

    public function getLogger(string $loggerFileName): Logger
    {
        return new Logger($loggerFileName);
    }

    public function getProductSynchronizer(string $loggerFileName): AbstractSynchronizer
    {
        return new UpdateSynchronizer(
            ProjectConfig::getValue('shopifyAuthConfig'),
            $this->getStagingDb(),
            $this->getLogger($loggerFileName),
            'zalando'
        );
    }

    public function getProductParser(string $loggerFileName): ProductParser
    {
        return new ProductParser(
            $this->getStagingDb(),
            $this->getLogger($loggerFileName)
        );
    }


}
