<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedPromotionRepository;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadAppliedPromotionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AppliedPromotionRepositoryTest extends WebTestCase
{
    /**
     * @var AppliedPromotionRepository
     */
    private $repository;

    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures([LoadAppliedPromotionData::class]);

        $this->repository = static::getContainer()->get('doctrine')
            ->getManagerForClass(AppliedPromotion::class)
            ->getRepository(AppliedPromotion::class);
    }

    public function testFindByOrder()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $expected = [
            $this->getReference(LoadAppliedPromotionData::SIMPLE_APPLIED_PROMOTION)->getId(),
            $this->getReference(LoadAppliedPromotionData::SIMPLE_APPLIED_PROMOTION_WITH_LINE_ITEM)->getId()
        ];

        $actualAppliedPromotion = $this->repository->findByOrder($order);

        $this->assertCount(count($expected), $actualAppliedPromotion);

        foreach ($actualAppliedPromotion as $appliedPromotion) {
            $this->assertContains($appliedPromotion->getId(), $expected);
        }
    }

    public function testGetAppliedDiscountsInfo()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $orderDiscount = $this->getReference(LoadAppliedPromotionData::SIMPLE_APPLIED_PROMOTION);
        $lineItemDiscount = $this->getReference(LoadAppliedPromotionData::SIMPLE_APPLIED_PROMOTION_WITH_LINE_ITEM);

        $info = [
            [
                'id' => $orderDiscount->getId(),
                'couponCode' => 'summer2000',
                'promotionName' => 'Some name',
                'promotionId' => 0,
                'active' => true,
                'currency' => 'USD',
                'type' => 'order',
                'amount' => '10.0000'
            ],
            [
                'id' => $lineItemDiscount->getId(),
                'couponCode' => null,
                'promotionName' => 'Some line item discount name',
                'promotionId' => 0,
                'active' => true,
                'currency' => 'USD',
                'type' => 'lineItem',
                'amount' => '10.0000'
            ],
        ];

        $this->assertEquals($info, $this->repository->getAppliedPromotionsInfo($order));
    }
}