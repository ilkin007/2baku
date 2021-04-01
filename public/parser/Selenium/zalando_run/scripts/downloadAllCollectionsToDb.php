<?php
exit;
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

use App\Connect;
use App\ProjectConfig;
use PHPShopify\ShopifySDK;

include('/var/www/html/parser/Selenium/vendor/autoload.php');

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));

//$sql = "SELECT `sizes` FROM `products`";
//$query = $db->query($sql);
//
//while ($q = $db->fetch($query)) {
//
//}

$shopifyAuthConfig = [
    'ShopUrl' => '2baku-de.myshopify.com/',
    'ApiKey' => '47eaa26ce29c932c6a94b848fb0febe5',
    'Password' => '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'
];

$shopify = new ShopifySDK($shopifyAuthConfig);

$result = $shopify->SmartCollection->get(['limit' => 250]);

foreach ($result as $value) {
    $sql = "UPDATE `categories` SET `shopify_category_id`='{$value['id']}', `updated_in_shopify`=1 WHERE title='{$value['title']}'";

    $db->query($sql);
}

//GET /admin/api/2021-01/smart_collections.json
