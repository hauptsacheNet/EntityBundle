<?php
/**
 * Created by PhpStorm.
 * User: marco
 * Date: 20.01.16
 * Time: 15:41
 */

namespace Hn\EntityBundle\Twig;


use Hn\EntityBundle\Service\FormListService;

class FormListExtension extends \Twig_Extension
{
    /**
     * @var FormListService
     */
    private $formListService;

    public function __construct(FormListService $formListService)
    {
        $this->formListService = $formListService;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('form_list', function (array $subjects, $type, array $options = array()) {
                return $this->formListService->createFormViewList($type, $type, $subjects, $options);
            }),
            new \Twig_SimpleFilter('form', function ($subject, $type, array $options = array()) {
                $forms = $this->formListService->createFormViewList($type, $type, array($subject), $options);
                return reset($forms);
            }),
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'hn_entity_form_list';
    }
}