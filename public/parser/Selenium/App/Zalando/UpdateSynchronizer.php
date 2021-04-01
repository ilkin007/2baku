<?php

namespace App\Zalando;

use App\Zalando\Domain\Product\Factory\ProductFactory;
use App\Zalando\Domain\Product\Product;
use App\Zalando\Domain\Product\ProductCategory;
use App\Zalando\Domain\Product\ProductCollection;
use App\Zalando\Domain\Product\ProductId;
use App\Zalando\Domain\Product\ShopifyId;
use App\Zalando\Exception\MarginNotFoundException;
use App\Zalando\Exception\ProductSynchronizerException;
use PHPShopify\Exception\ApiException;

class UpdateSynchronizer extends AbstractSynchronizer
{
    /**
     * @throws ProductSynchronizerException
     */
    public function initialize(): void
    {
        $products = $this->getProductsToUpdateInShopify();

        /** @var Product $product */
        foreach ($products as $product) {
            $this->setUpdateStatus($product->getId(), self::PENDING);
        }

        if ($products->count() === 0) {
            echo "No products to update!";

            exit;
        }

        foreach ($products as $product) {
            try {
                $product = $this->addMargin($product);
            }
            catch (MarginNotFoundException $e){
                $this->setUpdateStatus($product->getId(), self::FAILED);
            }

            $mappedProduct = $this->mapProductToShopify($product);

            if (count($mappedProduct) === 0) {
                $this->setUpdateStatus($product->getId(), self::OUT_OF_STOCK);

                continue;
            }

            try {
                $this->updateProductOnShopify($product->getShopifyId(), $mappedProduct);
                $this->setUpdateStatus($product->getId(), self::SUCCESS);
            } catch (\Exception $e) {
                $this->setUpdateStatus($product->getId(), self::FAILED);
                $this->log->error("Error while adding product to Shopify. The message:" . $e->getMessage());
            }
        }
    }

    public function mapProductToShopify(Product $product): array
    {
        $productVariants = [];

        $sizes = $this->removeSizesIfNotEnoughQuantity($product->getSizes());

        $productSizesArray = $sizes->toArray();
        foreach ($productSizesArray as $size => $quantity) {
            $productVariants[] = [
                //'compare_at_price'     => $product->getOldPrice()->toFloat(),
                'inventory_management' => 'shopify',
                'inventory_policy' => 'deny',
                'inventory_quantity' => $quantity,
                'option1' => $size,
                'option2' => $product->getColor()->toString(),
                'presentment_prices' => [
                    'price' => [
                        'currency_code' => 'EUR',
                        'amount' => $product->getPrice()->toFloat(),
                    ],
                    //'compare_at_price' => [
                    //    'currency_code' => 'EUR',
                    //    'amount'        => $product->getOldPrice()->toFloat(),
                    //],
                ],
                'price' => $product->getPrice()->toFloat(),
            ];
        }

        $tags = '';
        $productCategoriesArray = $product->getCategories()->toArray();

        //This product category is something like tags, not the main category of the product!!!
        foreach ($productCategoriesArray as $productCategory) {
            $tags .= $productCategory . ',';
        }

        $targetGroupsArray = $product->getTargetGroups()->toArray();
        foreach ($targetGroupsArray as $key => $targetGroup) {
            if ($key === 'age' || $key === 'gender') {
                foreach ($targetGroup as $value) {
                    $tags .= $value . ',';
                }
            }
        }

        foreach ($productSizesArray as $sizeName => $quantity) {
            $tags .= '"' . $sizeName . '",';
        }

        $tags .= '"' . $product->getColor()->toString() . '",';

        $tags .= '"' . $this->getPriceRange($product->getPrice()) . '",';

        $tags .= '"' . $product->getCategory()->toString() . '",';

        $tags .= $product->getBrand()->toString() . ',';

        $tags = substr($tags, 0, -1);

        $mappedProduct = [
            'tags' => $tags,
            'variants' => $productVariants,
        ];

        return $mappedProduct;
    }

    /**
     * @throws \PHPShopify\Exception\ApiException
     * @throws \PHPShopify\Exception\CurlException
     */
    private function updateProductOnShopify(ShopifyId $shopifyId, array $shopifyMappedProduct): bool
    {
        $locationId = '45139329160';
        $shopifyInventories = $this->getShopifyProductInventories($shopifyId->toString());

        if (count($shopifyMappedProduct['variants']) === 0) {

            foreach ($shopifyInventories as $sizeAndColor => $inventoryItemId) {
                $data = [
                    'location_id' => $locationId,
                    'inventory_item_id' => $inventoryItemId,
                    'available' => 0,
                ];

                $this->shopify->InventoryLevel->set($data);
            }

            return true;
        }


        foreach ($shopifyMappedProduct['variants'] as $variant) {
            $key = $variant['option1'] . '|' . $variant['option2'];
            if (!isset($shopifyInventories[$key])) {
                return false;
            }

            $inventoryId = $shopifyInventories[$key];

            $data = [
                'location_id' => $locationId,
                'inventory_item_id' => $inventoryId,
                'available' => $variant['inventory_quantity'],
            ];

            $this->shopify->InventoryLevel->set($data);
        }

        $response = $this->shopify->Product($shopifyId->toString())->put($shopifyMappedProduct);

        if (count($response) > 0) {
            return true;
        }

        return false;
    }

    /**
     * @throws ProductSynchronizerException
     */
    public function checkWhetherProductExistInShopify(ShopifyId $shopifyId): bool
    {
        try {
            $this->shopify->Product($shopifyId->toString())->get();
            return true;
        } catch (ApiException $e) {
            if (stristr($e->getMessage(), 'Not Found')) {
                return false;
            }
        } catch (\Exception $e) {
            $this->log->critical('Could not reach Shopify API. The error message: ' . $e->getMessage());

            throw new ProductSynchronizerException('Could not reach Shopify API. The error message: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * @throws ProductSynchronizerException
     */
    protected function getProductsToUpdateInShopify(): ProductCollection
    {
        $sql = "SELECT p.*, c.title as category_title FROM `products` p 
        LEFT JOIN categories c ON(p.category_id = c.id) 
        WHERE 
        `shopifyId` IS NOT NULL
        AND `locked` = 0
        AND `add_status` = '" . self::SUCCESS . "'
        AND `update_status` = '" . self::NOT_UPDATED . "'
        AND `reparsed` = 1
        
        LIMIT 0,50";

        $query = $this->db->query($sql);

        if (!$query) {
            throw new ProductSynchronizerException("Couldn't get new products to update, sql error");
        }

        $productFactory = new ProductFactory();
        $products = new ProductCollection();
        while ($q = $this->db->fetch($query)) {
            if ($q === false) {
                continue;
            }

            $newProduct = $productFactory->createFromDb($q);
            $products->add($newProduct);
        }

        return $products;
    }

    /**
     * @throws ProductSynchronizerException
     */
    protected function setUpdateStatus(ProductId $id, string $status): void
    {
        $sql = "UPDATE `products` SET `update_status`='{$status}' WHERE id='{$id->toString()}'";

        $query = $this->db->query($sql);

        if (!$query) {
            throw new ProductSynchronizerException("Couldn't set the status of product {$id->toString()}!");
        }
    }
}
