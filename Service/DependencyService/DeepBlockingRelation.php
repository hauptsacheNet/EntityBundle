<?php
/**
 * Created by PhpStorm.
 * User: marco
 * Date: 05.02.15
 * Time: 14:49
 */

namespace Hn\EntityBundle\Service\DependencyService;


use Hn\EntityBundle\Util\BlockingRelationStorage;

class DeepBlockingRelation extends AbstractBlockingRelation
{
    /**
     * @var BlockingRelationInterface
     */
    private $relation;

    /**
     * @var BlockingRelationStorage
     */
    private $deepRelations;

    public function __construct(BlockingRelationInterface $relation, BlockingRelationStorage $deepRelations)
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

        $deepEntityChains = $this->relation->findBlockingEntityChainsFor($entity);
        foreach ($deepEntityChains as $deepEntityChain) {
            $deepEntity = end($deepEntityChain);

            /** @var BlockingRelationInterface $deepRelation */
            foreach ($this->deepRelations as $deepRelation) {
                $leftToFind = $limit - count($blockingEntityChains);
                if ($leftToFind <= 0) {
                    break;
                }

                // for the case of single table inheritance it is possible that the relation contains different
                // instances. This check is required so that more specific relations which may be blocked can be checked
                if (!$deepRelation->appliesTo($deepEntity)) {
                    continue;
                }

                $deepBlockingEntityChains = $deepRelation->findBlockingEntityChainsFor($deepEntity, $leftToFind);
                $this->deepRelations->voteRelation($deepRelation, count($deepBlockingEntityChains));

                foreach ($deepBlockingEntityChains as $blockingEntityChain) {
                    $blockingEntityChains[] = array_merge($deepEntityChain, $blockingEntityChain);
                }
            }
        }

        return array_slice($blockingEntityChains, 0, $limit);
    }

    /**
     * @return void
     */
    public function clearCaches()
    {
        $this->relation->clearCaches();
        /** @var BlockingRelationInterface $relation */
        foreach ($this->deepRelations as $relation) {
            $relation->clearCaches();
        }
    }
}