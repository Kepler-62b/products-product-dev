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

namespace BaksDev\Products\Product\Repository\AllProducts;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;

//use BaksDev\Products\Category\Entity as CategoryEntity;
use BaksDev\Elastic\Api\Index\ElasticGetIndex;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Entity\Info\CategoryProductInfo;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Trans\CategoryProductTrans;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
use BaksDev\Products\Product\Entity\Description\ProductDescription;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\Price\ProductVariationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Photo\ProductPhoto;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Products\Product\Forms\ProductFilter\Admin\ProductFilterDTO;
use BaksDev\Users\Profile\UserProfile\Entity\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;

final class AllProductsRepository implements AllProductsInterface
{
    private PaginatorInterface $paginator;
    private DBALQueryBuilder $DBALQueryBuilder;

    private ?SearchDTO $search = null;
    private ?ProductFilterDTO $filter = null;
    private ?ElasticGetIndex $elasticGetIndex;

    public function __construct(
        DBALQueryBuilder $DBALQueryBuilder,
        PaginatorInterface $paginator,
        ?ElasticGetIndex $elasticGetIndex = null
    )
    {
        $this->paginator = $paginator;
        $this->DBALQueryBuilder = $DBALQueryBuilder;
        $this->elasticGetIndex = $elasticGetIndex;
    }

    public function search(SearchDTO $search): self
    {
        $this->search = $search;
        return $this;
    }

    public function filter(ProductFilterDTO $filter): self
    {
        $this->filter = $filter;
        return $this;
    }


    public function getAllProductsOffers(UserProfileUid|string $profile): PaginatorInterface
    {
        if(is_string($profile))
        {
            $profile = new UserProfileUid($profile);
        }


        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->select('product.id')
            ->addSelect('product.event')
            ->from(Product::class, 'product');

        $dbal->leftJoin(
            'product',
            ProductEvent::class,
            'product_event',
            'product_event.id = product.event'
        );

        $dbal
            ->addSelect('product_trans.name AS product_name')
            ->leftJoin(
                'product_event',
                ProductTrans::class,
                'product_trans',
                'product_trans.event = product_event.id AND product_trans.local = :local'
            );


        $dbal
            ->addSelect('product_desc.preview AS product_preview')
            ->addSelect('product_desc.description AS product_description')
            ->leftJoin(
                'product_event',
                ProductDescription::class,
                'product_desc',
                'product_desc.event = product_event.id AND product_desc.device = :device '

            )->setParameter('device', 'pc');


        $dbal->andWhere('product_info.profile = :profile OR product_info.profile IS NULL');
        $dbal->setParameter('profile', $profile, UserProfileUid::TYPE);


        /* ProductInfo */

        $dbal
            ->addSelect('product_info.url')
            ->leftJoin(
                'product_event',
                ProductInfo::class,
                'product_info',
                'product_info.product = product.id'
            );


        /** Ответственное лицо (Профиль пользователя) */

        $dbal->leftJoin(
            'product_info',
            UserProfile::class,
            'users_profile',
            'users_profile.id = product_info.profile'
        );

        $dbal
            ->addSelect('users_profile_personal.username AS users_profile_username')
            ->leftJoin(
                'users_profile',
                UserProfilePersonal::class,
                'users_profile_personal',
                'users_profile_personal.event = users_profile.event'
            );


        /** Торговое предложение */

        $dbal
            ->addSelect('product_offer.id as product_offer_id')
            ->addSelect('product_offer.const as product_offer_const')
            ->addSelect('product_offer.value as product_offer_value')
            ->addSelect('product_offer.postfix as product_offer_postfix')
            ->leftJoin(
                'product_event',
                ProductOffer::class,
                'product_offer',
                'product_offer.event = product_event.id'
            );

        if($this->filter->getOffer())
        {
            $dbal->andWhere('product_offer.value = :offer');
            $dbal->setParameter('offer', $this->filter->getOffer());
        }


        /* Тип торгового предложения */
        $dbal
            ->addSelect('category_offer.reference as product_offer_reference')
            ->leftJoin(
                'product_offer',
                CategoryProductOffers::class,
                'category_offer',
                'category_offer.id = product_offer.category_offer'
            );


        /** Множественные варианты торгового предложения */

        $dbal
            ->addSelect('product_variation.id as product_variation_id')
            ->addSelect('product_variation.const as product_variation_const')
            ->addSelect('product_variation.value as product_variation_value')
            ->addSelect('product_variation.postfix as product_variation_postfix')
            ->leftJoin(
                'product_offer',
                ProductVariation::class,
                'product_variation',
                'product_variation.offer = product_offer.id'
            );


        if($this->filter->getVariation())
        {
            $dbal->andWhere('product_variation.value = :variation');
            $dbal->setParameter('variation', $this->filter->getVariation());
        }


        /* Цена множественного варианта */
        $dbal->leftJoin(
            'category_variation',
            ProductVariationPrice::class,
            'product_variation_price',
            'product_variation_price.variation = product_variation.id'
        );


        /* Тип множественного варианта торгового предложения */
        $dbal
            ->addSelect('category_variation.reference as product_variation_reference')
            ->leftJoin(
                'product_variation',
                CategoryProductVariation::class,
                'category_variation',
                'category_variation.id = product_variation.category_variation'
            );


        /** Модификация множественного варианта */
        $dbal
            ->addSelect('product_modification.id as product_modification_id')
            ->addSelect('product_modification.const as product_modification_const')
            ->addSelect('product_modification.value as product_modification_value')
            ->addSelect('product_modification.postfix as product_modification_postfix')
            ->leftJoin(
                'product_variation',
                ProductModification::class,
                'product_modification',
                'product_modification.variation = product_variation.id '
            );


        if($this->filter->getModification())
        {
            $dbal->andWhere('product_modification.value = :modification');
            $dbal->setParameter('modification', $this->filter->getModification());
        }

        /** Получаем тип модификации множественного варианта */
        $dbal
            ->addSelect('category_modification.reference as product_modification_reference')
            ->leftJoin(
                'product_modification',
                CategoryProductModification::class,
                'category_modification',
                'category_modification.id = product_modification.category_modification'
            );


        /** Артикул продукта */

        $dbal->addSelect("
					CASE
					   WHEN product_modification.article IS NOT NULL 
					   THEN product_modification.article
					   
					   WHEN product_variation.article IS NOT NULL 
					   THEN product_variation.article
					   
					   WHEN product_offer.article IS NOT NULL 
					   THEN product_offer.article
					   
					   WHEN product_info.article IS NOT NULL 
					   THEN product_info.article
					   
					   ELSE NULL
					END AS product_article
				"
        );


        /** Фото продукта */

        $dbal->leftJoin(
            'product_event',
            ProductPhoto::class,
            'product_photo',
            'product_photo.event = product_event.id AND product_photo.root = true'
        );

        $dbal->leftJoin(
            'product_offer',
            ProductVariationImage::class,
            'product_variation_image',
            'product_variation_image.variation = product_variation.id AND product_variation_image.root = true'
        );

        $dbal->leftJoin(
            'product_offer',
            ProductOfferImage::class,
            'product_offer_images',
            'product_offer_images.offer = product_offer.id AND product_offer_images.root = true'
        );

        $dbal->addSelect("
			CASE
			   WHEN product_variation_image.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductVariationImage::class)."' , '/', product_variation_image.name)
			   
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductOfferImage::class)."' , '/', product_offer_images.name)
			   
