<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Main
{
    public function get_http_response_code(string $url): string
    {
        $headers = get_headers($url);

        return substr($headers[ 0 ], 9, 3);
    }
}