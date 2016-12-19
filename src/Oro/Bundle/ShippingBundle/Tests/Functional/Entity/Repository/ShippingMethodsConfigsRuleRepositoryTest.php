<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRules;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingAddressStub;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @dbIsolation
 */
class ShippingMethodsConfigsRuleRepositoryTest extends WebTestCase
{
    use EntityTrait;

    /**
     * @var ShippingMethodsConfigsRuleRepository
     */
    protected $repository;

    /**
     * @var EntityManager
     */
    protected $em;

    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadShippingMethodsConfigsRules::class,
        ]);

        $this->em = static::getContainer()->get('doctrine')
            ->getManagerForClass('OroShippingBundle:ShippingMethodsConfigsRule');
        $this->repository = $this->em->getRepository('OroShippingBundle:ShippingMethodsConfigsRule');
    }

    /**
     * @dataProvider getByCurrencyAndCountryDataProvider
     *
     * @param AddressInterface $shippingAddress
     * @param string $currency
     * @param array|ShippingMethodsConfigsRule[] $expectedRules
     */
    public function testGetByDestinationAndCurrency($shippingAddress, $currency, array $expectedRules)
    {
        $expectedShippingRules = $this->getEntitiesByReferences($expectedRules);
        $shippingRules = $this->repository->getByDestinationAndCurrency(
            $shippingAddress,
            $currency
        );

        static::assertEquals($expectedShippingRules, $shippingRules);
    }

    /**
     * TODO: refactor in BB-6393
     */
    public function testGetRulesWithoutShippingMethods()
    {
        $this->markTestSkipped('refactor in BB-6393');

        $rulesWithoutShippingMethods = $this->repository->getRulesWithoutShippingMethods();
        $enabledRulesWithoutShippingMethods = $this->repository->getRulesWithoutShippingMethods(true);

        static::assertCount(2, $rulesWithoutShippingMethods);
        static::assertCount(1, $enabledRulesWithoutShippingMethods);
    }

    /**
     * TODO: refactor in BB-6393
     */
    public function testDisableRulesWithoutShippingMethods()
    {
        $this->markTestSkipped('refactor in BB-6393');

        $this->repository->disableRulesWithoutShippingMethods();

        $rulesWithoutShippingMethods = $this->repository->getRulesWithoutShippingMethods();
        $enabledRulesWithoutShippingMethods = $this->repository->getRulesWithoutShippingMethods(true);

        static::assertCount(2, $rulesWithoutShippingMethods);
        static::assertCount(0, $enabledRulesWithoutShippingMethods);
    }

    /**
     * @return array
     */
    public function getByCurrencyAndCountryDataProvider()
    {
        return [
            [
                'shippingAddress' => $this->getEntity(ShippingAddressStub::class, [
                    'country' => new Country('US'),
                    'region' => $this->getEntity(Region::class, [
                        'combinedCode' => 'US-NY',
                        'code' => 'NY',
                    ]),
                    'postalCode' => '12345',
                ]),
                'currency' => 'EUR',
                'expectedRules' => [
                    'shipping_rule.1',
                    'shipping_rule.4',
                ]
            ],
            [
                'shippingAddress' => $this->getEntity(ShippingAddressStub::class),
                'currency' => 'USD',
                'expectedRules' => [
                    'shipping_rule.7',
                    'shipping_rule.8',
                ]
            ],
            [
                'shippingAddress' => $this->getEntity(ShippingAddressStub::class, [
                    'country' => new Country('FR'),
                ]),
                'currency' => 'EUR',
                'expectedRules' => [
                    'shipping_rule.2',
                    'shipping_rule.3',
                    'shipping_rule.6',
                ]
            ],
        ];
    }

    /**
     * @param array $rules
     * @return array
     */
    protected function getEntitiesByReferences(array $rules)
    {
        return array_map(function ($ruleReference) {
            return $this->getReference($ruleReference);
        }, $rules);
    }
}
