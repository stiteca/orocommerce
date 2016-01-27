<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorageInterface;

use OroB2B\Bundle\PricingBundle\Entity\ChangedProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ChangedProductPriceRepository;

class ProductPriceEntityListener
{
    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @var ExtraActionEntityStorageInterface
     */
    protected $extraActionsStorage;

    /**
     * @param ExtraActionEntityStorageInterface $extraActionsStorage
     */
    public function __construct(ExtraActionEntityStorageInterface $extraActionsStorage)
    {
        $this->extraActionsStorage = $extraActionsStorage;
    }

    /**
     * @param ProductPrice $productPrice
     * @param LifecycleEventArgs $event
     */
    public function prePersist(ProductPrice $productPrice, LifecycleEventArgs $event)
    {
        $this->handleChanges($productPrice, $event);
    }

    /**
     * @param ProductPrice $productPrice
     * @param LifecycleEventArgs $event
     */
    public function preRemove(ProductPrice $productPrice, LifecycleEventArgs $event)
    {
        $this->handleChanges($productPrice, $event);
    }

    /**
     * @param ProductPrice $productPrice
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(ProductPrice $productPrice, PreUpdateEventArgs $event)
    {
        $this->handleChanges($productPrice, $event);
    }

    /**
     * @param ProductPrice $productPrice
     * @param LifecycleEventArgs $event
     */
    protected function handleChanges(ProductPrice $productPrice, LifecycleEventArgs $event)
    {
        $changedProductPrice = $this->createChangedProductPrice($productPrice);

        $em = $event->getEntityManager();
        if (null === $changedProductPrice
            || $this->extraActionsStorage->isScheduledForInsert($changedProductPrice)
            || $this->getRepository($em)->isCreated($changedProductPrice)
        ) {
            return;
        }

        $this->extraActionsStorage->scheduleForExtraInsert($changedProductPrice);
    }

    /**
     * @param ProductPrice $productPrice
     * @return ChangedProductPrice|null
     */
    protected function createChangedProductPrice(ProductPrice $productPrice)
    {
        /** @var PriceList $priceList */
        $priceList = $productPrice->getPriceList();
        $product = $productPrice->getProduct();

        if (!$priceList || !$product || !$priceList->getId() || !$product->getId()) {
            return null;
        }

        return new ChangedProductPrice($priceList, $product);
    }

    /**
     * @param EntityManager $em
     * @return ChangedProductPriceRepository
     */
    protected function getRepository(EntityManager $em)
    {
        if (!$this->repository) {
            $this->repository = $em->getRepository('OroB2BPricingBundle:ChangedProductPrice');
        }

        return $this->repository;
    }
}
