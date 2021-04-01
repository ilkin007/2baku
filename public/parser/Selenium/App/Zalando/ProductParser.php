<?php
declare(strict_types=1);

namespace App\Zalando;

use App\Services\Exception\ParserException;
use App\Services\Task\Task;
use App\Zalando\Domain\Product\Product;
use App\Zalando\Domain\Product\ProductCategory;
use App\Zalando\Domain\Product\Factory\ProductFactory;
use App\Zalando\Domain\Product\ProductId;
use App\Zalando\Domain\Product\ProductLink;
use App\Zalando\Domain\Product\ProductPrice;
use App\Zalando\Domain\Product\SizeCollection;
use Ramsey\Uuid\Uuid;

class ProductParser extends ZalandoParser
{
    public function parse(ProductCategory $category): string
    {
        try {
            return $this->initialize($category);
        } catch (\Throwable $e) {
            $this->turnOffBrowser();
            $this->log->critical('Unknown error! Product url:' . $e->getMessage());

            return Task::FAILED;
        }
    }

    /**
     * @throws ParserException
     */
    public function initialize(ProductCategory $category): string
    {
        $startTime = microtime(true);

        $productsLinksToParse = $this->parseProductColorVariants($this->task->getLink());
        $productsCount = count($productsLinksToParse);
        if ($productsCount === 0) {
            $message = 'No products found for the category link' . $this->task->getLink();
            $this->log->error($message);

            throw new ParserException($message);
        }

        $counter = 1;
        $successfulCounter = 0;

        $generalId = (string)Uuid::uuid1();

        foreach ($productsLinksToParse as $link) {
            try {
                $id = (string)Uuid::uuid3($generalId, $link);
                $product = $this->parseProduct(new ProductLink($link), $category, new ProductId($id));
                $successfulCounter++;
            } catch (\Throwable $e) {
                $this->log->critical($e->getMessage() . '| SKIPPED');

                continue;
            }

            $fillData = $this->prepareNormalizedInsertSql($product, $id, $generalId);
            $this->normalizeAndInsertProduct($fillData);

            $counter++;
        }

        $this->turnOffBrowser();
        $endTime = microtime(true);
        $elapsed = $endTime - $startTime;

        //$this->log->info("Execution time : $elapsed seconds for " . $successfulCounter . " products");

        if ($successfulCounter > 0) {
            return Task::SUCCESS;
        }

        return Task::DUPLICATE;
    }

    /**
     * @throws ParserException
     */
    public function parseProductForUpdate(
        ProductLink $link,
        ProductId $productId,
    ): Product {
        $productFactory = new ProductFactory();
        $product = $productFactory->createBlank();

        $htmlPage = $this->curl($link->toString());
        $productData = $this->getProductData($htmlPage);

        if ($productData === null) {
            throw new ParserException('No data! : '. $link->toString());
        }

        $article = $productData['model']['articleInfo'];

        //Throw an error if the product is not active anymore
        if (!$article['active'] && !$article['available']) {
            throw new ParserException('The product is not active!');
        }

        if (
            isset($article['units'])
            && isset($article['units']['isPriceDifferent'])
            && $article['units']['isPriceDifferent'] === true
        ) {
            throw new ParserException('Price is different for the sizes!');
        }

        $product->setOldPrice($article['displayPrice']['originalPrice']['value']);
        $product->setPrice(new ProductPrice($article['displayPrice']['price']['value']));

        $product->setId($productId);

        $sizes = $this->parseSizes($article);
        if ($sizes === null) {
            throw new ParserException('Out of stock due to no sizes');
        }

        $product->setSizes(new SizeCollection($sizes));

        return $product;
    }

    private function isProductExists(ProductLink $productLink): bool
    {
        $sql = "SELECT * FROM `products` WHERE
        `link` = '{$productLink->toString()}'";

        $query = $this->db->query($sql);

        if (!$query) {
            $this->log->error("Error while getting random product to update!");
        }

        return (bool)$this->db->numRows($query);
    }

    //private function addNewCategoryToProduct(ProductLink $productLink, ProductCategory $productCategory): void
    //{
    //    $sql =
    //}

