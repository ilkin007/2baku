<?php
declare(strict_types=1);

namespace App\Zalando\Domain\Product;

use App\Zalando\Domain\GenericFloat;
use App\Zalando\Domain\GenericString;

class Product
{
    public function __construct(
        private ProductId $id,
        private ?ShopifyId $shopifyId = null,
        private ProductLink $link,
        private GenericString $title,
        private GenericString $color,
        private CategoryCollection $categories,
        private ProductCategory $category,
        private GenericString $brand,
        private GenericString $brandLogo,
        private GenericFloat $oldPrice,
        private ProductPrice $price,
        private ImageCollection $images,
        private DetailCollection $details,
        private TargetGroupCollection $targetGroups,
        private SizeCollection $sizes
    ) {

    }

    public function getId(): ProductId
    {
        return $this->id;
    }

    public function getShopifyId(): ?ShopifyId
    {
        return $this->shopifyId;
    }

    public function getLink(): ProductLink
    {
        return $this->link;
    }

    public function getTitle(): GenericString
    {
        return $this->title;
    }

    public function getColor(): GenericString
    {
        return $this->color;
    }

    public function getCategories(): CategoryCollection
    {
        return $this->categories;
    }

    public function getCategory(): ProductCategory
    {
        return $this->category;
    }

    public function getBrand(): GenericString
    {
        return $this->brand;
    }

    public function getBrandLogo(): GenericString
    {
        return $this->brandLogo;
    }

    public function getOldPrice(): GenericFloat
    {
        return $this->oldPrice;
    }

    public function getPrice(): ProductPrice
    {
        return $this->price;
    }

    public function getImages(): ImageCollection
    {
        return $this->images;
    }

    public function getDetails(): DetailCollection
    {
        return $this->details;
    }

    public function getTargetGroups(): TargetGroupCollection
    {
        return $this->targetGroups;
    }

    public function getSizes(): SizeCollection
    {
        return $this->sizes;
    }


    public function setId(ProductId $id): void
    {
        $this->id = $id;
    }

    public function setShopifyId(ShopifyId $shopifyId): void
    {
        $this->shopifyId = new ShopifyId($shopifyId);
    }

    public function setLink(ProductLink $link): void
    {
        $this->link = $link;
    }

    public function setTitle(string $title): void
    {
        $this->title = new GenericString($title);
    }

    public function setColor(string $color): void
    {
        $this->color = new GenericString($color);
    }

    public function setCategories(array $categories): void
    {
        $this->categories = new CategoryCollection($categories);
    }

    public function setCategory(ProductCategory $category): void
    {
        $this->category = $category;
    }

    public function setBrand(string $brand): void
    {
        $this->brand = new GenericString($brand);
    }

    public function setBrandLogo(string $brandLogo): void
    {
        $this->brandLogo = new GenericString($brandLogo);
    }

    public function setOldPrice(float $oldPrice): void
    {
        $this->oldPrice = new GenericFloat($oldPrice);
    }

    public function setPrice(ProductPrice $price): void
    {
        $this->price = $price;
    }

    public function setImages(array $images): void
    {
        $this->images = new ImageCollection($images);
    }

    public function setDetails(array $details): void
    {
        $this->details = new DetailCollection($details);
    }

    public function setTargetGroups(array $targetGroups): void
    {
        $this->targetGroups = new TargetGroupCollection($targetGroups);
    }

    public function setSizes(SizeCollection $sizes): void
    {
        $this->sizes = $sizes;
    }


}
