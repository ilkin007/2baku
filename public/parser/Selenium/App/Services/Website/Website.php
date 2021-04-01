<?php

namespace App\Services\Website;

use App\Connect;

class Website
{
    private $website;
    private $db;

    public function __construct(string $website, Connect $db)
    {
        $this->website = $website;
        $this->db = $db;
    }

    public function invokeWebsiteParser()
    {

    }

}