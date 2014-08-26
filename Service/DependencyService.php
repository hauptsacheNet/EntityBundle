<?php
/**
 * Created by PhpStorm.
 * User: marcopfeiffer
 * Date: 02.07.14
 * Time: 09:58
 */

namespace Hn\EntityBundle\Service;


use Doctrine\ORM\EntityManager;
use Hn\EntityBundle\Exception\EntityRelationException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
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

    public function __construct(EntityManager $em, EntityService $entityService, CsrfTokenManagerInterface $csrf, RouterInterface $router)
    {
        $this->em = $em;
        $this->entityService = $entityService;
        $this->csrf = $csrf;
        $this->router = $router;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Finds all relations which don't get removed if the specified entity is removed.
     * It is very likely that you don't want to remove an entity if it still has such relations.
     *
     * This method returns an array where the keys are properties which need to be accessed.
     * The value is either "null" which means this property is a blocking relation
     * or another array which means the value of that property needs to be checked with that array too.
     *
     * array(
     *     "jobs" => NULL
     *     "issues" => array(
     *         "homeAds" => NULL
     *     )
     * )
     *
     * @param string $className
     * @return array
     * @throws \RuntimeException
     */
    public function findBlockingRelations($className)
    {
        $result = array();
        $meta = $this->em->getClassMetadata($className);

        if ($meta === null) {
            throw new \RuntimeException("'$className' has no meta data");
        }

        $assiciations = $meta->getAssociationMappings();
        foreach ($assiciations as $field => $data) {
            $otherData = array('isCascadeRemove' => false);
            $targetClass = $data['targetEntity'];
            $otherProperty = $data['mappedBy'] ? : $data['inversedBy'];
            if ($otherProperty !== null) {
                $otherData = $this->em->getClassMetadata($targetClass)->getAssociationMapping($otherProperty);
            }

            // if the relation does not cascade delete, it is blocking
            // check both sides of the relation if it is weak (Issue to MediaObject does not block)
            $weakRelation = $data['isCascadeRemove'] || $otherData['isCascadeRemove'];
            if (!$weakRelation) {
                $result[$field] = null;
                continue;
            }

            // if the relation is weak from the other side don't do these checks (Issue to MediaObject)
            if (!$otherData['isCascadeRemove']) {
                // check if the relation target contains fields which might block
                $association = $this->findBlockingRelations($targetClass);
                if (!empty($association)) {
                    $result[$field] = $association;
                    continue;
                }
            }
        }

        return $result;
    }

    /**
     * Gets all entities which can not safely be removed together with the specified entity.
     *
     * @param object $entity
     * @return object[]
     * @throws \RuntimeException
     */
    public function findBlockingEntities($entity)
    {
        if (!is_object($entity)) {
            $type = is_object($entity) ? get_class($entity) : gettype($entity);
            throw new \RuntimeException("Expected an object, got $type");
        }

        $blockingRelations = $this->findBlockingRelations(get_class($entity));
        return $this->scanFields($entity, $blockingRelations);
    }

    /**
     * @param object $entity
     * @param array $relations
     * @param array $chain
     * @return object[][]
     */
    protected function scanFields($entity, $relations, $chain = array())
    {
        $results = array();
        foreach ($relations as $fieldName => $subRelations) {
            $fieldEntities = $this->propertyAccessor->getValue($entity, $fieldName);
            if (!is_array($fieldEntities) && !($fieldEntities instanceof \Traversable)) {
                $fieldEntities = $fieldEntities !== null ? array($fieldEntities) : array();
            }

            // if there are no sub relations and the value is not empty, include values
            // if there are sub relations scan them too for blocking relations
            if ($subRelations === null) {
                foreach ($fieldEntities as $fieldEntity) {
                    $results[] = array_merge($chain, array($fieldEntity));
                }
            } else {
                foreach ($fieldEntities as $fieldEntity) {
                    $nextChain = array_merge($chain, array($fieldEntity));
                    $subEntities = $this->scanFields($fieldEntity, $subRelations, $nextChain);
                    foreach ($subEntities as $subEntityChain) {
                        $results[] = $subEntityChain;
                    }
                }
            }
        }

        // filter back references
        foreach ($results as $index => $result) {
            if (end($result) === $entity) {
                unset($results[$index]);
            }
        }

        return $results;
    }

    /**
     * Returns true if the entity can safely be removed
     *
     * @param object $entity
     * @return bool
     */
    public function isDeletable($entity)
    {
        $blockingEntities = $this->findBlockingEntities($entity);
        return empty($blockingEntities);
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

        $blockingEntities = $this->findBlockingEntities($entity);
        if (!empty($blockingEntities)) {
            echo '<pre>';
            \Doctrine\Common\Util\Debug::dump($this->findBlockingRelations(get_class($entity)));
            echo '</pre>';
            throw new EntityRelationException("It is not safe to remove entity", $entity, $blockingEntities);
        }

        $this->em->remove($entity);
        if ($andFlush) {
            $this->em->flush();
        }
    }
} 