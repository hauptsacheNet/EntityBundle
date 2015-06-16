<?php
/**
 * Created by PhpStorm.
 * User: marco
 * Date: 05.02.15
 * Time: 13:22
 */
namespace Hn\EntityBundle\Service\DependencyService;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

class InversedBlockingRelation extends AbstractBlockingRelation
{
    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $property;

    public function __construct(\ReflectionClass $sourceClass, EntityRepository $repository, $property)
    {
        parent::__construct($sourceClass);
        $this->repository = $repository;
        $this->property = $property;
    }

    /**
     * NOTICE: this method isn't used due to a problem in doctrine
     * http://www.doctrine-project.org/jira/browse/DDC-2988
     * This prevents the usage of findBy and matching to resolve the relations.
     *
     * @param object $entity
     * @return Criteria
     */
    protected function createCriteria($entity)
    {
        return Criteria::create()->where(Criteria::expr()->eq($this->property, $entity));
    }

    /**
     * @param object $entity
     * @deprecated this is just a workaround until the doctrine but DDC-2988 is fixed
     * @see http://www.doctrine-project.org/jira/browse/DDC-2988
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function createQueryBuilder($entity)
    {
        $qb = $this->repository->createQueryBuilder('related_entity');
        $qb->join('related_entity.' . $this->property, 'entity');
        $qb->andWhere('entity = :entity');
        $qb->setParameter('entity', $entity);
        return $qb;
    }

    /**
     * @param object $entity
     * @param int $limit
     * @return \object[][]
     */
    public function findBlockingEntityChainsFor($entity, $limit = PHP_INT_MAX)
    {
        $this->typeCheck($entity);

        $qb = $this->createQueryBuilder($entity);
        $qb->setMaxResults($limit);
        $result = $qb->getQuery()->getResult();

        $chains = array();
        foreach ($result as $entry) {
            $chains[] = array($entry);
        }
        return $chains;
    }
}