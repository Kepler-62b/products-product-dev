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

declare(strict_types=1);

namespace BaksDev\Products\Product\Command\Upgrade;

use BaksDev\Products\Product\Entity\ProductInvariable;
use BaksDev\Products\Product\Repository\AllProductsIdentifier\AllProductsIdentifierInterface;
use BaksDev\Products\Product\Repository\ProductInvariable\ProductInvariableInterface;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Products\Product\UseCase\Admin\Invariable\ProductInvariableDTO;
use BaksDev\Products\Product\UseCase\Admin\Invariable\ProductInvariableHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'baks:upgrade:product:invariable',
    description: 'Обновляет Invariable всех продуктов'
)]
class UpgradeProductInvariableCommand extends Command
{
    public function __construct(
        private readonly AllProductsIdentifierInterface $allProductsIdentifier,
        private readonly ProductInvariableInterface $productInvariable,
        private readonly ProductInvariableHandler $productInvariableHandler,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('argument', InputArgument::OPTIONAL, 'Описание аргумента');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $progressIndicator = new ProgressIndicator($output);
        $progressIndicator->start('Processing...');

        $products = $this->allProductsIdentifier->findAll();

        /** @var array{
         * "product_id",
         * "product_event" ,
         * "offer_id" ,
         * "offer_const",
         * "variation_id" ,
         * "variation_const" ,
         * "modification_id",
         * "modification_const"
         * } $product
         */

        foreach($products as $product)
        {
            $progressIndicator->advance();

            $ProductInvariableUid = $this->productInvariable
                ->product($product['product_id'])
                ->offer($product['offer_const'])
                ->variation($product['variation_const'])
                ->modification($product['modification_const'])
                ->find();

            if(false === $ProductInvariableUid)
            {
                $ProductInvariableDTO = new ProductInvariableDTO();

                $ProductInvariableDTO
                    ->setProduct(new ProductUid($product['product_id']))
                    ->setOffer($product['offer_const'] ? new ProductOfferConst($product['offer_const']) : null)
                    ->setVariation($product['variation_const'] ? new ProductVariationConst($product['variation_const']) : null)
                    ->setModification($product['modification_const'] ? new ProductModificationConst($product['modification_const']) : null);

                $handle = $this->productInvariableHandler->handle($ProductInvariableDTO);

                if(false === ($handle instanceof ProductInvariable))
                {
                    $io->error(sprintf('%s: Ошибка при обновлении ProductInvariable', $handle));
                }
            }
        }

        $progressIndicator->finish('Finished');

        $io->success('Обновление успешно завершено');

        return Command::SUCCESS;
    }
}