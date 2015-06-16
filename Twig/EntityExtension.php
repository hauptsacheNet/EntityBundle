<?php
/**
 * Created by PhpStorm.
 * User: marcopfeiffer
 * Date: 02.07.14
 * Time: 11:01
 */

namespace Hn\EntityBundle\Twig;


use Hn\EntityBundle\Service\DependencyService;
use Hn\EntityBundle\Service\EntityService;

class EntityExtension extends \Twig_Extension
{
    /**
     * @var DependencyService
     */
    private $dependencyService;

    /**
     * @var EntityService
     */
    private $entityService;

    /**
     * @param DependencyService $dependencyService
     * @param EntityService $entityService
     */
    public function __construct(DependencyService $dependencyService, EntityService $entityService)
    {
        $this->dependencyService = $dependencyService;
        $this->entityService = $entityService;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('class_name', 'get_class'),
            new \Twig_SimpleFilter('id', array($this->entityService, 'getIdentifier')),
            new \Twig_SimpleFilter('readable_class_name', array($this->entityService, 'getReadableClassName')),
            new \Twig_SimpleFilter('readable_id', array($this->entityService, 'getReadableIdentifier'))
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('delete_entity_path', array($this->dependencyService, 'generateDeleteUrl')),
            new \Twig_SimpleFunction('find_delete_blocking_entities', array($this->dependencyService, 'findBlockingEntityChains')),
            new \Twig_SimpleFunction('is_deletable', array($this->dependencyService, 'isDeletable')),
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'hn_entity_extension';
    }
}