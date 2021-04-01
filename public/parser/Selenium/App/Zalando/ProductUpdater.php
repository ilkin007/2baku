<?php
declare(strict_types=1);

namespace App\Zalando;


use App\Connect;
use App\Zalando\Domain\Product\Factory\ProductFactory;
use App\Zalando\Domain\Product\Product;
use App\Zalando\Domain\Product\ProductId;
use App\Zalando\Domain\Product\SizeCollection;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ProductUpdater
{
    private $website = 'zalando';

    private $db;
    private $log;
    private $parser;
    private $productSynchronizer;

    public function __construct(
        Connect $db,
        Logger $log
    ) {
        $zalandoDi = new ZalandoDI();

        $this->db = $db;
        $this->log = $log;
        $this->parser = $zalandoDi->getProductParser('productUpdater');
        $this->productSynchronizer = $zalandoDi->getProductSynchronizer('productUpdater');

        $logName = $this->log->getName();
        $log->pushHandler(new StreamHandler('/var/www/html/parser/Selenium/App/' . ucfirst($this->website) . '/log/' . $logName . '.log',
            Logger::INFO));
    }

    public function setAllNotUpdated(): void
    {
        $sql = "UPDATE `products` SET
        `update_status` = '" . AbstractSynchronizer::NOT_UPDATED . "',
        `reparsed` = 0
        ";

        $query = $this->db->query($sql);

        if (!$query) {
            $this->log->error("Error while setting products as not updated!");
        }
    }

    private function getRandomProductToUpdate(): Product
    {
        $sql = "SELECT p.*, c.title as category_title FROM `products` p 
                LEFT JOIN categories c ON (p.category_id = c.id)
                WHERE
                `add_status`='success'
                AND `locked` = 0
                AND `shopifyId` IS NOT NULL
                AND `reparsed` = 0
                AND `update_status` = '" . AbstractSynchronizer::NOT_UPDATED . "'
                ORDER BY RAND() LIMIT 0,1";

        $query = $this->db->query($sql);

        if (!$query) {
            $this->log->error("Error while getting random product to update!");
        }

        $productFactory = new ProductFactory();

        $q = $this->db->fetch($query);

        if ($this->db->numRows($query) === 0 || $q === false) {
            throw new \Exception('No products to update were found in the database!');
        }

        return $productFactory->createFromDb($q);
    }

    private function lockProduct(ProductId $productId): void
    {
        $sql = "UPDATE `products` SET `locked`=1 WHERE id='{$productId->toString()}'";

        $query = $this->db->query($sql);

        if (!$query) {
            $this->log->error("Error while setting as locked the product " . $productId->toString() . "!");
        }
    }

    private function unlockProduct(ProductId $productId): void
    {
        $sql = "UPDATE `products` SET `locked`=0 WHERE id='{$productId->toString()}'";

        $query = $this->db->query($sql);

        if (!$query) {
            $this->log->error("Error while setting as not locked the product " . $productId->toString() . "!");
        }
    }

    /**
     * @throws \Exception
     */
    public function updateRandomProduct(): void
    {
        $product = $this->getRandomProductToUpdate();

        $this->lockProduct($product->getId());

        if (!$this->productSynchronizer->checkWhetherProductExistInShopify($product->getShopifyId())) {
            $this->deleteProductFromStaging($product->getId());

            throw new \Exception('The product is not more in Shopify, so we delete it in Staging database');
        }

        try {
            $updatedProduct = $this->parser->parseProductForUpdate(
                $product->getLink(),
                $product->getId(),
            );
        } catch (\Exception $e) {
            $this->log->critical($e->getMessage());

            $updatedProduct = $product; //there we set updated product as an old one to not update anything
            $updatedProduct->setSizes(new SizeCollection()); //there we set zero sizes to be sure that the product will have 0 sizes on shopify
        }

        $updatedProduct->setSizes(
            $this->productSynchronizer->removeSizesIfNotEnoughQuantity($updatedProduct->getSizes())
        );

        if ($this->parser->normalizeAndUpdateProductDataInStagingDb($updatedProduct)) {
            $this->setProductAsReparsed($product->getId());
        }

        $this->unlockProduct($product->getId());
    }

    private function deleteProductFromStaging(ProductId $productId): void
    {
        $sql = "DELETE FROM `products` WHERE id='{$productId->toString()}'";

        $query = $this->db->query($sql);

        if (!$query) {
            $this->log->error("Error while deleting product {$productId->toString()} from staging!");
        }
    }

    private function setProductAsReparsed(ProductId $id)
    {
        $sql = "UPDATE `products` SET
        `reparsed` =1
        WHERE id='{$id->toString()}'";

        $query = $this->db->query($sql);

        if (!$query) {
            $this->log->error("Error while setting product {$id->toString()} as updated!");
        }
    }
}
