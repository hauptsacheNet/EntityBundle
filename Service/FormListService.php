<?php
/**
 * Created by PhpStorm.
 * User: marcopfeiffer
 * Date: 04.07.14
 * Time: 09:37
 */

namespace Hn\EntityBundle\Service;


use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;

class FormListService
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var EntityService
     */
    private $entityService;

    public function __construct(FormFactoryInterface $formFactory, EntityService $entityService)
    {
        $this->formFactory = $formFactory;
        $this->entityService = $entityService;
    }

    /**
     * @param string $name
     * @param string|FormTypeInterface $type
     * @param object[] $entities
     * @param array $options
     * @return FormInterface[]
     */
    public function createFormList($name, $type, array $entities, $options = array())
    {
        $forms = array();
        foreach ($entities as $entity) {
            $id = $this->entityService->getIdentifier($entity);
            $formName = $name . '_' . $id;
            $currentOptions = $options;

            if (is_callable($currentOptions)) {
                $currentOptions = $currentOptions($entity, $formName);
            }

            $form = $this->formFactory->createNamed($formName, $type, $entity, $currentOptions);
            $forms[$id] = $form;
        }
        return $forms;
    }

    /**
     * @param string $name
     * @param string|FormTypeInterface $type
     * @param object[] $entities
     * @param array $options
     * @return FormView[]
     */
    public function createFormViewList($name, $type, array $entities, $options = array())
    {
        $forms = $this->createFormList($name, $type, $entities, $options);
        $views = array();
        foreach ($forms as $id => $form) {
            $views[$id] = $form->createView();
        }
        return $views;
    }
} 