<?php

namespace App\Zalando;

use App\Zalando\Domain\Product\Factory\ProductFactory;
use App\Zalando\Domain\Product\Product;
use App\Zalando\Domain\Product\ProductCategory;
use App\Zalando\Domain\Product\ProductCollection;
use App\Zalando\Domain\Product\ProductId;
use App\Zalando\Exception\MarginNotFoundException;
use App\Zalando\Exception\ProductSynchronizerException;

class CreateSynchronizer extends AbstractSynchronizer
{
    /**
     * @throws ProductSynchronizerException
     */
    public function initialize(): void
    {
        //$this->removeDuplicates();
        $products = $this->getProductsToAddToShopify();

        if ($products->count() === 0) {
            echo "No products to add!";

            exit;
        }

        /** @var Product $product */
        foreach ($products as $product) {
            $this->setAddStatus($product->getId(), self::PENDING);
        }

        foreach ($products as $product) {
            try {
                $product = $this->addMargin($product);
            } catch (MarginNotFoundException $e) {
                $this->setAddStatus($product->getId(), self::FAILED);

                continue;
            }

            $mappedProduct = $this->mapProductToShopify($product);

            if (count($mappedProduct) === 0) {
                $this->setAddStatus($product->getId(), self::OUT_OF_STOCK);

                continue;
            }

            try {
                $this->createProductOnShopify($mappedProduct, $product->getId());
                $this->setAddStatus($product->getId(), self::SUCCESS);
                $this->removeProductImages($product->getId());
            } catch (\Exception $e) {
                $this->setAddStatus($product->getId(), self::FAILED);
                $this->log->error("Error while adding product to Shopify. The message:" . $e->getMessage());
            }
        }
    }

    private function removeProductImages(ProductId $productId): void
    {
        $hash = md5($productId->toString() . '2bakuIsVerySaltY20202021');

        $result = file_get_contents('https://img.2baku.de/remover.php?id='
            . $productId->toString() . '&hash=' . $hash);

        if (!$result || $result === 'No folder' || $result === 'Wrong hash') {
            $this->log->critical('Could not remove images of the product id '
                . $productId->toString() . ' | The reason: ' . $result);
        }
    }

    public function mapProductToShopify(Product $product): array
    {
        $productVariants = [];
        $productDetails = $product->getDetails();
        $sku = $productDetails->offsetGet('heading_details')['config_sku'];

        $productDetails->offsetUnset('heading_beauty_details');

        $headingDetails = $productDetails->offsetGet('heading_details');
        if (isset($headingDetails['config_sku'])) {
            unset($headingDetails['config_sku']);
        }
        $productDetails->offsetSet('heading_details', $headingDetails);

        $productDetails->offsetUnset('heading_material_highlights');

        $sizes = $this->removeSizesIfNotEnoughQuantity($product->getSizes());

        if ($sizes->count() === 0) {
            //Not enough sizes available for the product!
            return [];
        }

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
                'requires_shipping' => true,
                'taxable' => true,
                'title' => $product->getTitle()->toString(),
                'sku' => $sku
            ];
        }

        $images = [];

        foreach ($product->getImages() as $image) {
            $images[] = [
                "src" => $image
            ];
        }

        $bodyHtml = '';

        $productDetailsArray = $productDetails->toArray();
        foreach ($productDetailsArray as $heading => $productDetail) {
            $heading = $this->basicHeaderTranslator($heading);

            $bodyHtml .= '<h3>' . $heading . '</h3>';

            foreach ($productDetail as $key => $value) {
                $bodyHtml .= $key . ': ' . $value . "<br />\n";
            }
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
            'body_html' => $bodyHtml,
            'handle' => urlencode($product->getTitle()->toString()),
            'images' => $images,
            'options' => [
                ['name' => 'size'],
                ['name' => 'color']
            ],
            'product_type' => $product->getCategory()->toString(),
            'tags' => $tags,
            'title' => $product->getTitle()->toString(),
            'variants' => $productVariants,
            'vendor' => $product->getBrand()->toString(),
        ];

        return $mappedProduct;
    }

    /**
     * @throws ProductSynchronizerException
     */
    private function getProductsToAddToShopify(): ProductCollection
    {
        $sql = "SELECT p.*, c.title category_title FROM `products` p
                LEFT JOIN categories c ON(p.category_id = c.id) 
                WHERE `add_status`='" . self::NEW . "' LIMIT 0,50";

        $query = $this->db->query($sql);

        if (!$query) {
            throw new ProductSynchronizerException("Couldn't get new products to add, sql error");
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
    protected function setAddStatus(ProductId $id, string $status): void
    {
        $sql = "UPDATE `products` SET `add_status`='{$status}' WHERE id='{$id->toString()}'";

        $query = $this->db->query($sql);

        if (!$query) {
            throw new ProductSynchronizerException("Couldn't set the status of product {$id->toString()}!");
        }
    }

    public function deleteAllProducts(): void
    {
        $productIdList = [];

        $x = $this->shopify->Product()->get(['limit' => 50]);
        $c = 0;

        foreach ($x as $product) {
            $productIdList[] = $product['id'];
        }

        foreach ($productIdList as $productId) {
            try {
                $z = $this->shopify->Product($productId)->delete();
                $c++;
            } catch (\Throwable $e) {

            }
        }

        echo $c;
    }
}
