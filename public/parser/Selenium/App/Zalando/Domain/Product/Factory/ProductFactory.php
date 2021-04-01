<?php
declare(strict_types=1);

namespace App\Zalando\Domain\Product\Factory;

use App\Zalando\Domain\GenericFloat;
use App\Zalando\Domain\GenericString;
use App\Zalando\Domain\Product\CategoryCollection;
use App\Zalando\Domain\Product\DetailCollection;
use App\Zalando\Domain\Product\ImageCollection;
use App\Zalando\Domain\Product\Product;
use App\Zalando\Domain\Product\ProductCategory;
use App\Zalando\Domain\Product\ProductId;
use App\Zalando\Domain\Product\ProductLink;
use App\Zalando\Domain\Product\ProductPrice;
use App\Zalando\Domain\Product\ShopifyId;
use App\Zalando\Domain\Product\SizeCollection;
use App\Zalando\Domain\Product\TargetGroupCollection;

class ProductFactory
{
    public function create(
        ProductId $id,
        ?ShopifyId $shopifyId,
        ProductLink $link,
        GenericString $title,
        GenericString $color,
        CategoryCollection $categories,
        ProductCategory $category,
        GenericString $brand,
        GenericString $brandLogo,
        GenericFloat $oldPrice,
        ProductPrice $price,
        ImageCollection $images,
        DetailCollection $details,
        TargetGroupCollection $targetGroups,
        SizeCollection $sizes,
    ): Product {
        return new Product(
            $id,
            $shopifyId,
            $link,
            $title,
            $color,
            $categories,
            $category,
            $brand,
            $brandLogo,
            $oldPrice,
            $price,
            $images,
            $details,
            $targetGroups,
            $sizes,
        );
    }

    public function createFromDb(array $row): Product
    {
        return $this->create(
            new ProductId($row['id']),
            new ShopifyId($row['shopifyId'] ?? ''),
            new ProductLink($row['link']),
            new GenericString($row['title']),
            new GenericString($row['color'] ?? ''),
            new CategoryCollection(json_decode($row['categories'], true)),
            new ProductCategory($row['category_title'], $row['category_id']),
            new GenericString($row['brand']),
            new GenericString($row['brandLogo'] ?? ''),
            new GenericFloat((float)$row['oldPrice'] ?? 0),
            new ProductPrice((float)$row['price']),
            new ImageCollection(json_decode($row['images'], true)),
            new DetailCollection(json_decode($row['details'], true)),
            new TargetGroupCollection(json_decode($row['target_groups'], true)),
            new SizeCollection(json_decode($row['sizes'], true)),
        );
    }

    public function createBlank(): Product
    {
        return $this->create(
            new ProductId(''),
            null,
            new ProductLink(''),
            new GenericString(''),
            new GenericString(''),
            new CategoryCollection([]),
            new ProductCategory(''),
            new GenericString(''),
            new GenericString(''),
            new GenericFloat(0),
            new ProductPrice(0),
            new ImageCollection([]),
            new DetailCollection([]),
            new TargetGroupCollection([]),
            new SizeCollection([]),
        );
    }
}
