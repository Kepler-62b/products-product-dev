<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Products\Product\Entity\Category;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'product_categories_product')]
class ProductCategory extends EntityEvent
{
    public const TABLE = "product_categories_product";

    /** ID события */
    #[Assert\NotBlank]
    #[Assert\Type(ProductEvent::class)]
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: ProductEvent::class, inversedBy: "category")]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: "id")]
    private ProductEvent $event;

    /** ID Category */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[Assert\Type(CategoryProductUid::class)]
    #[ORM\Id]
    #[ORM\Column(type: CategoryProductUid::TYPE, nullable: false)]
    private CategoryProductUid $category;

    /** Корневая категория */
    #[Assert\Type('bool')]
    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private bool $root = false;

    public function __construct(ProductEvent $event)
    {
        $this->event = $event;
    }

    public function __toString(): string
    {
        return (string) $this->event;
    }

    /**
     * Root
     */
    public function isRoot(): bool
    {
        return $this->root;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof ProductCategoryInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof ProductCategoryInterface || $dto instanceof self)
        {
            if($dto->getCategory() === null)
            {
                return false;
            }

            return parent::setEntity($dto);
        }
        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function getCategory(): CategoryProductUid
    {
        return $this->category;
    }

}
