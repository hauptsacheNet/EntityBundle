<?php
/**
 * Created by PhpStorm.
 * User: marcopfeiffer
 * Date: 03.07.14
 * Time: 17:51
 */

namespace Hn\EntityBundle\Form;


use Doctrine\Common\Util\Debug;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\RouterInterface;

class UpdateableEntityType extends AbstractType
{
    /**
     * @var RouterInterface
     */
    private $router;

    function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        //Debug::dump($view->vars);

        $fullName = $view->vars['full_name'];

        if (!$options['rootFormType']) {
            $options['rootFormType'] = $form->getRoot()->getName();
        }

        $updateUrl = $this->router->generate('hn_entity_entity_submitformupdatepartial', array(
            'formType' => $options['rootFormType'],
            'propertyPath' => $fullName
        ));

        $view->vars['attr']['data-update-url'] = $updateUrl;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'rootFormType' => null
        ));
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
        return 'hn_updateable_entity';
    }
}