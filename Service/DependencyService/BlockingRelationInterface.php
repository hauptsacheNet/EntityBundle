<?php
/**
 * Created by PhpStorm.
 * User: marco
 * Date: 05.02.15
 * Time: 13:26
 */

namespace Hn\EntityBundle\Service\DependencyService;


interface BlockingRelationInterface
{
    /**
     * @return \ReflectionClass
     */
    public function getReflectionClass();

    /**
     * Tests if the given instance is a source for this relation.
     * If not, using #findBlockingEntitiesFor will throw an exception.
     *
     * @param object $entity
     * @return bool
     */
    public function appliesTo($entity);

    /**
     * Get's all entities that would block the removal of the specified entity.
     * The first level of the array represents an entity that is blocking.
     * The second level contains all entities in that relation chain.
     *
     * It is possible to use that chain to represent how the entity is blocked from removal.
     *
     * @param object $entity
     * @param int $limit
     * @return \object[][]
     */
    public function findBlockingEntityChainsFor($entity, $limit = PHP_INT_MAX);

    /**
     * @param object $entity
     * @return bool
     */
    public function isBlocked($entity);

    /**
     * @return void
     */
    public function clearCaches();
}