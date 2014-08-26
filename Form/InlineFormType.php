<?php
/**
 * Created by PhpStorm.
 * User: marcopfeiffer
 * Date: 03.07.14
 * Time: 17:51
 */

namespace Hn\EntityBundle\Form;


use Hn\EntityBundle\Service\EntityService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\RouterInterface;

class InlineFormType extends AbstractType
{
    /**
     * @var EntityService
     */
    private $entityService;

    /**
     * @var RouterInterface
     */
    private $router;

    function __construct(EntityService $entityService, RouterInterface $router)
    {
        $this->entityService = $entityService;
        $this->router = $router;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('form_type_class'));
    }


    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (!is_object($form->getData())) {
            throw new UnexpectedTypeException($form->getData(), 'entity');
        }
        $view->vars['submitPath'] = $this->router->generate('hn_entity_entity_updateproperty', array(
            'formTypeClass' => $options['form_type_class'],
            'formBaseName' => $form->getName(),
            'entityClass' => get_class($form->getData()),
            'entityId' => $this->entityService->getIdentifier($form->getData())
        ));
    }

    public function getParent()
    {
        return 'form';
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'hn_entity_inline_form';
    }
}