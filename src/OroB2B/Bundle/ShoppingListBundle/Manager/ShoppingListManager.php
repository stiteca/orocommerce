<?php
namespace OroB2B\Bundle\ShoppingListBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListManager
{
    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return bool
     */
    public function createCurrent(AccountUser $accountUser)
    {
        $shoppingList = new ShoppingList();
        $owner = $this->manager->getRepository('OroUserBundle:User')->find(1);
        $shoppingList->setOwner($owner)
            ->setOrganization($accountUser->getOrganization())
            ->setAccount($accountUser->getCustomer())
            ->setAccountUser($accountUser)
            ->setLabel('Default');

        return $this->setCurrent($accountUser, $shoppingList);
    }

    /**
     * @param AccountUser  $accountUser
     * @param ShoppingList $shoppingList
     *
     * @return bool
     */
    public function setCurrent(AccountUser $accountUser, ShoppingList $shoppingList)
    {
        $currentList = $this->manager
            ->getRepository('OroB2BShoppingListBundle:ShoppingList')
            ->findCurrentForAccountUser($accountUser);

        if ($currentList instanceof ShoppingList && $currentList !== $shoppingList) {
            $currentList->setIsCurrent(false);
            $this->manager->persist($currentList);
        }

        $shoppingList->setIsCurrent(true);
        $this->manager->persist($shoppingList);
        $this->manager->flush();

        return true;
    }
}
