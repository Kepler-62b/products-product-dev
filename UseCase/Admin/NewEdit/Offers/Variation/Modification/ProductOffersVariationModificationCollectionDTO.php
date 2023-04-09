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


namespace BaksDev\Products\Product\UseCase\Admin\NewEdit\Offers\Variation\Modification;

use BaksDev\Products\Category\Type\Offers\Modification\ProductCategoryOffersVariationModificationUid;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductOfferVariationModificationInterface;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductOfferVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductOfferVariationModificationConst;
use Doctrine\Common\Collections\ArrayCollection;
use ReflectionProperty;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

final class ProductOffersVariationModificationCollectionDTO implements ProductOfferVariationModificationInterface
{
	/** ID множественного варианта торгового предложения категории */
	private ProductCategoryOffersVariationModificationUid $categoryModification;
	
	/** Постоянный уникальный идентификатор модификации */
	#[Assert\NotBlank]
	private readonly ProductOfferVariationModificationConst $const;
	
	/** Заполненное значение */
	private ?string $value = null;
	
	/** Артикул */
	private ?string $article = null;
	
	/** Стоимость торгового предложения */
	private ?Price\ProductOfferVariationModificationPriceDTO $price = null;
	
	/** Количественный учет */
	private ?Quantity\ProductOfferVariationModificationQuantityDTO $quantity = null;
	
	/** Дополнительные фото торгового предложения */
	private ArrayCollection $image;
	

	public function __construct()
	{
		$this->image = new ArrayCollection();
	}
	
	
	/** Постоянный уникальный идентификатор модификации */
	
	public function getConst() : ProductOfferVariationModificationConst
	{
		if(!(new ReflectionProperty($this::class, 'const'))->isInitialized($this))
		{
			$this->const = new ProductOfferVariationModificationConst();
		}
		
		return $this->const;
	}
	
	public function setConst(ProductOfferVariationModificationConst $const) : void
	{
		$this->const = $const;
	}
	
	
	/** Заполненное значение */
	
	public function getValue() : ?string
	{
		return $this->value;
	}
	
	
	public function setValue(?string $value) : void
	{
		$this->value = $value;
	}
	
	
	/** Артикул */
	
	public function getArticle() : ?string
	{
		return $this->article;
	}
	
	
	public function setArticle(?string $article) : void
	{
		$this->article = $article;
	}
	
	
	/** Стоимость торгового предложения */
	
	public function getPrice() : ?Price\ProductOfferVariationModificationPriceDTO
	{
		return $this->price;
	}
	
	
	public function setPrice(?Price\ProductOfferVariationModificationPriceDTO $price) : void
	{
		$this->price = $price;
	}
	
	
	/** Количественный учет */
	
	public function getQuantity() : ?Quantity\ProductOfferVariationModificationQuantityDTO
	{
		return $this->quantity;
	}
	
	
	public function setQuantity(?Quantity\ProductOfferVariationModificationQuantityDTO $quantity) : void
	{
		$this->quantity = $quantity;
	}
	
	
	/** Дополнительные фото торгового предложения */
	
	public function getImage() : ArrayCollection
	{
		return $this->image;
	}
	
	
	public function addImage(Image\ProductOfferVariationModificationImageCollectionDTO $image) : void
	{
		if(!$this->image->contains($image))
		{
			$this->image->add($image);
		}
	}
	
	
	public function removeImage(Image\ProductOfferVariationModificationImageCollectionDTO $image) : void
	{
		$this->image->removeElement($image);
	}
	
	
	/**
	 * @return ProductCategoryOffersVariationModificationUid
	 */
	public function getCategoryModification() : ProductCategoryOffersVariationModificationUid
	{
		return $this->categoryModification;
	}
	
	
	/**
	 * @param ProductCategoryOffersVariationModificationUid $categoryVariation
	 */
	public function setCategoryModification(ProductCategoryOffersVariationModificationUid $categoryModification) : void
	{
		$this->categoryModification = $categoryModification;
	}
	

}

