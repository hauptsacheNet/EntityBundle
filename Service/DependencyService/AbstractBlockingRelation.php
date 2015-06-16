<?php
/**
 * Created by PhpStorm.
 * User: marco
 * Date: 05.02.15
 * Time: 13:36
 */

namespace Hn\EntityBundle\Service\DependencyService;


abstract class AbstractBlockingRelation implements BlockingRelationInterface
{
    /**
     * @var \ReflectionClass
     */
    private $sourceClass;

    public function __construct(\ReflectionClass $sourceClass)
    {
        $this->sourceClass = $sourceClass;
    }

    /**
     * @return \ReflectionClass
     */
    public function getReflectionClass()
    {
        return $this->sourceClass;
    }

    /**
     * @param object $entity
     * @return bool
     */
    public function appliesTo($entity)
    {
        return $this->sourceClass->isInstance($entity);
    }

    /**
     * @param $entity
     */
    protected function typeCheck($entity)
    {
        if (!$this->sourceClass->isInstance($entity)) {
            $type = is_object($entity) ? get_class($entity) : gettype($entity);
            throw new \RuntimeException("Expected $this->sourceClass, got $type");
        }
    }

    /**
     * @param object $entity
     * @return bool
     */
    public function isBlocked($entity)
    {
        return count($this->findBlockingEntityChainsFor($entity, 1)) > 0;
    }
}