    /**
     * @throws ParserException
     */
    public function parseProduct(
        ProductLink $link,
        ProductCategory $category,
        ProductId $productId
    ): Product {
        if ($this->isProductExists($link)) {
            //$this->addNewCategoryToProduct($link, $category);

            throw new ParserException('Product already exists in the database!');
        }

        $productFactory = new ProductFactory();
        $product = $productFactory->createBlank();

        $htmlPage = $this->curl($link->toString());
        $productData = $this->getProductData($htmlPage);

        if ($productData === null) {
            throw new ParserException('No data! : '. $link->toString());
        }

        $article = $productData['model']['articleInfo'];

        if ((!$article['active'] || !$article['available'])) {
            throw new ParserException('Sold out!');
        }

        $product->setTitle($article['name']);
        $product->setColor($article['color']);
        $product->setCategories($article['categories']);
        $product->setCategory($category);
        $product->setBrand($article['brand']['name']);
        $product->setBrandLogo($article['brand']['logoUrl']);

        if (
            isset($article['units'])
            && isset($article['units']['isPriceDifferent'])
            && $article['units']['isPriceDifferent'] === true
        ) {
            throw new ParserException('Price is different for the sizes!');
        }

        $product->setOldPrice($article['displayPrice']['originalPrice']['value']);
        $product->setPrice(new ProductPrice($article['displayPrice']['price']['value']));

        $images = [];
        $i = 1;
        foreach ($article['media']['images'] as $image) {

            $imageUrl = $this->downloadImage($productId, $image['sources']['zoom'], $i, $link);
            //TODO: find the issue why only every second image is reachable
            if ($imageUrl === null) {
                continue;
            }
            $images[] = $imageUrl;
            $i++;
        }

        $skipProduct = true;
        foreach ($images as $image) {
            if ($image !== null) {
                $skipProduct = false;
            }
        }

        if (count($images) === 0 && $skipProduct) {
            throw new ParserException('No images: '.$product->getLink()->toString());
        }

        $product->setImages($images);

        $details = [];
        foreach ($article['attributes'] as $attribute) {
            $heading = $attribute['category'];

            $details[$heading] = [];
            foreach ($attribute['data'] as $element) {
                $details[$heading][$element['name']] = $element['values'];
            }
        }

        $product->setDetails($details);

        $product->setId($productId);
        $product->setLink($link);

        $product->setTargetGroups($article['targetGroups']);
        //$product->setModelHeight($article['modelHeight']);

        $colors = [];

        foreach ($article['colors'] as $color) {
            $colors[$color['color']] = [
                'link' => $color['shopUrl'],
                'image' => $color['pictureUrl'],
            ];
        }

        //$product->setColors($colors);

        $sizes = $this->parseSizes($article);
        if ($sizes === null) {
            throw new ParserException('Out of stock due to no sizes');
        }

        $product->setSizes(new SizeCollection($sizes));

        return $product;
    }

    private function parseProductColorVariants(string $link): array
    {
        $htmlPage = $this->curl($link);
        $productData = $this->getProductData($htmlPage);

        if ($productData === null) {
            throw new ParserException('No product data, check for ban!');
        }

        $article = $productData['model']['articleInfo'];

        $productLinks = [];
        foreach ($article['colors'] as $color) {
            $productLinks[] = $color['shopUrl'];
        }

        return $productLinks;
    }

    public function downloadImage(
        ProductId $productId,
        string $imageUrl,
        int $iterator,
        ProductLink $productLink
    ): ?string {
        $urlString = 'https://img.2baku.de/downloader.php';
        $urlString .= '?productLink=' . $productLink->toString();
        $urlString .= '&imageUrl=' . $imageUrl;
        $urlString .= '&iterator=' . $iterator;
        $urlString .= '&productId=' . $productId->toString();

        $result = file_get_contents($urlString);

        if (!$result) {
            throw new \Exception('Warning! We couldn\'t reach the image server to download an image!');
        }

        if (str_contains($result, 'img.2baku')) {
            return $result;
        } else {
            //$this->log->error($result);

            return null;
        }
    }

    private function prepareNormalizedInsertSql(Product $product, string $id, string $generalId): array
    {
        $createdAt = date('Y-m-d H:i:s');

        return [
            'id' => $id,
            'generalId' => $generalId,
            'link' => $product->getLink()->toDbString(),
            'sizes' => $product->getSizes()->toJson(),
            'color' => $product->getColor()->toDbString(),
            'categories' => $product->getCategories()->toJson(),
            'category_id' => $product->getCategory()->getId(),
            'brand' => $product->getBrand()->toDbString(),
            'title' => $product->getTitle()->toDbString(),
            'price' => $product->getPrice()->toFloat(),
            'oldPrice' => $product->getOldPrice()->toFloat(),
            'images' => $product->getImages()->toJson(),
            'details' => $product->getDetails()->toJson(),
            'target_groups' => $product->getTargetGroups()->toJson(),
            'created_at' => $createdAt,
            'website' => 'zalando',
        ];
    }

    private function normalizeAndInsertProduct(array $fillData): bool
    {
        $sql = $this->db->con->prepare("INSERT INTO products
        (
        `id`,
        `generalId`,
        `link`,
        `sizes`,
        `color`,
        `categories`,
        `category_id`,
        `brand`,
        `title`,
        `price`,
        `oldPrice`,
        `images`,
        `details`,
        `target_groups`,
        `created_at`,
        `website`
        )
        VALUES
        (
         :id,
         :generalId,
         :link,
         :sizes,
         :color,
         :categories,
         :category_id,
         :brand,
         :title,
         :price,
         :oldPrice,
         :images,
         :details,
         :target_groups,
         :created_at,
         :website
        )
        ");

        foreach ($fillData as $key => &$value) {
            $sql->bindParam(':' . $key, $value);
        }

        $result = $sql->execute();

        if ($result) {
            //$this->log->info('Product was normalized/inserted');

            return true;
        } else {
            $this->log->error('Products were not normalized/inserted, sql error! ');

            return false;
        }
    }

    public function normalizeAndUpdateProductDataInStagingDb(Product $product): bool
    {
        $sql = $this->db->con->prepare("
        UPDATE products SET
            `sizes` = :sizes,
            `price` = :price,
            `oldPrice` = :oldPrice
        WHERE `id` = :id
        ");

        $sizes = $product->getSizes()->toJson();
        $price = $product->getPrice()->toFloat();
        $oldPrice = $product->getOldPrice()->toFloat();
        $id = $product->getId()->toString();

        $sql->bindParam(':sizes', $sizes);
        $sql->bindParam(':price', $price);
        $sql->bindParam(':oldPrice', $oldPrice);
        $sql->bindParam(':id', $id);

        $result = $sql->execute();

        if ($result) {
            //$this->log->info('Product was normalized/updated');

            return true;
        } else {
            $this->log->error('Products were not normalized/updated, sql error! ');

            return false;
        }
    }
}
