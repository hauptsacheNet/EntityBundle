<?php
/**
 * Created by PhpStorm.
 * User: marcopfeiffer
 * Date: 02.07.14
 * Time: 09:58
 */

namespace Hn\EntityBundle\Service;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Hn\EntityBundle\Exception\EntityRelationException;
use Hn\EntityBundle\Service\DependencyService\BlockingRelationInterface;
use Hn\EntityBundle\Service\DependencyService\DeepBlockingRelation;
use Hn\EntityBundle\Service\DependencyService\InversedBlockingRelation;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

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
     * array("className" => array(BlockingRelation, ...))
     * @var BlockingRelationInterface[][]
     */
    private $blockingRelationCache = array();

    public function __construct(EntityManager $em, EntityService $entityService, CsrfTokenManagerInterface $csrf, RouterInterface $router)
    {
        $this->em = $em;
        $this->entityService = $entityService;
        $this->csrf = $csrf;
        $this->router = $router;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param string $className
     * @return BlockingRelationInterface[]
     */
    protected function findBlockingRelations($className)
    {
        if (array_key_exists($className, $this->blockingRelationCache)) {
            return $this->blockingRelationCache[$className];
        }

        $blockingRelations = array();
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

                $blockingRelations[] = $relation;
            }
        }

        return $blockingRelations;
    }

    /**
     * @param object $entity
     * @return object[][]
     */
    public function findBlockingEntityChains($entity)
    {
        if (!is_object($entity)) {
            $type = is_object($entity) ? get_class($entity) : gettype($entity);
            throw new \RuntimeException("Expected an object, got $type");
        }

        $blockingRelations = $this->findBlockingRelations(get_class($entity));
        $allBlockingEntityChains = array();

        foreach ($blockingRelations as $blockingRelation) {
            $blockingEntityChains = $blockingRelation->findBlockingEntityChainsFor($entity);

            foreach ($blockingEntityChains as $blockingEntityChain) {
                $allBlockingEntityChains[] = $blockingEntityChain;
            }
        }

        return $allBlockingEntityChains;
    }

    /**
     * @param object $entity
     * @return object[][]
     * @deprecated use #findBlockingEntityChains instead
     */
    public function findBlockingEntities($entity)
    {
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
        $chains = $this->findBlockingEntityChains($entity);
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