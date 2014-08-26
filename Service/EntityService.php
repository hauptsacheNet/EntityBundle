<?php
/**
 * Created by PhpStorm.
 * User: marcopfeiffer
 * Date: 02.07.14
 * Time: 13:04
 */

namespace Hn\EntityBundle\Service;


use Doctrine\ORM\EntityManager;
use Hn\EntityBundle\Entity\BaseEntity;

class EntityService
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param $entity
     * @return string
     * @throws \RuntimeException
     */
    public function getReadableClassName($entity)
    {
        if (!is_object($entity)) {
            throw new \RuntimeException("Expected an object, got " . gettype($entity));
        }

        if ($entity instanceof BaseEntity) {
            return $entity->getHumanClassName();
        } else {
            $className = get_class($entity);
            preg_match('/(?<=^|\W)\w+$/', $className, $matches);
            return !empty($matches) ? $matches[0] : $className;
        }
    }

    /**
     * @param $entity
     * @return string
     * @throws \RuntimeException
     */
    public function getMinimalisticClassName($entity)
    {
        if (!is_object($entity)) {
            throw new \RuntimeException("Expected an object, got " . gettype($entity));
        }

        $reflection = new \ReflectionClass($entity);
        return mb_strtolower($reflection->getShortName());
    }

    /**
     * @param $entity
     * @return string
     * @throws \RuntimeException
     */
    public function getReadableIdentifier($entity)
    {
        if (!is_object($entity)) {
            throw new \RuntimeException("Expected an object, got " . gettype($entity));
        }

        return method_exists($entity, 'getHumanIdentifier')
            ? $entity->getHumanIdentifier()
            : $this->getIdentifier($entity);
    }

    /**
     * @param object $entity
     * @return mixed
     * @throws \RuntimeException
     * @throws \LogicException
     */
    public function getIdentifier($entity)
    {
        if (!is_object($entity)) {
            throw new \RuntimeException("Expected an object, got " . gettype($entity));
        }
        $className = get_class($entity);
        $meta = $this->em->getClassMetadata($className);
        if ($meta === null) {
            throw new \LogicException("$className has not meta data");
        }
        $ids = $meta->getIdentifierValues($entity);
        $numIds = count($ids);
        if ($numIds > 1) {
            throw new \LogicException("Can't handle more or less then 1 id per entity, $numIds for $className");
        }
        return $numIds > 0 ? reset($ids) : null;
    }

    /**
     * Creates a representation of the entity for debugging
     *
     * @param object $entity
     * @return string
     * @throws \RuntimeException
     */
    public function createRepresentation($entity)
    {
        if (!is_object($entity)) {
            throw new \RuntimeException("Expected an object, got " . gettype($entity));
        }

        if (method_exists($entity, '__toString')) {
            return (string) $entity;
        }

        return get_class($entity) . ':' . $this->getIdentifier($entity);
    }
} 