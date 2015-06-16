<?php
/**
 * Created by PhpStorm.
 * User: marco
 * Date: 05.02.15
 * Time: 14:49
 */

namespace Hn\EntityBundle\Service\DependencyService;


class DeepBlockingRelation extends AbstractBlockingRelation
{
    /**
     * @var BlockingRelationInterface
     */
    private $relation;

    /**
     * @var BlockingRelationInterface[]
     */
    private $deepRelations;

    public function __construct(BlockingRelationInterface $relation, array $deepRelations)
    {
        parent::__construct($relation->getReflectionClass());
        $this->relation = $relation;
        $this->deepRelations = $deepRelations;
    }

    /**
     * @param object $entity
     * @param int $limit
     * @return \object[][]
     */
    public function findBlockingEntityChainsFor($entity, $limit = PHP_INT_MAX)
    {
        $this->typeCheck($entity);
        $blockingEntityChains = array();

        $deepEntityChains = $this->relation->findBlockingEntityChainsFor($entity, $limit);
        foreach ($deepEntityChains as $deepEntityChain) {
            $deepEntity = end($deepEntityChain);

            foreach ($this->deepRelations as $deepRelation) {

                // for the case of single table inheritance it is possible that the relation contains different
                // instances. This check is required so that more specific relations which may be blocked can be checked
                if (!$deepRelation->appliesTo($deepEntity)) {
                    continue;
                }

                $leftToFind = $limit - count($blockingEntityChains);
                $deepBlockingEntityChains = $deepRelation->findBlockingEntityChainsFor($deepEntity, $leftToFind);
                foreach ($deepBlockingEntityChains as $blockingEntityChain) {
                    $blockingEntityChains[] = array_merge($deepEntityChain, $blockingEntityChain);
                }
            }
        }

        return $blockingEntityChains;
    }
}