			   WHEN product_photo.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductPhoto::class)."' , '/', product_photo.name)
			   
			   ELSE NULL
			END AS product_image
		"
        );

        /** Флаг загрузки файла CDN */
        $dbal->addSelect("
			CASE
			   WHEN product_variation_image.name IS NOT NULL 
			   THEN product_variation_image.ext
			   
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN product_offer_images.ext
			   
			   WHEN product_photo.name IS NOT NULL 
			   THEN product_photo.ext
			   
			   ELSE NULL
			END AS product_image_ext
		");


        /** Флаг загрузки файла CDN */
        $dbal->addSelect("
			CASE
			   WHEN product_variation_image.name IS NOT NULL 
			   THEN product_variation_image.cdn
					
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN product_offer_images.cdn
					
			   WHEN product_photo.name IS NOT NULL 
			   THEN product_photo.cdn
			   
			   ELSE NULL
			END AS product_image_cdn
		");


        /* Категория */
        $dbal->join(
            'product_event',
            ProductCategory::class,
            'product_event_category',
            'product_event_category.event = product_event.id AND product_event_category.root = true'
        );

        if($this->filter->getCategory())
        {
            $dbal->andWhere('product_event_category.category = :category');
            $dbal->setParameter('category', $this->filter->getCategory(), CategoryProductUid::TYPE);
        }

        $dbal->join(
            'product_event_category',
            CategoryProduct::class,
            'category',
            'category.id = product_event_category.category'
        );

        $dbal
            ->addSelect('category_trans.name AS category_name')
            ->leftJoin(
                'category',
                CategoryProductTrans::class,
                'category_trans',
                'category_trans.event = category.event AND category_trans.local = :local'
            );


        if($this->search->getQuery())
        {

            /** Поиск по модификации */
            $result = $this->elasticGetIndex ? $this->elasticGetIndex->handle(ProductModification::class, $this->search->getQuery(), 1) : false;

            if($result)
            {
                $counter = $result['hits']['total']['value'];

                if($counter)
                {
                    /** Идентификаторы */
                    $data = array_column($result['hits']['hits'], "_source");

                    $dbal
                        ->createSearchQueryBuilder($this->search)
                        ->addSearchInArray('product_modification.id', array_column($data, "id"));

                    return $this->paginator->fetchAllAssociative($dbal);
                }
            }

            $dbal
                ->createSearchQueryBuilder($this->search)
                ->addSearchEqualUid('product.id')
                ->addSearchEqualUid('product.event')
                ->addSearchEqualUid('product_variation.id')
                ->addSearchEqualUid('product_modification.id')
                ->addSearchLike('product_trans.name')
                //->addSearchLike('product_trans.preview')
                ->addSearchLike('product_info.article')
                ->addSearchLike('product_offer.article')
                ->addSearchLike('product_modification.article')
                ->addSearchLike('product_modification.article')
                ->addSearchLike('product_variation.article');

        }

        $dbal->orderBy('product.event', 'DESC');

        return $this->paginator->fetchAllAssociative($dbal);

    }

    public function getAllProducts(UserProfileUid|string $profile): PaginatorInterface
    {
        if(is_string($profile))
        {
            $profile = new UserProfileUid($profile);
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->select('product.id')
            ->addSelect('product.event')
            ->from(Product::class, 'product');

        $dbal->leftJoin(
            'product',
            ProductEvent::class,
            'product_event',
            'product_event.id = product.event');

        $dbal
            ->addSelect('product_trans.name AS product_name')
            ->leftJoin(
                'product_event',
                ProductTrans::class,
                'product_trans',
                'product_trans.event = product_event.id AND product_trans.local = :local'
            );


        $dbal
            ->addSelect('product_desc.preview AS product_preview')
            ->addSelect('product_desc.description AS product_description')
            ->leftJoin(
                'product_event',
                ProductDescription::class,
                'product_desc',
                'product_desc.event = product_event.id AND product_desc.device = :device '

            )->setParameter('device', 'pc');


        $dbal->andWhere('product_info.profile = :profile OR product_info.profile IS NULL');
        $dbal->setParameter('profile', $profile, UserProfileUid::TYPE);


        /* ProductInfo */

        $dbal
            ->addSelect('product_info.url')
            ->leftJoin(
                'product_event',
                ProductInfo::class,
                'product_info',
                'product_info.product = product.id'
            );

        /** Ответственное лицо (Профиль пользователя) */

        $dbal
            ->addSelect('product_info.article')
            ->leftJoin(
                'product_info',
                UserProfile::class,
                'users_profile',
                'users_profile.id = product_info.profile'
            );

        $dbal
            ->addSelect('users_profile_personal.username AS users_profile_username')
            ->leftJoin(
                'users_profile',
                UserProfilePersonal::class,
                'users_profile_personal',
                'users_profile_personal.event = users_profile.event'
            );




        /** Торговое предложение */

        $dbal->leftJoin(
            'product_event',
            ProductOffer::class,
            'product_offer',
            'product_offer.event = product_event.id'
        );


        /* Тип торгового предложения */

        $dbal->leftJoin(
            'product_offer',
            CategoryProductOffers::class,
            'category_offer',
            'category_offer.id = product_offer.category_offer'
        );


        /** Множественные варианты торгового предложения */

        $dbal->leftJoin(
            'product_offer',
            ProductVariation::class,
            'product_variation',
            'product_variation.offer = product_offer.id'
        );


        /* Цена множественного варианта */
        $dbal->leftJoin(
            'category_variation',
            ProductVariationPrice::class,
            'product_variation_price',
            'product_variation_price.variation = product_variation.id'
        );


        /* Тип множественного варианта торгового предложения */
        $dbal->leftJoin(
            'product_variation',
            CategoryProductVariation::class,
            'category_variation',
            'category_variation.id = product_variation.category_variation'
        );


        /** Модификация множественного варианта */

        $dbal->leftJoin(
            'product_variation',
            ProductModification::class,
            'product_modification',
            'product_modification.variation = product_variation.id '
        );


        /** Получаем тип модификации множественного варианта */
        $dbal->leftJoin(
            'product_modification',
            CategoryProductModification::class,
            'category_modification',
            'category_modification.id = product_modification.category_modification'
        );


        /** Фото продукта */

        $dbal->leftJoin(
            'product_event',
            ProductPhoto::class,
            'product_photo',
            'product_photo.event = product_event.id AND product_photo.root = true'
        );

        $dbal->leftJoin(
            'product_offer',
            ProductVariationImage::class,
            'product_variation_image',
            'product_variation_image.variation = product_variation.id AND product_variation_image.root = true'
        );

        $dbal->leftJoin(
            'product_offer',
            ProductOfferImage::class,
            'product_offer_images',
            'product_offer_images.offer = product_offer.id AND product_offer_images.root = true'
        );

        $dbal->addSelect("
			CASE
			   WHEN product_variation_image.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductVariationImage::class)."' , '/', product_variation_image.name)
					
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductOfferImage::class)."' , '/', product_offer_images.name)
					
			   WHEN product_photo.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductPhoto::class)."' , '/', product_photo.name)
					
			   ELSE NULL
			END AS product_image
		"
        );

        /** Флаг загрузки файла CDN */
        $dbal->addSelect("
			CASE
			   WHEN product_variation_image.name IS NOT NULL 
			   THEN product_variation_image.ext
					
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN product_offer_images.ext
					
			   WHEN product_photo.name IS NOT NULL 
			   THEN product_photo.ext
					
			   ELSE NULL
			END AS product_image_ext
		")
            ->addGroupBy('product_variation_image.ext')
            ->addGroupBy('product_offer_images.ext')
            ->addGroupBy('product_photo.ext');


        /** Флаг загрузки файла CDN */
        $dbal->addSelect("
			CASE
			   WHEN product_variation_image.name IS NOT NULL 
			   THEN product_variation_image.cdn
					
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN product_offer_images.cdn
					
			   WHEN product_photo.name IS NOT NULL 
			   THEN product_photo.cdn
					
			   ELSE NULL
			END AS product_image_cdn
		")
            ->addGroupBy('product_variation_image.cdn')
            ->addGroupBy('product_offer_images.cdn')
            ->addGroupBy('product_photo.cdn');

        /* Категория */
        $dbal->join(
            'product_event',
            ProductCategory::class,
            'product_event_category',
            'product_event_category.event = product_event.id AND product_event_category.root = true'
        );


        if($this->filter->getCategory())
        {
            $dbal->andWhere('product_event_category.category = :category');
            $dbal->setParameter('category', $this->filter->getCategory(), CategoryProductUid::TYPE);
        }

        $dbal->join(
            'product_event_category',
            CategoryProduct::class,
            'category',
            'category.id = product_event_category.category'
        );


        $dbal
            ->addSelect('category_info.url AS category_url')
            ->leftJoin(
                'category',
                CategoryProductInfo::class,
                'category_info',
                'category_info.event = category.event'
            );


        $dbal->addSelect('category_trans.name AS category_name');

        $dbal->leftJoin(
            'category',
            CategoryProductTrans::class,
            'category_trans',
            'category_trans.event = category.event AND category_trans.local = :local'
        );


        $dbal->addSelect("JSON_AGG
			( DISTINCT
				
					JSONB_BUILD_OBJECT
					(
						/* свойства для сортирвоки JSON */
						'0', CONCAT(product_offer.value, product_variation.value, product_modification.value),
						
						
						'offer_value', product_offer.value, /* значение торгового предложения */
						'offer_reference', category_offer.reference, /* тип (field) торгового предложения */
						'offer_article', product_offer.article, /* артикул торгового предложения */

						'variation_value', product_variation.value, /* значение множественного варианта */
						'variation_reference', category_variation.reference, /* тип (field) множественного варианта */
						'variation_article', category_variation.article, /* валюта множественного варианта */

						'modification_value', product_modification.value, /* значение модификации */
						'modification_reference', category_modification.reference, /* тип (field) модификации */
						'modification_article', category_modification.article /* артикул модификации */

					)
				
			)
			AS product_offers"
        );

        if($this->search->getQuery())
        {

            /** Поиск по продукции */
            $result = $this->elasticGetIndex ? $this->elasticGetIndex->handle(Product::class, $this->search->getQuery(), 1) : false;

            if($result)
            {
                $counter = $result['hits']['total']['value'];

                if($counter)
                {
                    /** Идентификаторы */
                    $data = array_column($result['hits']['hits'], "_source");

                    $dbal
                        ->createSearchQueryBuilder($this->search)
                        ->addSearchInArray('product.id', array_column($data, "id"));

                    return $this->paginator->fetchAllAssociative($dbal);
                }
            }


            $dbal
                ->createSearchQueryBuilder($this->search)
                ->addSearchEqualUid('product.id')
                ->addSearchEqualUid('product.event')
                ->addSearchEqualUid('product_variation.id')
                ->addSearchEqualUid('product_modification.id')
                ->addSearchLike('product_trans.name')
                //->addSearchLike('product_trans.preview')
                ->addSearchLike('product_info.article')
                ->addSearchLike('product_offer.article')
                ->addSearchLike('product_modification.article')
                ->addSearchLike('product_modification.article')
                ->addSearchLike('product_variation.article');

        }

        $dbal->orderBy('product.event', 'DESC');

        $dbal->allGroupByExclude();

        return $this->paginator->fetchAllAssociative($dbal);

    }

}