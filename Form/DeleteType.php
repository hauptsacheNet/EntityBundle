<?php
/**
 * Created by PhpStorm.
 * User: marcopfeiffer
 * Date: 02.07.14
 * Time: 12:59
 */

namespace Hn\EntityBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ButtonTypeInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DeleteType extends AbstractType implements ButtonTypeInterface
{
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('redirect'));
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['redirect'] = $options['redirect'];
    }

    public function getParent()
    {
        return 'button';
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'hn_entity_delete';
    }
}