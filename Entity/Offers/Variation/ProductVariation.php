<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

namespace BaksDev\Products\Product\Entity\Offers\Variation;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Products\Category\Type\Offers\Variation\ProductCategoryVariationUid;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

// Вариант в торговом предложения

#[ORM\Entity]
#[ORM\Table(name: 'product_variation')]
#[ORM\Index(columns: ['const'])]
#[ORM\Index(columns: ['article'])]
class ProductVariation extends EntityEvent
{
    public const TABLE = 'product_variation';

    /** ID варианта торгового предложения */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: ProductVariationUid::TYPE)]
    private ProductVariationUid $id;

    /** ID торгового предложения  */
    #[Assert\NotBlank]
    #[ORM\ManyToOne(targetEntity: ProductOffer::class, inversedBy: 'variation')]
    #[ORM\JoinColumn(name: 'offer', referencedColumnName: 'id')]
    private ProductOffer $offer;

    /** Постоянный уникальный идентификатор варианта */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: ProductVariationConst::TYPE)]
    private readonly ProductVariationConst $const;

    /** ID торгового предложения категории */
    #[Assert\Uuid]
    #[ORM\Column(name: 'category_variation', type: ProductCategoryVariationUid::TYPE, nullable: true)]
    private ?ProductCategoryVariationUid $categoryVariation = null;

    /** Заполненное значение */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $value = null;

    /** Артикул */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $article = null;

    /** Постфикс */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $postfix = null;

    /** Стоимость торгового предложения */
    #[ORM\OneToOne(mappedBy: 'variation', targetEntity: Price\ProductVariationPrice::class, cascade: ['all'])]
    private ?Price\ProductVariationPrice $price = null;

    /** Количественный учет */
    #[ORM\OneToOne(mappedBy: 'variation', targetEntity: Quantity\ProductVariationQuantity::class, cascade: ['all'])]
    private ?Quantity\ProductVariationQuantity $quantity = null;

    /** Дополнительные фото торгового предложения */
    #[Assert\Valid]
    #[ORM\OneToMany(mappedBy: 'variation', targetEntity: Image\ProductVariationImage::class, cascade: ['all'])]
    private Collection $image;

    /** Коллекция вариаций в торговом предложении  */
    #[Assert\Valid]
    #[ORM\OneToMany(mappedBy: 'variation', targetEntity: Modification\ProductModification::class, cascade: ['all'])]
    private Collection $modification;

    public function __construct(ProductOffer $offer)
    {
        $this->id = new ProductVariationUid();
        // $this->const = new ProductVariationConst();
        $this->offer = $offer;

        $this->price = new Price\ProductVariationPrice($this);
        $this->quantity = new Quantity\ProductVariationQuantity($this);
        // $this->images = new ArrayCollection();
    }

    public function __clone()
    {
        $this->id = clone $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): ProductVariationUid
    {
        return $this->id;
    }

    public function getOffer(): ProductOffer
    {
        return $this->offer;
    }

    /**
     * Const.
     */
    public function getConst(): ProductVariationConst
    {
        return $this->const;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if ($dto instanceof ProductVariationInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if ($dto instanceof ProductVariationInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    /**
     * Image
     */
    public function getImage(): Collection
    {
        return $this->image;
    }

    /**
     * Modification
     */
    public function getModification(): Collection
    {
        return $this->modification;
    }


}