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

namespace BaksDev\Products\Product\Controller\User;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Repository\ProductDetail\ProductDetailByValueInterface;
use BaksDev\Products\Product\Repository\ProductDetailOffer\ProductDetailOfferInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class NotFoundController extends AbstractController
{
    /** Not Found Product */
    #[Route('/catalog/{category}/{url}/{offer}/{variation}/{modification}/notfound',
        name: 'user.notfound',
        priority: 10
    )]
    public function notfound(
        #[MapEntity(mapping: ['url' => 'url'])] ProductInfo $info,
        ProductDetailByValueInterface $productDetail,
        ProductDetailOfferInterface $productDetailOffer,
        ?string $offer = null,
        ?string $variation = null,
        ?string $modification = null,
        ?string $postfix = null,
    ): Response
    {
        $productCard = $productDetail->fetchProductAssociative(
            $info->getProduct(),
            $offer,
            $variation,
            $modification,
            $postfix
        );

        /** Другие ТП данного продукта */
        $productOffer = $productDetailOffer->fetchProductOfferAssociative($info->getProduct());

        return $this->render(
            [
                'card' => $productCard,
                'offers' => $productOffer,
                'offer' => $offer,
                'variation' => $variation,
                'modification' => $modification
            ],
            response: new Response(status: 404)
        );
    }
}