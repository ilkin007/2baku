<?php

namespace App;

class ProjectConfig
{
    private const config = [
        'stagingDbCredentials' => [
            'dbHost' => 'db',
            'dbUser' => 'db_user',
            'dbPass' => 'db_user_pass',
            'dbName' => 'app_db'
        ],
        'shopifyAuthConfig' => [
            'ShopUrl' => '{SOME_SHOP_URL}',
            'ApiKey' => '{API_KEY_HERE}',
            'Password' => '{PASSWORD}'
        ]
    ];

    private $categoryStopWords;
    private $brandStopWords;

    public static function getValue(string $key)
    {
        return self::config[$key];
    }

    public function __construct()
    {
        $categoryStopWords = file_get_contents('/var/www/html/parser/Selenium/App/configData/categoryStopWords.json');
        $this->categoryStopWords = json_decode(strtolower($categoryStopWords), true);
        $this->categoryStopWords = $this->categoryStopWords['words'];

        $brandStopWords = file_get_contents('/var/www/html/parser/Selenium/App/configData/brandStopWords.json');
        $this->brandStopWords = json_decode(strtolower($brandStopWords), true);
        $this->brandStopWords = $this->brandStopWords['words'];
    }

    public function getCategoryStopWords(): array
    {
        return $this->categoryStopWords;
    }

    public function getBrandStopWords(): array
    {
        return $this->brandStopWords;
    }
}
