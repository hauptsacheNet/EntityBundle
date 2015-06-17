<?php
/**
 * Created by PhpStorm.
 * User: marcopfeiffer
 * Date: 02.07.14
 * Time: 09:58
 */

namespace Hn\EntityBundle\Service;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Hn\EntityBundle\Exception\EntityRelationException;
use Hn\EntityBundle\Service\DependencyService\BlockingRelationInterface;
use Hn\EntityBundle\Service\DependencyService\DeepBlockingRelation;
use Hn\EntityBundle\Service\DependencyService\InversedBlockingRelation;
use Hn\EntityBundle\Util\BlockingRelationStorage;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class DependencyService
{
    const WEAK = 'weak';
    const STRONG = 'strong';

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var EntityService
     */
    private $entityService;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrf;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * array("className" => array(BlockingRelation, ...))
     * @var BlockingRelationStorage[]
     */
    private $blockingRelationCache = array();

    public function __construct(EntityManager $em, EntityService $entityService, CsrfTokenManagerInterface $csrf, RouterInterface $router, $stopwatch)
    {
        $this->em = $em;
        $this->entityService = $entityService;
        $this->csrf = $csrf;
        $this->router = $router;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->stopwatch = $stopwatch;
    }

    /**
     * @param string $className
     * @return BlockingRelationStorage
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function findBlockingRelations($className)
    {
        if (isset($this->blockingRelationCache[$className])) {
            return $this->blockingRelationCache[$className];
        }

        $this->stopwatch->start("find blocking relations for $className");

        $blockingRelations = new BlockingRelationStorage();
        $classMetadataFactory = $this->em->getMetadataFactory();

        /** @var ClassMetadataInfo $thisClassMetadata */
        $thisClassMetadata = $classMetadataFactory->getMetadataFor($className);
        $reflClass = $thisClassMetadata->reflClass;

        /** @var ClassMetadataInfo $classMetadata */
        foreach ($classMetadataFactory->getAllMetadata() as $classMetadata) {

            $associations = $classMetadata->getAssociationMappings();
            foreach ($associations as $field => $data) {

                // if the relation does not target the specified class ignore
                if ($data['targetEntity'] !== $className) {
                    continue;
                }

                // a relation that is mappedBy another property is not important
                // those relations aren't dangerous if one side is removed
                if ($data['mappedBy']) {
                    continue;
                }

                $repository = $this->em->getRepository($classMetadata->name);
                $relation = new InversedBlockingRelation($reflClass, $repository, $field);

                // if the entity behind this relation is removed while we are removed,
                // this relation is not blocking BUT the other entity (child) could have further relations
                // which are again blocking the removal of the child.
                if ($data['inversedBy']) {
                    $ownerMapping = $thisClassMetadata->getAssociationMapping($data['inversedBy']);

                    if ($ownerMapping['isCascadeRemove']) {
                        $deepRelations = $this->findBlockingRelations($classMetadata->name);
                        $relation = new DeepBlockingRelation($relation, $deepRelations);
                    }
                }

                $blockingRelations->add($relation);
            }
        }

        $this->blockingRelationCache[$className] = $blockingRelations;
        $this->stopwatch->stop("find blocking relations for $className");

        return $blockingRelations;
    }

    /**
     * @param object $entity
     * @param int $limit
     * @return \object[][]
     */
    public function findBlockingEntityChains($entity, $limit = 10)
    {
        if (!is_object($entity)) {
            $type = is_object($entity) ? get_class($entity) : gettype($entity);
            throw new \RuntimeException("Expected an object, got $type");
        }

        $className = get_class($entity);
        $blockingRelations = $this->findBlockingRelations($className);
        $allBlockingEntityChains = array();

        /** @var BlockingRelationInterface $blockingRelation */
        foreach ($blockingRelations as $blockingRelation) {
            $leftToFind = $limit - count($allBlockingEntityChains);
            if ($leftToFind <= 0) {
                break;
            }

            $this->stopwatch->start("find blocking entities for $className");
            $blockingEntityChains = $blockingRelation->findBlockingEntityChainsFor($entity, $leftToFind);
            $blockingRelations->voteRelation($blockingRelation, count($blockingEntityChains));
            $this->stopwatch->stop("find blocking entities for $className");

            foreach ($blockingEntityChains as $blockingEntityChain) {
                $allBlockingEntityChains[] = $blockingEntityChain;
            }
        }

        return array_slice($allBlockingEntityChains, 0, $limit);
    }

    /**
     * Must be called if the database state changes
     */
    public function clearResultCache()
    {
        foreach ($this->blockingRelationCache as $className => $relations) {
            /** @var BlockingRelationInterface $relation */
            foreach ($relations as $relation) {
                $relation->clearCaches();
            }
        }
    }

    /**
     * @param object $entity
     * @return object[][]
     * @deprecated use #findBlockingEntityChains instead
     */
    public function findBlockingEntities($entity)
    {
        trigger_error("findBlockingEntities is deprecated, use findBlockingEntityChains", E_USER_DEPRECATED);
        return $this->findBlockingEntityChains($entity);
    }

    /**
     * Returns true if the entity can safely be removed
     *
     * @param object $entity
     * @return bool
     */
    public function isDeletable($entity)
    {
        $chains = $this->findBlockingEntityChains($entity, 1);
        return empty($chains);
    }

    /**
     * Generates an url to safely remove an entity
     *
     * @param object $entity
     * @param string $redirect
     * @return string
     * @throws \RuntimeException
     * @throws \LogicException
     */
    public function generateDeleteUrl($entity, $redirect = null)
    {
        if (!is_object($entity)) {
            $type = is_object($entity) ? get_class($entity) : gettype($entity);
            throw new \RuntimeException("Expected an object, got $type");
        }

        $class = get_class($entity);
        $id = $this->entityService->getIdentifier($entity);
        if ($id === null) {
            $entityString = $this->entityService->createRepresentation($entity);
            throw new \RuntimeException("The specified entity '$entityString' seems to be not persisted");
        }

        $token = $this->csrf->refreshToken("$class:$id:$redirect")->getValue();
        return $this->router->generate('hn_entity_entity_delete', array(
            'class' => $class,
            'id' => $id,
            'redirect' => $redirect,
            'token' => $token
        ));
    }

    /**
     * Removes an entity safely
     *
     * @param object $entity
     * @param boolean $andFlush
     * @throws \RuntimeException
     * @throws EntityRelationException
     */
    public function safeRemove($entity, $andFlush = true)
    {
        if (!is_object($entity)) {
            $type = is_object($entity) ? get_class($entity) : gettype($entity);
            throw new \RuntimeException("Expected an object, got $type");
        }

        $blockingEntities = $this->findBlockingEntityChains($entity);
        if (!empty($blockingEntities)) {
            $blockingEntities = array_map('end', $blockingEntities);
            throw new EntityRelationException("It is not safe to remove entity", $entity, $blockingEntities);
        }

        $this->em->remove($entity);
        if ($andFlush) {
            $this->em->flush();
        }
    }
} 