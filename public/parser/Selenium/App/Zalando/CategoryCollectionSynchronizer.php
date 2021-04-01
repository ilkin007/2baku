<?php

namespace App\Zalando;

use App\Connect;
use Monolog\Logger;
use PHPShopify\Exception\ApiException;
use PHPShopify\Exception\CurlException;
use PHPShopify\ShopifySDK;

class CategoryCollectionSynchronizer
{
    private $shopify;

    public function __construct(
        private array $shopifyAuthConfig,
        private Connect $db,
        private Logger $log,
        private string $website,
    ) {
        $this->shopify = new ShopifySDK($shopifyAuthConfig);
    }

    public function initialize(): void
    {
        $newCategories = $this->getNewCategories();

        if(count($newCategories) === 0){
            exit;
        }

        $this->createNewShopifyCollections($newCategories);
    }

    private function getNewCategories(): array
    {
        $sql = "SELECT `id`, `title` FROM `categories` WHERE `updated_in_shopify` IS null or `updated_in_shopify` = 0";

        $query = $this->db->query($sql);

        if (!$query) {
            $this->log->critical('Error while setting category as updated (with new shopify_category_id');
            exit;
        }

        $categoriesArray = [];
        while ($q = $this->db->fetch($query)) {
            $categoriesArray[] = $q;
        }

        return $categoriesArray;
    }

    private function createNewShopifyCollections(array $categories): void
    {
        foreach ($categories as $category) {
            $shopifyId = $this->createNewShopifyCollection($category['title']);
            $this->setCategoryAsUpdatedInShopify($category['id'], $shopifyId);
        }
    }

    private function createNewShopifyCollection(string $categoryTitle): string
    {
        $collection = [
            'title' => $categoryTitle,
            'rules' => [
                [
                    'column' => 'tag',
                    'relation' => 'equals',
                    'condition' => '"' . $categoryTitle . '"',
                ],
                [
                    'column' => 'variant_inventory',
                    'relation' => 'greater_than',
                    'condition' => 0,
                ],
            ],
        ];

        try {
            $result = $this->shopify->SmartCollection->post($collection);
        } catch (ApiException $e) {
            $this->log->critical($e->getMessage());
            exit;
        } catch (CurlException $e) {
            $this->log->critical($e->getMessage());
            exit;
        }

        return $result['id'];
    }

    private function setCategoryAsUpdatedInShopify(string $categoryId, string $shopifyId): void
    {
        $sql = "UPDATE `categories` SET 
                        `updated_in_shopify` = 1,
                        `shopify_category_id` = '{$shopifyId}'
        WHERE id='{$categoryId}'";

        $query = $this->db->query($sql);

        if (!$query) {
            $this->log->critical('Error while setting category as updated (with new shopify_category_id');
            exit;
        }
    }
}
