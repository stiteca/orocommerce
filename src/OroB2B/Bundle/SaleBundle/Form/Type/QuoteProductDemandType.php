<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProductDemand;
use OroB2B\Bundle\SaleBundle\Validator\Constraints\ConfigurableQuoteProductOffer;
use OroB2B\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use OroB2B\Bundle\ValidationBundle\Validator\Constraints\GreaterThanZero;

class QuoteProductDemandType extends AbstractType
{
    const NAME = 'orob2b_sale_quote_product_demand';

    const FIELD_QUANTITY = 'quantity';
    const FIELD_QUOTE_PRODUCT_OFFER = 'quoteProductOffer';
    const FIELD_UNIT = 'unit';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['data']);
        $resolver->setDefaults(
            [
                'data_class' => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProductDemand',
                'constraints' => new ConfigurableQuoteProductOffer()
            ]
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var QuoteProductDemand $quoteProductDemand */
        $quoteProductDemand = $options['data'];

        $quoteProduct = $quoteProductDemand->getQuoteProductOffer()->getQuoteProduct();
        $builder
            ->add(
                self::FIELD_QUANTITY,
                'number',
                [
                    'constraints' => [new NotBlank(), new Decimal(), new GreaterThanZero()],
                    'read_only' => !$quoteProduct->hasIncrementalOffers(),
                    'required' => true
                ]
            )->add(
                self::FIELD_QUOTE_PRODUCT_OFFER,
                QuoteProductDemandOfferChoiceType::NAME,
                [
                    'choices' => $quoteProduct->getQuoteProductOffers(),
                    'required' => true
                ]
            )->add(
                self::FIELD_UNIT,
                'hidden',
                [
                    'mapped' => false,
                    'data' => $quoteProductDemand->getQuoteProductOffer()->getProductUnitCode()
                ]
            );

        // Make sure that form is workable even if offer field was removed
        $builder->get(self::FIELD_QUOTE_PRODUCT_OFFER)->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($quoteProductDemand) {
                $data = $event->getData();
                if (!$data) {
                    $event->setData($quoteProductDemand->getQuoteProductOffer());
                }
            }
        );
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($quoteProductDemand) {
                $data = $event->getData();
                if (!array_key_exists(self::FIELD_QUOTE_PRODUCT_OFFER, $data)) {
                    $data[self::FIELD_QUANTITY] = $quoteProductDemand->getQuoteProductOffer()->getQuantity();
                    $data[self::FIELD_UNIT] = $quoteProductDemand->getQuoteProductOffer()->getProductUnitCode();
                    $event->setData($data);
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var QuoteProductDemand $quoteProductDemand */
        $quoteProductDemand = $options['data'];
        $view->vars['quoteProduct'] = $quoteProductDemand->getQuoteProductOffer()->getQuoteProduct();

        // move constraint to quantity field to support JS validation
        /** @var FormView $quantityView */
        $quantityView = $view->children[self::FIELD_QUANTITY];
        if (isset($view->vars['attr']['data-validation'], $quantityView->vars['attr']['data-validation'])) {
            $viewAttr = $view->vars['attr']['data-validation'];
            $quantityViewAttr = $quantityView->vars['attr']['data-validation'];

            $quantityView->vars['attr']['data-validation'] = json_encode(
                array_merge(json_decode($viewAttr, true), json_decode($quantityViewAttr, true))
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
