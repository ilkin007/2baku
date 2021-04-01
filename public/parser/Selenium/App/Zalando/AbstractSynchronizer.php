<?php

namespace App\Zalando;

use App\Connect;
use App\Zalando\Domain\Product\Product;
use App\Zalando\Domain\Product\ProductId;
use App\Zalando\Domain\Product\ProductPrice;
use App\Zalando\Domain\Product\SizeCollection;
use App\Zalando\Exception\MarginNotFoundException;
use App\Zalando\Exception\ProductSynchronizerException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPShopify\Exception\ApiException;
use PHPShopify\ShopifySDK;

abstract class AbstractSynchronizer
{
    protected $shopify;
    protected $db;
    protected $log;
    protected $website;

    const NEW = 'new';
    const NOT_UPDATED = 'not_updated';
    const PENDING = 'pending';
    const FAILED = 'failed';
    const OUT_OF_STOCK = 'out_of_stock';
    const SUCCESS = 'success';

    protected const CREATE_STATUSES = [
        self::NEW,
        self::PENDING,
        self::FAILED,
        self::OUT_OF_STOCK,
        self::SUCCESS,
    ];

    protected const UPDATE_STATUSES = [
        self::NOT_UPDATED,
        self::PENDING,
        self::FAILED,
        self::OUT_OF_STOCK,
        self::SUCCESS,
    ];

    public function __construct(array $shopifyAuthConfig, Connect $db, Logger $log, string $website)
    {
        $this->shopify = new ShopifySDK($shopifyAuthConfig);
        $this->db = $db;
        $this->log = $log;
        $this->website = $website;

        $logName = $this->log->getName();
        $log->pushHandler(new StreamHandler('/var/www/html/parser/Selenium/App/' . ucfirst($this->website) . '/log/' . $logName . '.log',
            Logger::INFO));
    }

    /**
     * @throws ProductSynchronizerException
     * @throws MarginNotFoundException
     */
    public function addMargin(Product $product): Product
    {
        $margin = $this->getMargin($product->getPrice());

        if ($margin === 0) {
            $this->log->critical('Error! Can not get margin data from database!');

            throw new MarginNotFoundException();
        }

        return $this->setMargin($product, $margin);
    }

    /**
     * @throws ProductSynchronizerException
     */
    public abstract function initialize(): void;

    protected function getMargin(ProductPrice $price): int
    {
        $price = $price->toFloat();
        $price = $price * 100; //convert price to int, to make sql between work properly (inclusive value)
        $price = (int)$price;

        $sql = "SELECT `marginPercent` FROM `margins` WHERE {$price} >= `fromPrice` AND {$price} <= `toPrice`";
        $query = $this->db->query($sql);

        if (!$query) {
            throw new ProductSynchronizerException("Couldn't get price margins!");
        }

        if ($this->db->numRows($query) === 0) {
            return 0;
        }

        $q = $this->db->fetch($query);

        return (int)$q['marginPercent'];
    }

    protected function setMargin(Product $product, int $margin): Product
    {
        $oldPrice = $product->getOldPrice()->toFloat() * (($margin + 100) / 100);
        $price = $product->getPrice()->toFloat() * (($margin + 100) / 100);

        $product->setOldPrice($oldPrice);
        $product->setPrice(new ProductPrice($price));

        return $product;
    }

    public function removeSizesIfNotEnoughQuantity(SizeCollection $sizes): SizeCollection
    {
        $productSizesArray = $sizes->toArray();

        foreach ($productSizesArray as $size => $quantity) {
            if ($quantity < 10) {
                $sizes->offsetUnset($size);
            }
        }

        return $sizes;
    }

    protected function basicHeaderTranslator(string $word)
    {
        $translates = [
            'heading_material' => 'Material & care',
            'heading_measure_and_fitting' => 'Size & fit',
        ];

        foreach ($translates as $key => $translate) {
            if ($key === $word) {
                return $translate;
            }
        }
    }

    public abstract function mapProductToShopify(Product $product): array;

    /**
     * @throws ApiException
     * @throws ProductSynchronizerException
     * @throws \PHPShopify\Exception\CurlException
     */
    protected function createProductOnShopify(array $mappedProduct, ProductId $productId): void
    {
        $response = $this->shopify->Product->post($mappedProduct);

        $this->setProductShopifyId($productId, $response['id']);
    }

    protected function getShopifyProductInventories(string $shopifyId): array
    {
        $product = $this->shopify->Product($shopifyId)->get();
        $productVariants = $product['variants'];

        $inventories = [];
        foreach ($productVariants as $productVariant) {
            $size = $productVariant['option1'];
            $color = $productVariant['option2'];
            $inventories[$size . '|' . $color] = $productVariant['inventory_item_id'];
        }

        return $inventories;
    }

    /**
     * @throws ProductSynchronizerException
     */
    protected function setProductShopifyId(ProductId $id, int $shopifyId): void
    {
        $sql = "UPDATE `products` SET `shopifyId`='{$shopifyId}' WHERE `id`='{$id->toString()}'";
        $query = $this->db->query($sql);

        if (!$query) {
            throw new ProductSynchronizerException("Couldn't set shopify id to the product with id {$id->toString()} on staging!");
        }
    }

    protected function removeDuplicates(): void
    {
        $sql = "DELETE p
                FROM   products p
                JOIN
                (
                    SELECT   id
                    FROM     products
                    GROUP BY `link`
                    HAVING   COUNT(`link`) > 1
                ) b ON p.id = b.id
                ";

        $query = $this->db->query($sql);

        if ($query) {
            $this->log->info('Removed duplicates');
        } else {
            $this->log->error('Error while getting duplicate products to delete');
        }
    }

    protected function getPriceRange(ProductPrice $price): string
    {
        $price = $price->toFloat();

        $priceRanges = [
            '0-25' => [0, 25],
            '25-50' => [25.01, 50],
            '50-100' => [50.01, 100],
            '100-200' => [100.01, 200],
            '200+' => [200.01, 100000]
        ];

        foreach ($priceRanges as $name => $priceRange) {
            if ($price >= $priceRange[0] && $price <= $priceRange[1]) {
                return $name;
            }
        }

        return '';
    }
}
