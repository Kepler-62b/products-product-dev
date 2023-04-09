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

namespace BaksDev\Products\Product\UseCase\Admin\NewEdit\Offers\Variation\Modification\Quantity;

use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Quantity\ProductOfferVariationModificationQuantityInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ProductOfferVariationModificationQuantityDTO implements ProductOfferVariationModificationQuantityInterface
{
	/** В наличие */
	private ?int $quantity = null; // 0 - нет в наличие
	
	/** Резерв */
	#[Assert\NotBlank]
	private ?int $reserve = 0;
	
	
	/** В наличие */
	
	public function getQuantity() : ?int
	{
		return $this->quantity;
	}
	
	
	public function setQuantity(?int $quantity) : void
	{
		$this->quantity = $quantity;
	}
	
	
	/** Резерв */
	
	public function getReserve() : ?int
	{
		
		
		return $this->reserve;
	}
	
	
	public function setReserve(?int $reserve) : void
	{
		
		dump($reserve);
		
		$this->reserve = $reserve ?: 0;
	}
	
}

