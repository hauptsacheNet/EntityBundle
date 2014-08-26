<?php

namespace Hn\EntityBundle\Form;

use Doctrine\Common\Util\Debug;
use Doctrine\ORM\EntityManager;
use Hn\EntityBundle\Entity\BaseEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EntityPlusType extends AbstractType
{
    /** @var EntityManager */
    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'modal_route' => null,
            'modal_route_parameters' => array()
        ));

        $resolver->setAllowedTypes(array(
            'modal_route' => array('string'),
            'modal_route_parameters' => array('array')
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        // gather information of the form this type is in
        $rootForm = $form->getRoot();
        $rootFormType = $rootForm->getConfig()->getType()->getInnerType();
        $view->vars['formType'] = $rootFormType->getName();
        $data = $rootForm->getData();

        if (! is_object($data)) {
            throw new \LogicException("The root form (which includes an entity_plus type) must have an entity as data. May be you should set the 'data_class' attribute of the root form");
        } else {
            // get the metadata for the root form data
            $meta = $this->em->getClassMetadata(get_class($data));
            if ($meta === null) {
                $type = is_object($data) ? get_class($data) : gettype($data);
                $formType = $view->vars['formType'];
                throw new \LogicException("entity_plus is used in $formType which doesn't has an Entity as data, got $type instead");
            }

            // identifier
            $ids = $meta->getIdentifierValues($data);
            if (count($ids) > 1) {
                throw new \LogicException("entity_plus can't handle entites with more than 1 identidier");
            }

            $view->vars['entityId'] = reset($ids);
            $view->vars['entityClass'] = $meta->getName();
        }

        // get options for modal request
        $view->vars['modalRoute'] = $options['modal_route'];
        $view->vars['modalRouteParameters'] = $options['modal_route_parameters'];
    }

    public function getParent()
    {
        return 'entity';
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'entity_plus';
    }
}