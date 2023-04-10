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

use BaksDev\Core\Services\Reference\ReferenceChoice;
use BaksDev\Products\Category\Type\Offers\Modification\ProductCategoryOffersVariationModificationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductOfferVariationModificationConst;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;


final class ProductOffersVariationModificationCollectionForm extends AbstractType
{
	private ReferenceChoice $reference;
	
	
	public function __construct(ReferenceChoice $reference)
	{
		$this->reference = $reference;
	}
	
	
	public function buildForm(FormBuilderInterface $builder, array $options) : void
	{
		
		$modification = $options['modification'];

		$builder->add('categoryModification', HiddenType::class);
		
		
		$builder->get('categoryModification')->addModelTransformer(
			new CallbackTransformer(
				function($categoryModification) {
					return $categoryModification instanceof ProductCategoryOffersVariationModificationUid ? $categoryModification->getValue() : $categoryModification;
				},
				function($categoryModification) {
					return new ProductCategoryOffersVariationModificationUid($categoryModification);
				}
			)
		);

        $builder->add('const', HiddenType::class);

        $builder->get('const')->addModelTransformer(
            new CallbackTransformer(
                function($const) {
                    return $const instanceof ProductOfferVariationModificationConst ? $const->getValue() : $const;
                },
                function($const) {
                    return new ProductOfferVariationModificationConst($const);
                }
            )
        );
		
		
		$builder->add('article', TextType::class);
		
		$builder->add('value', TextType::class, ['label' => $modification->name, 'attr' => [ 'class' => 'mb-3' ]]);
		
		$builder->add('price', Price\ProductOfferVariationModificationPriceForm::class, ['label' => false]);
		
		$builder->add('quantity', Quantity\ProductOfferVariationModificationQuantityForm::class, ['label' => false]);
		
		/** Торговые предложения */
		$builder->add('image', CollectionType::class, [
			'entry_type' => Image\ProductOfferVariationModificationImageCollectionForm::class,
			'entry_options' => [
				'label' => false,
			],
			'label' => false,
			'by_reference' => false,
			'allow_delete' => true,
			'allow_add' => true,
			'prototype_name' => '__modification_image__',
		]);
		
		$builder->addEventListener(
			FormEvents::PRE_SET_DATA,
			function(FormEvent $event) use ($modification) {
				$data = $event->getData();
				$form = $event->getForm();
				
				if($data)
				{
					
					/* Получаем данные торговые предложения категории */
					//$offerCat = $data->getOffer();
					
					/* Если ТП - справочник - перобразуем поле ChoiceType   */
					if($modification->reference)
					{
						$reference = $this->reference->getChoice($modification->reference);
						
						if($reference)
						{
							$form->add
							(
								'value',
								$reference->form(),
								[
									'label' => false,
									'required' => false,
									//'mapped' => false,
									//'attr' => [ 'data-select' => 'select2' ],
								]
							);
							
//							$form
//								->add('value', ChoiceType::class, [
//									'choices' => $reference->choice(),
//									'choice_value' => function($choice) {
//										if(is_string($choice)) { return $choice; }
//										return $choice?->getType()->value;
//									},
//									'choice_label' => function($choice) {
//										return $choice?->getType()->value;
//									},
//
//
//									'label' => $modification->name,
//									'expanded' => false,
//									'multiple' => false,
//									'required' => true,
//									'placeholder' => 'placeholder',
//									'translation_domain' => $reference->domain(),
//									'attr' => [ 'data-select' => 'select2' ]
//								])
//							;
						}
					}
					
					
					/* Удаляем количественный учет */
					if(!$modification->quantitative)
					{
						$form->remove('quantity');
					}
					
					/* Удаляем артикул если запрещено */
					if(!$modification->article)
					{
						$form->remove('article');
					}
					
					/* Удаляем пользовательское изображение если запрещено */
					if(!$modification->image)
					{
						$form->remove('image');
					}
					
					/* Удаляем Прайс на торговое предложение, если нет прайса */
					if(!$modification->price)
					{
						$form->remove('price');
					}
				}
			}
		);
		
		$builder->add('DeleteModification',
			ButtonType::class,
			[
				'label_html' => true,
				'attr' =>
					['class' => 'btn btn-sm btn-icon btn-light-danger del-item-modification'],
			]
		);
		
	}
	
	public function configureOptions(OptionsResolver $resolver) : void
	{
		$resolver->setDefaults([
			'data_class' => ProductOffersVariationModificationCollectionDTO::class,
			'modification' => null,
			//'offers' => null,
		]);
	}
	
}
