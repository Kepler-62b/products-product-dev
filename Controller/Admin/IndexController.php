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

namespace BaksDev\Products\Product\Controller\Admin;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Products\Product\Forms\ProductFilter\Admin\ProductFilterDTO;
use BaksDev\Products\Product\Forms\ProductFilter\Admin\ProductFilterForm;
use BaksDev\Products\Product\Repository\AllProducts\AllProductsInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_PRODUCT')]
final class IndexController extends AbstractController
{
    #[Route('/admin/products/{page<\d+>}', name: 'admin.index', methods: [
        'GET',
        'POST',
    ])]
    public function index(
        Request $request,
        AllProductsInterface $getAllProduct,
        int $page = 0,
    ): Response
    {
        // Поиск
        $search = new SearchDTO($request);
        $searchForm = $this->createForm(SearchForm::class, $search, [
            'action' => $this->generateUrl('products-product:admin.index'),
        ]);
        $searchForm->handleRequest($request);


//        /**
//         * Фильтр профиля пользователя
//         */
//
//        $profile = new ProductProfileFilterDTO($request, $this->getProfileUid());
//        $ROLE_ADMIN = $this->isGranted('ROLE_ADMIN');
//
//        if($ROLE_ADMIN)
//        {
//            $profileForm = $this->createForm(ProductProfileFilterFormAdmin::class, $profile, [
//                'action' => $this->generateUrl('products-product:admin.index'),
//            ]);
//        }
//        else
//        {
//            $profileForm = $this->createForm(ProductProfileFilterForm::class, $profile, [
//                'action' => $this->generateUrl('products-product:admin.index'),
//            ]);
//        }
//
//        $profileForm->handleRequest($request);
//        !$profileForm->isSubmitted() ?: $this->redirectToReferer();


        /**
         * Фильтр продукции по ТП
         */
        $filter = new ProductFilterDTO($request);
        $filterForm = $this->createForm(ProductFilterForm::class, $filter, [
            'action' => $this->generateUrl('products-product:admin.index'),
        ]);
        $filterForm->handleRequest($request);
        !$filterForm->isSubmitted() ?: $this->redirectToReferer();


        $isFilter = (bool) $search->getQuery() || $filter->getOffer() || $filter->getVariation() || $filter->getModification();

        if($isFilter)
        {
            // Получаем список торговых предложений
            $query = $getAllProduct
                ->search($search)
                ->filter($filter)
                ->getAllProductsOffers($this->getProfileUid());
        }
        else
        {
            // Получаем список продукции
            $query = $getAllProduct
                ->filter($filter)
                ->getAllProducts($this->getProfileUid());
        }





        // Получаем список оп фильтру
        //$query = $getAllProduct->getAllProducts($search, $filter, $this->getAdminFilterProfile());


        return $this->render(
            [
                'query' => $query,
                'search' => $searchForm->createView(),
                'filter' => $filterForm->createView(),
                //'profile' => $profileForm->createView(),
            ],
            file : $isFilter ? 'offers.html.twig' : 'product.html.twig'
        );
    }
}
