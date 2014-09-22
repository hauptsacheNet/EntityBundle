<?php
/**
 * Created by PhpStorm.
 * User: marcopfeiffer
 * Date: 02.07.14
 * Time: 13:04
 */

namespace Hn\EntityBundle\Service;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Proxy\Proxy;
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

    protected function checkObject($object)
    {
        if (!is_object($object)) {
            throw new \RuntimeException("Expected an object, got " . gettype($object));
        }
    }

    protected function checkEntity($object)
    {
        if (!$this->isEntity($object)) {
            $type = is_object($object) ? get_class($object) : gettype($object);
            throw new \RuntimeException("Require an entity for this operation. got $type");
        }
    }

    /**
     * @param object $entity
     * @return string
     * @throws \RuntimeException
     */
    public function getClass($entity)
    {
        $this->checkObject($entity);

        if ($entity instanceof Proxy) {
            return get_parent_class($entity);
        } else {
            return get_class($entity);
        }
    }

    /**
     * @param object $object
     * @return bool
     */
    public function isEntity($object)
    {
        $this->checkObject($object);

        $className = $this->getClass($object);
        return !$this->em->getMetadataFactory()->isTransient($className);
    }

    /**
     * @param $entity
     * @return string
     * @throws \RuntimeException
     */
    public function getReadableClassName($entity)
    {
        $this->checkObject($entity);

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
        $this->checkObject($entity);

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
        $this->checkObject($entity);

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
        $this->checkEntity($entity);

        $className = $this->getClass($entity);
        $meta = $this->em->getClassMetadata($className);
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
        $this->checkObject($entity);

        if (method_exists($entity, '__toString')) {
            return (string) $entity;
        }

        return get_class($entity) . ':' . $this->getIdentifier($entity);
    }
} 