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

declare(strict_types=1);

namespace BaksDev\Products\Product\Repository\ProductOfferChoice;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Trans\CategoryProductOffersTrans;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Quantity\ProductOfferQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Quantity\ProductModificationQuantity;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Offers\Variation\Quantity\ProductVariationQuantity;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use Generator;

final class ProductOfferChoiceRepository implements ProductOfferChoiceInterface
{

    private ORMQueryBuilder $ORMQueryBuilder;
    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(
        ORMQueryBuilder $ORMQueryBuilder,
        DBALQueryBuilder $DBALQueryBuilder
    )
    {
        $this->ORMQueryBuilder = $ORMQueryBuilder;
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }

    /**
     * Метод возвращает все постоянные идентификаторы CONST торговых предложений продукта
     */
    public function fetchProductOfferByProduct(ProductUid $product): ?array
    {
        $qb = $this->ORMQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $select = sprintf('new %s(
            offer.const, 
            offer.value, 
            trans.name, 
            category_offer.reference
        )', ProductOfferConst::class);

        $qb->select($select);

        $qb
            ->from(Product::class, 'product')
            ->where('product.id = :product')
            ->setParameter('product', $product, ProductUid::TYPE);

        $qb
            ->join(
                ProductOffer::class,
            'offer',
            'WITH',
            'offer.event = product.event'
        );

        // Тип торгового предложения

        $qb
            ->join(
                CategoryProductOffers::class,
            'category_offer',
            'WITH',
            'category_offer.id = offer.categoryOffer'
        );


        $qb
            ->leftJoin(
                CategoryProductOffersTrans::class,
                'category_offer_trans',
            'WITH',
                'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
        );


        /* Кешируем результат ORM */
        return $qb->enableCache('products-product', 86400)->getResult();

    }


    /**
     * Метод возвращает все идентификаторы торговых предложений продукта по событию
     */
    public function fetchProductOfferByProductEvent(ProductEventUid $product): ?array
    {

        $qb = $this->ORMQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $select = sprintf('new %s(
            offer.id, 
            offer.value, 
            trans.name, 
            category_offer.reference
        )', ProductOfferUid::class);

        $qb->select($select);

        $qb
            ->from(Product::class, 'product')
            ->where('product.event = :product')
            ->setParameter('product', $product, ProductEventUid::TYPE);

        //        $qb->join(
        //            ProductEvent::class,
        //            'event',
        //            'WITH',
        //            'event.id = product.event'
        //        );

        $qb->join(
            ProductOffer::class,
            'offer',
            'WITH',
            'offer.event = product.event'
        );

        // Тип торгового предложения

        $qb->join(
            CategoryProductOffers::class,
            'category_offer',
            'WITH',
            'category_offer.id = offer.categoryOffer'
        );


        $qb->leftJoin(
            CategoryProductOffersTrans::class,
            'category_offer_trans',
            'WITH',
            'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
        );


        /* Кешируем результат ORM */
        return $qb->enableCache('products-product', 86400)->getResult();

    }


    /**
     * Метод возвращает все идентификаторы торговых предложений продукта по событию имеющиеся в доступе
     */
    public function fetchProductOfferExistsByProductEvent(ProductEventUid|string $product): Generator
    {
        if(is_string($product))
        {
            $product = new ProductEventUid($product);
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->from(Product::class, 'product')
            ->where('product.event = :product')
            ->setParameter('product', $product, ProductEventUid::TYPE);


        $dbal->join(
            'product',
            ProductOffer::class,
            'product_offer',
            'product_offer.event = product.event'
        );

        // Тип торгового предложения

        $dbal->leftJoin(
            'product_offer',
            CategoryProductOffers::class,
            'category_offer',
            'category_offer.id = product_offer.category_offer'
        );


        $dbal->leftJoin(
            'category_offer',
            CategoryProductOffersTrans::class,
            'category_offer_trans',
            'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
        );


        $dbal->leftJoin(
            'product_offer',
            ProductVariation::class,
            'product_variation',
            'product_variation.offer = product_offer.id'
        );

        $dbal->leftJoin(
            'product_variation',
            ProductModification::class,
            'product_modification',
            'product_modification.variation = product_variation.id'
        );

        /**
         * Quantity
         */

        $dbal->leftJoin(
            'product_offer',
            ProductOfferQuantity::class,
            'product_offer_quantity',
            'product_offer_quantity.offer = product_offer.id'
        );

        $dbal->leftJoin(
            'product_variation',
            ProductVariationQuantity::class,
            'product_variation_quantity',
            'product_variation_quantity.variation = product_variation.id'
        );

        $dbal->leftJoin(
            'product_modification',
            ProductModificationQuantity::class,
            'product_modification_quantity',
            'product_modification_quantity.modification = product_modification.id'
        );


        //        $select = sprintf('new %s(
        //            offer.id,
        //            offer.value,
        //            trans.name,
        //            category_offer.reference
        //        )', ProductOfferUid::class);
        //
        //        $dbal->select($select);

        $dbal->addSelect('product_offer.id AS value');
        $dbal->addSelect("CONCAT(product_offer.value, ' ', product_offer.postfix) AS attr");


        $dbal->addSelect('

            CASE
               WHEN SUM(product_modification_quantity.quantity - product_modification_quantity.reserve) > 0
               THEN SUM(product_modification_quantity.quantity - product_modification_quantity.reserve)

               WHEN SUM(product_variation_quantity.quantity - product_variation_quantity.reserve) > 0
               THEN SUM(product_variation_quantity.quantity - product_variation_quantity.reserve)

               WHEN SUM(product_offer_quantity.quantity - product_offer_quantity.reserve) > 0
               THEN SUM(product_offer_quantity.quantity - product_offer_quantity.reserve)

               ELSE 0
            END

        AS option');


        $dbal->addSelect('category_offer_trans.name AS property');
        $dbal->addSelect('category_offer.reference AS characteristic');

        $dbal->allGroupByExclude();

        $dbal->andWhere('
            product_modification_quantity.quantity > 0 OR 
            product_variation_quantity.quantity > 0 OR 
            product_offer_quantity.quantity > 0 
        ');

        return $dbal->fetchAllHydrate(ProductOfferUid::class);


    }

}
