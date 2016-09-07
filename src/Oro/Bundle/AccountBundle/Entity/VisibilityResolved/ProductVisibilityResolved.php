<?php

namespace Oro\Bundle\AccountBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\ProductRepository")
 * @ORM\Table(name="oro_prod_vsb_resolv")
 */
class ProductVisibilityResolved extends BaseProductVisibilityResolved
{
    /**
     * @var ProductVisibility
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility")
     * @ORM\JoinColumn(name="source_product_visibility", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $sourceProductVisibility;

    /**
     * @return ProductVisibility
     */
    public function getSourceProductVisibility()
    {
        return $this->sourceProductVisibility;
    }

    /**
     * @param ProductVisibility|null $sourceProductVisibility
     * @return $this
     */
    public function setSourceProductVisibility(ProductVisibility $sourceProductVisibility = null)
    {
        $this->sourceProductVisibility = $sourceProductVisibility;

        return $this;
    }
}
