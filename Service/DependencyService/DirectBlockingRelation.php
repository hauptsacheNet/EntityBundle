<?php
/**
 * Created by PhpStorm.
 * User: marco
 * Date: 05.02.15
 * Time: 13:35
 */

namespace Hn\EntityBundle\Service\DependencyService;


use Symfony\Component\PropertyAccess\PropertyAccessor;

class DirectBlockingRelation extends AbstractBlockingRelation
{
    /**
     * @var string
     */
    protected $property;

    /**
     * @var PropertyAccessor
     */
    protected $accessor;

    public function __construct(\ReflectionClass $sourceClass, $property, PropertyAccessor $accessor)
    {
        parent::__construct($sourceClass);
        $this->property = $property;
        $this->accessor = $accessor;
    }

    /**
     * @param object $entity
     * @param int $limit
     * @return \object[][]
     */
    public function findBlockingEntityChainsFor($entity, $limit = PHP_INT_MAX)
    {
        $this->typeCheck($entity);

        $result = $this->accessor->getValue($entity, $this->property);

        if ($result instanceof \Traversable) {
            $result = iterator_to_array($result);
        }

        if (!is_array($result)) {
            $result = array($result);
        }

        $chains = array();
        foreach ($result as $entry) {
            $chains[] = array($entry);
        }

        return array_slice($chains, 0, $limit);
    }

    /**
     * @return void
     */
    public function clearCaches()
    {
    }
}