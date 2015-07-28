<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class ShoppingListRepositoryTest extends WebTestCase
{
    /** @var AccountUser */
    protected $accountUser;

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists',
            ]
        );

        $this->accountUser = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BCustomerBundle:AccountUser')
            ->findOneBy(['username' => LoadAccountUserData::AUTH_USER]);
    }

    public function testFindCurrentForAccountUser()
    {
        $shoppingList = $this->getRepository()->findCurrentForAccountUser($this->accountUser);
        $this->assertInstanceOf('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', $shoppingList);
        $this->assertEquals($this->accountUser, $shoppingList->getAccountUser());
    }

    public function testFindByUser()
    {
        $shoppingLists = $this->getRepository()->findByUser($this->accountUser);
        $this->assertTrue(count($shoppingLists) > 0);
        $shoppingList = array_shift($shoppingLists);
        $this->assertInstanceOf('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', $shoppingList);
        $this->assertEquals($this->accountUser, $shoppingList->getAccountUser());
    }

    public function testFindByUserAndId()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingListReference = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $shoppingList = $this->getRepository()
            ->findByUserAndId($this->accountUser, $shoppingListReference->getId());
        $this->assertInstanceOf('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', $shoppingList);
        $this->assertEquals($this->accountUser, $shoppingList->getAccountUser());
    }

    public function testCreateFindForAccountUserQueryBuilder()
    {
        $repository = $this->getRepository();
        $account = $this->getAccountUser();

        $qb = $repository->createFindForAccountUserQueryBuilder($account);
        $this->assertInstanceOf('\Doctrine\ORM\QueryBuilder', $qb);

        /** @var ShoppingList[] $accountShoppingLists */
        $accountShoppingLists = $qb->getQuery()->execute();
        foreach ($accountShoppingLists as $shoppingList) {
            if ($shoppingList->getAccount() instanceof Customer) {
                $this->assertEquals($account->getCustomer()->getId(), $shoppingList->getAccount()->getId());
            } else {
                $this->assertEquals($account->getId(), $shoppingList->getAccountUser()->getId());
            }
        }
    }

    /**
     * @return AccountUser
     */
    public function getAccountUser()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BCustomerBundle:AccountUser')
            ->findOneBy(['username' => LoadAccountUserData::AUTH_USER]);
    }

    /**
     * @return ShoppingListRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BShoppingListBundle:ShoppingList');
    }
}
