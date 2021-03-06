<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CustomerBundle\Form\Type\CustomerGroupType;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\EventListener\CustomerGroupFlatPricingRelationFormListener;
use Oro\Bundle\PricingBundle\Form\Extension\CustomerGroupFormFlatPricingExtension;
use Oro\Bundle\PricingBundle\Form\Type\PriceListRelationType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub\CustomerGroupTypeStub;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use Oro\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Validation;

class CustomerGroupFormFlatPricingExtensionTest extends FormIntegrationTestCase
{
    use EntityTrait;

    public function testGetExtendedTypes()
    {
        $this->assertSame([CustomerGroupType::class], CustomerGroupFormFlatPricingExtension::getExtendedTypes());
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject $featureChecker */
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        /** @var CustomerGroupFlatPricingRelationFormListener|\PHPUnit\Framework\MockObject\MockObject $listener */
        $listener = $this->createMock(CustomerGroupFlatPricingRelationFormListener::class);

        $formExtension = new CustomerGroupFormFlatPricingExtension($listener);
        $formExtension->setFeatureChecker($featureChecker);
        $formExtension->addFeature('feature1');

        $websiteScopedDataType = (new WebsiteScopedTypeMockProvider())->getWebsiteScopedDataType();
        return [
            new PreloadedExtension(
                [
                    PriceListRelationType::class => new PriceListRelationType(),
                    WebsiteScopedDataType::class => $websiteScopedDataType,
                    CustomerGroupType::class => new CustomerGroupTypeStub(),
                    PriceListSelectType::class => new PriceListSelectTypeStub(),
                    EntityType::class => new EntityTypeStub([])
                ],
                [
                    CustomerGroupTypeStub::class => [$formExtension]
                ]
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testBuildFormFeatureDisabled()
    {
        /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject $featureChecker */
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        /** @var CustomerGroupFlatPricingRelationFormListener|\PHPUnit\Framework\MockObject\MockObject $listener */
        $listener = $this->createMock(CustomerGroupFlatPricingRelationFormListener::class);
        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->never())->method('add');

        $formExtension = new CustomerGroupFormFlatPricingExtension($listener);
        $formExtension->setFeatureChecker($featureChecker);
        $formExtension->addFeature('feature1');
        $formExtension->buildForm($builder, []);
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $submitted
     * @param array $expected
     */
    public function testSubmit(array $submitted, array $expected)
    {
        $form = $this->factory->create(CustomerGroupType::class, [], []);
        $form->submit(['priceListsByWebsites' => $submitted]);
        $data = $form->get('priceListsByWebsites')->getData();
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $data);
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [
                'submitted' => [
                    1 => [
                        'priceList' => 1
                    ],
                ],
                'expected' => [
                    1 => [
                        'priceList' => $this->getEntity(PriceList::class, ['id' => 1])
                    ],
                ]
            ]
        ];
    }
}
