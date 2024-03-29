<?php
/**
 * Created by PhpStorm.
 * User: marco
 * Date: 19.09.14
 * Time: 16:21
 */

namespace Hn\EntityBundle\Form\DataTransformer;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\PropertyAccess\PropertyAccess;

class CommaListTransformer implements DataTransformerInterface
{
    private $manager;

    private $className;

    private $propertyPath;

    private $propertyAccessor;

    private $allowCreate;
    
    private static $newTags = [];

    public function __construct(ObjectManager $manager, $className, $propertyPath, $allowCreate)
    {
        $this->manager = $manager;
        $this->className = $className;
        $this->propertyPath = $propertyPath;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->allowCreate = $allowCreate;
    }

    /**
     * Transforms a value from the original representation to a transformed representation.
     *
     * This method is called on two occasions inside a form field:
     *
     * 1. When the form field is initialized with the data attached from the datasource (object or array).
     * 2. When data from a request is submitted using {@link Form::submit()} to transform the new input data
     *    back into the renderable format. For example if you have a date field and submit '2009-10-10'
     *    you might accept this value because its easily parsed, but the transformer still writes back
     *    "2009/10/10" onto the form field (for further displaying or other purposes).
     *
     * This method must be able to deal with empty values. Usually this will
     * be NULL, but depending on your implementation other empty values are
     * possible as well (such as empty strings). The reasoning behind this is
     * that value transformers must be chainable. If the transform() method
     * of the first value transformer outputs NULL, the second value transformer
     * must be able to process that value.
     *
     * By convention, transform() should return an empty string if NULL is
     * passed.
     *
     * @param mixed $value The value in the original representation
     *
     * @return string The value in the transformed representation
     *
     * @throws TransformationFailedException When the transformation fails.
     */
    public function transform($value)
    {
        if ($value === null) {
            return '';
        }

        $isTraversable = is_array($value) || $value instanceof \Traversable;
        if (!$isTraversable) {
            $type = is_object($value) ? get_class($value) : gettype($value);
            throw new TransformationFailedException("$type is not traversable");
        }

        $list = array();
        foreach ($value as $tag) {
            $className = $this->className;
            if ($tag instanceof $className) {
                $list[] = $this->propertyAccessor->getValue($tag, $this->propertyPath);
            } else {
                throw new TransformationFailedException("$tag is not a Tag");
            }
        }

        return implode(",", $list);
    }

    /**
     * Transforms a value from the transformed representation to its original
     * representation.
     *
     * This method is called when {@link Form::submit()} is called to transform the requests tainted data
     * into an acceptable format for your data processing/model layer.
     *
     * This method must be able to deal with empty values. Usually this will
     * be an empty string, but depending on your implementation other empty
     * values are possible as well (such as empty strings). The reasoning behind
     * this is that value transformers must be chainable. If the
     * reverseTransform() method of the first value transformer outputs an
     * empty string, the second value transformer must be able to process that
     * value.
     *
     * By convention, reverseTransform() should return NULL if an empty string
     * is passed.
     *
     * @param mixed $value The value in the transformed representation
     *
     * @return ArrayCollection The value in the original representation
     *
     * @throws TransformationFailedException When the transformation fails.
     */
    public function reverseTransform($value)
    {
        if ($value === "" || $value === null) {
            return new ArrayCollection();
        }

        $rawNames = preg_split("/\s*,\s*/", trim($value));
        $rawLowerCaseNames = array_unique(array_map('strtolower', $rawNames));

        /** @var EntityRepository $repository */
        $repository = $this->manager->getRepository($this->className);
        $qb = $repository->createQueryBuilder('tag');
        $qb->andWhere($qb->expr()->in("tag.$this->propertyPath", $rawLowerCaseNames));
        $tags = $qb->getQuery()->getResult();

        foreach ($tags as $tag) {
            $tagName = $this->propertyAccessor->getValue($tag, $this->propertyPath);
            $lowerCaseTagName = strtolower($tagName);
            foreach ($rawLowerCaseNames as $index => $rawLowerCaseName) {
                if ($rawLowerCaseName === $lowerCaseTagName) {
                    unset($rawNames[$index]);
                }
            }
        }

        if (!$this->allowCreate && !empty($rawNames)) {
            throw new TransformationFailedException("New tags found but wasn't allowed to create them");
        }

        foreach ($rawNames as $tagName) {
            if(isset(self::$newTags[strtolower($tagName)])) {
                $tags[] = self::$newTags[strtolower($tagName)];
                continue;
            }
            $tag = new $this->className();
            $this->propertyAccessor->setValue($tag, $this->propertyPath, $tagName);
            $tags[] = $tag;
            self::$newTags[strtolower($tagName)] = $tag;
        }

        return new ArrayCollection($tags);
    }
}
