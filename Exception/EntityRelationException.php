<?php
/**
 * Created by PhpStorm.
 * User: marcopfeiffer
 * Date: 02.07.14
 * Time: 12:05
 */

namespace Hn\EntityBundle\Exception;


use Exception;

class EntityRelationException extends \Exception
{
    private $relatedEntities;

    /**
     * @param string $message
     * @param object $entity
     * @param object[] $relatedEntities
     */
    public function __construct($message, $entity, array $relatedEntities = array())
    {
        $relatedText = '';
        if (!empty($relatedEntities)) {
            $relatedText .= "\nFollowing Entities are involved:";

            foreach ($relatedEntities as $relatedEntity) {
                $representation = $this->getEntityRepresentation($relatedEntity);
                $relatedText .= "\n\t'$representation'";
            }
        }

        $representation = $this->getEntityRepresentation($entity);
        parent::__construct("$message '$representation'$relatedText");
        $this->relatedEntities = $relatedEntities;
    }

    /**
     * @return object[]
     */
    public function getRelatedEntities()
    {
        return $this->relatedEntities;
    }

    /**
     * @param object $entity
     * @return string
     */
    protected static function getEntityRepresentation($entity)
    {
        return method_exists($entity, '__toString') ? $entity->__toString() : get_class($entity);
    }
}