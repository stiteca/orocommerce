<?php

namespace Oro\Bundle\ScopeBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class ScopeRepository extends EntityRepository
{
    /**
     * @param ScopeCriteria $criteria
     * @return BufferedQueryResultIterator|Scope[]
     */
    public function findByCriteria(ScopeCriteria $criteria)
    {
        $qb = $this->createQueryBuilder('scope');
        $criteria->applyWhere($qb, 'scope');

        return new BufferedQueryResultIterator($qb);
    }

    /**
     * @param ScopeCriteria $criteria
     * @return BufferedQueryResultIterator|Scope[]
     */
    public function findScalarByCriteria(ScopeCriteria $criteria)
    {
        $qb = $this->createQueryBuilder('scope');
        $criteria->applyWhere($qb, 'scope');
        $result = $qb->select('scope.id')
            ->getQuery()
            ->getScalarResult();
        $scopeIds = array_map(function ($scope) {
            return $scope['id'];
        }, $result);

        return $scopeIds;
    }

    /**
     * @param ScopeCriteria $criteria
     * @return Scope
     */
    public function findOneByCriteria(ScopeCriteria $criteria)
    {
        $qb = $this->createQueryBuilder('scope');
        $criteria->applyWhere($qb, 'scope');
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
